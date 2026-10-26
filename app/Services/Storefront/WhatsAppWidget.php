<?php

namespace App\Services\Storefront;

use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * The floating WhatsApp button of the storefront (Contenus › Bouton WhatsApp): on or off, the message typed in
 * advance, the WhatsApp-like chat window it opens (name, status, greeting, button, automatic opening), where it sits
 * and on which screens. The number is the store's WhatsApp number (Paramètres › Coordonnées), shared with the rest of
 * the site. Each setting falls back to the default below while the back-office has not set it.
 */
class WhatsAppWidget
{
    /** Pages where the button stays hidden when "hide_on_checkout" is on: the order form and the payment (the order confirmation keeps it). */
    public const CHECKOUT_ROUTES = ['checkout.show', 'checkout.store', 'payments.*'];

    public function __construct(private StoreSettings $store) {}

    /**
     * @return array{enabled: bool, message: string, include_page: bool, greeting_enabled: bool, agent_name: string, status_text: string, greeting: string, button_label: string, greeting_delay: int, position: string, show_desktop: bool, show_mobile: bool, hide_on_checkout: bool}
     */
    public function settings(): array
    {
        $name = (string) config('storefront.name');
        $flag = fn (string $key, bool $default) => (bool) (int) Setting::get("whatsapp_widget.{$key}", $default ? '1' : '0');
        $text = fn (string $key, string $default) => (string) (Setting::get("whatsapp_widget.{$key}") ?: $default);

        return [
            'enabled' => $flag('enabled', true),
            'message' => $text('message', "Bonjour {$name}, j’ai une question."),
            'include_page' => $flag('include_page', true),
            'greeting_enabled' => $flag('greeting_enabled', true),
            'agent_name' => $text('agent_name', "Service client {$name}"),
            'status_text' => $text('status_text', 'Répond généralement en quelques minutes'),
            'greeting' => $text('greeting', 'Bonjour 👋 Comment pouvons-nous vous aider ?'),
            'button_label' => $text('button_label', 'Discuter sur WhatsApp'),
            'greeting_delay' => max(0, min(60, (int) Setting::get('whatsapp_widget.greeting_delay', 8))),
            'position' => Setting::get('whatsapp_widget.position') === 'right' ? 'right' : 'left',
            'show_desktop' => $flag('show_desktop', true),
            'show_mobile' => $flag('show_mobile', true),
            'hide_on_checkout' => $flag('hide_on_checkout', true),
        ];
    }

    public function number(): string
    {
        return preg_replace('/\D/', '', (string) ($this->store->contact()['whatsapp'] ?? ''));
    }

    /**
     * Whether the button shows on this page: switched on, a number set, at least one kind of screen, and not on the
     * cart or the checkout when asked so.
     */
    public function shownOn(Request $request): bool
    {
        $settings = $this->settings();

        return $settings['enabled']
            && $this->number() !== ''
            && ($settings['show_desktop'] || $settings['show_mobile'])
            && ! ($settings['hide_on_checkout'] && $request->routeIs(...self::CHECKOUT_ROUTES));
    }

    /**
     * The wa.me link with the message: the page's own message when it has one (a product, an order), else the
     * message set here; the page address is added when asked and not already in the message.
     */
    public function url(?string $pageMessage = null, ?string $pageUrl = null): string
    {
        $settings = $this->settings();
        $message = filled($pageMessage) ? $pageMessage : $settings['message'];

        if ($settings['include_page'] && filled($pageUrl) && ! str_contains($message, $pageUrl)) {
            $message .= "\n".$pageUrl;
        }

        return 'https://wa.me/'.$this->number().'?text='.rawurlencode($message);
    }
}
