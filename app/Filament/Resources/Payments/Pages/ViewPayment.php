<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Payments\PaymentActions;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string
    {
        return "Paiement {$this->getRecord()->merchant_transaction_id}";
    }

    protected function getHeaderActions(): array
    {
        return [
            PaymentActions::check(),
            PaymentActions::refund(),
            Action::make('order')
                ->label('Voir la commande')
                ->icon('heroicon-o-shopping-cart')
                ->color('gray')
                ->visible(fn (Payment $record) => $record->order !== null)
                ->url(fn (Payment $record) => OrderResource::getUrl('view', ['record' => $record->order])),
        ];
    }
}
