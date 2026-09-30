<?php

namespace App\Services\Storefront;

use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Maintenance mode switched from the back-office (Administration › Maintenance): visitors get the maintenance page,
 * the team keeps the whole site. Unlike "php artisan down", the back-office, the courier app and CinetPay's calls
 * stay open, and it needs no server access. Saved as settings ("maintenance.*").
 */
class Maintenance
{
    public const DEFAULT_MESSAGE = 'Nous procédons à une mise à jour de la boutique pour vous offrir une meilleure expérience. Merci de votre patience.';

    public const DEFAULT_DURATION = 2;

    public function enabled(): bool
    {
        return Setting::get('maintenance.enabled') === '1';
    }

    public function message(): string
    {
        return (string) Setting::get('maintenance.message', self::DEFAULT_MESSAGE);
    }

    /** Estimated length, in hours (0.5 to 72). */
    public function duration(): float
    {
        return (float) Setting::get('maintenance.duration', self::DEFAULT_DURATION);
    }

    /** Progress of the work shown to visitors, 0 to 100. */
    public function progress(): int
    {
        return max(0, min(100, (int) Setting::get('maintenance.progress', 0)));
    }

    /**
     * Addresses let through: one IP or range (CIDR) per line.
     *
     * @return list<string>
     */
    public function allowedIps(): array
    {
        return self::parseIps((string) Setting::get('maintenance.allowed_ips', ''));
    }

    public function startedAt(): ?Carbon
    {
        $at = Setting::get('maintenance.started_at');

        return $at ? Carbon::parse($at) : null;
    }

    /** When the store should be back: start + estimated length. */
    public function expectedBackAt(): ?Carbon
    {
        return $this->startedAt()?->copy()->addMinutes((int) round($this->duration() * 60));
    }

    public function durationLabel(): string
    {
        $minutes = (int) round($this->duration() * 60);

        return match (true) {
            $minutes < 60 => "{$minutes} min",
            $minutes % 60 === 0 => intdiv($minutes, 60).' h',
            default => intdiv($minutes, 60).' h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT),
        };
    }

    public function enable(): void
    {
        Setting::store(['maintenance.enabled' => '1', 'maintenance.started_at' => now()->toIso8601String()]);
    }

    public function disable(): void
    {
        Setting::store(['maintenance.enabled' => '0', 'maintenance.started_at' => null]);
    }

    /**
     * Whether this request sees the site despite the maintenance: an allowed address, or a member of the team
     * signed in (anyone who can open the back-office).
     */
    public function lets(Request $request): bool
    {
        $ips = $this->allowedIps();

        if ($ips !== [] && IpUtils::checkIp((string) $request->ip(), $ips)) {
            return true;
        }

        $user = $request->user();

        return $user instanceof User && $user->canAccessPanel(Filament::getPanel('admin'));
    }

    /**
     * @return list<string>
     */
    public static function parseIps(string $text): array
    {
        return collect(preg_split('/[\s,;]+/', $text) ?: [])
            ->map(fn (string $ip) => trim($ip))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** An IP (v4 or v6), with or without a /mask. */
    public static function isValidIp(string $ip): bool
    {
        [$address, $mask] = array_pad(explode('/', $ip, 2), 2, null);

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $max = str_contains($address, ':') ? 128 : 32;

        return $mask === null || (ctype_digit($mask) && (int) $mask <= $max);
    }
}
