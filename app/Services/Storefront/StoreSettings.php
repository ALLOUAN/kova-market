<?php

namespace App\Services\Storefront;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Store identity values: those saved in the back-office settings win over config/storefront.php.
 */
class StoreSettings
{
    /**
     * Fields of the "contact" group editable from the back-office.
     */
    public const CONTACT_FIELDS = ['phone', 'whatsapp', 'email', 'address', 'opening_hours'];

    /**
     * @return array<string, string>
     */
    public function contact(): array
    {
        $contact = config('storefront.contact');

        foreach (self::CONTACT_FIELDS as $field) {
            $contact[$field] = Setting::get("contact.{$field}", $contact[$field] ?? '');
        }

        return $contact;
    }

    /**
     * Social networks with a real profile url (a "#" url means "not set yet" and hides the icon).
     *
     * @return Collection<int, array<string, string>>
     */
    public function socialLinks(): Collection
    {
        return collect(config('storefront.social'))
            ->map(fn (array $network) => [...$network, 'url' => Setting::get("social.{$network['key']}", $network['url'])])
            ->reject(fn (array $network) => blank($network['url']) || $network['url'] === '#')
            ->values();
    }
}
