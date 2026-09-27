<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContactMessages\HandleAction;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function getTitle(): string
    {
        return "Message de {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        /** @var ContactMessage $message */
        $message = $this->getRecord();

        return [
            Action::make('whatsapp')
                ->label('Répondre sur WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url($message->whatsappUrl(), shouldOpenInNewTab: true),
            Action::make('email')
                ->label('Répondre par e-mail')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->url('mailto:'.$message->email.'?subject='.rawurlencode('Re : '.$message->subject->getLabel()))
                ->visible(filled($message->email)),
            HandleAction::make(),
            DeleteAction::make(),
        ];
    }
}
