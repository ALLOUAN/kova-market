<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saved delivery address of a customer (F-071). Only one address per customer is the default one.
 */
#[Fillable(['user_id', 'label', 'recipient_name', 'phone', 'commune_id', 'district', 'landmark', 'is_default'])]
class Address extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Address $address): void {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)->whereKeyNot($address->getKey())->update(['is_default' => false]);
            }
        });

        // Deleting the default address hands the role to the most recent remaining one.
        static::deleted(function (Address $address): void {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)->latest('id')->first()?->update(['is_default' => true]);
            }
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
            'is_default' => 'boolean',
        ];
    }

    protected function phone(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => PhoneNumber::normalize($value) ?? $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function summary(): string
    {
        return collect([$this->district, $this->commune?->name, $this->landmark])->filter()->implode(', ');
    }
}
