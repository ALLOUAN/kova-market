<?php

namespace App\Enums;

use App\Services\Payments\CinetPayClient;
use Filament\Support\Contracts\HasLabel;

/**
 * How an order is paid (F-060 to F-063): at the door, or online through CinetPay (Orange Money, MTN MoMo,
 * Moov Money, Wave, bank card), offered once the CinetPay keys are set.
 */
enum PaymentMethod: string implements HasLabel
{
    case CashOnDelivery = 'paiement_livraison';
    case Online = 'cinetpay';

    public function getLabel(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Paiement à la livraison',
            self::Online => 'Paiement en ligne',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Vous payez en espèces ou par Mobile Money au livreur, à la réception.',
            self::Online => 'Orange Money, MTN MoMo, Moov Money, Wave ou carte bancaire, sur la page sécurisée de CinetPay.',
        };
    }

    /**
     * Paid on CinetPay's page before the order is prepared.
     */
    public function isOnline(): bool
    {
        return $this === self::Online;
    }

    /**
     * The methods the checkout offers now: online payment only once CinetPay is configured.
     *
     * @return list<self>
     */
    public static function available(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $method) => ! $method->isOnline() || app(CinetPayClient::class)->isConfigured(),
        ));
    }
}
