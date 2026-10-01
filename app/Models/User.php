<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Notifications\AccountSecurityAlert;
use App\Services\Account\AccountEraser;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use SensitiveParameter;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'marketing_opt_in'])]
#[Hidden([
    'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    'app_authentication_secret', 'app_authentication_recovery_codes',
])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * A changed password, e-mail or phone is reported to the former e-mail and phone (F-147), whatever the way it
     * was changed: account page, password reset, courier app, back-office. Not for a temporary password set by the
     * store (it is sent with the credentials) nor for an erased account.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            $changes = array_values(array_filter(['password', 'email', 'phone'], fn (string $field) => $user->wasChanged($field)));

            if ($changes === [] || $user->must_change_password || $user->name === AccountEraser::ANONYMOUS) {
                return;
            }

            $phone = $user->getRawOriginal('phone');

            Notification::route('mail', $user->getRawOriginal('email'))
                ->route('whatsapp', $phone)
                ->route('sms', $phone)
                ->notify(new AccountSecurityAlert($user->name, $changes));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'marketing_opt_in' => 'boolean',
            'suspended_at' => 'datetime',
            'must_change_password' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'phone'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    /**
     * Stored in international form ("+2250701020304") whatever the way it was typed.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => PhoneNumber::normalize($value) ?? (filled($value) ? $value : null),
        );
    }

    /**
     * Account matching a login identifier: an e-mail address or a phone number (F-070).
     */
    public static function findByLogin(?string $login): ?self
    {
        $login = trim((string) $login);

        if ($login === '') {
            return null;
        }

        if (str_contains($login, '@')) {
            return static::where('email', Str::lower($login))->first();
        }

        $phone = PhoneNumber::normalize($login);

        return $phone === null ? null : static::where('phone', $phone)->first();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->isSuspended() && $this->hasAnyRole(array_map(fn (Role $role) => $role->value, Role::panelRoles()));
    }

    /**
     * A suspended account cannot sign in anywhere (F-122).
     */
    /**
     * Password check for the sign-in forms that also takes the hashing time when no account matches, so the answer
     * does not tell whether an identifier exists (F-147).
     */
    public static function passwordMatches(?self $user, #[SensitiveParameter] string $password): bool
    {
        $hash = $user?->password ?? Cache::rememberForever('auth.unknown-account-hash', fn () => Hash::make(Str::random(40)));

        return Hash::check($password, $hash) && $user !== null;
    }

    /**
     * A plain customer account: no back-office or courier role. Only these sign in on the storefront and the mobile
     * app (F-147); the team signs in on the back-office, with its two-factor code, and couriers on their own page.
     */
    public function isCustomer(): bool
    {
        return $this->roles()->doesntExist();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function courier(): HasOne
    {
        return $this->hasOne(Courier::class);
    }

    /**
     * Phone number used by the SMS channel.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest('id');
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function canAddAddress(): bool
    {
        return $this->addresses()->count() < Address::MAX_PER_CUSTOMER;
    }

    /**
     * "Enregistrer cette adresse" at checkout: the order's delivery details join the address book, while there is room.
     */
    public function saveAddressFromOrder(Order $order): void
    {
        if (! $this->canAddAddress()) {
            return;
        }

        $this->addresses()->create([
            'label' => 'Adresse '.($this->addresses()->count() + 1),
            'recipient_name' => $order->customer_name,
            'phone' => $order->phone,
            'commune_id' => $order->commune_id,
            'district' => $order->district,
            'landmark' => $order->landmark,
            'is_default' => $this->addresses()->doesntExist(),
        ]);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    public function requiresTwoFactor(): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $role) => $role->value, Role::requiringTwoFactor()));
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email ?? $this->phone ?? $this->name;
    }

    /**
     * @return array<string>|null
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param  array<string>|null  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
