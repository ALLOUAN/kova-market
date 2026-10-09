<?php

namespace App\Http\Controllers;

use App\Enums\ContactSubject;
use App\Enums\Permission;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use App\Services\Storefront\Analytics;
use App\Services\Storefront\StoreSettings;
use App\Support\PhoneNumber;
use App\Support\StaffRecipients;
use Filament\Actions\Action;
use Filament\Notifications\Notification as BackOfficeAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Contact page (F-080): store details, WhatsApp and a message form. Messages are kept for the back-office and
 * copied by e-mail to the store's contact address.
 */
class ContactController extends Controller
{
    public function show(Request $request, StoreSettings $settings): View
    {
        $user = $request->user();

        return view('pages.contact', [
            'contact' => $settings->contact(),
            'whatsappUrl' => $settings->whatsappUrl('Bonjour '.config('storefront.name').', '),
            'subjects' => ContactSubject::cases(),
            'startedAt' => Crypt::encryptString((string) now()->timestamp),
            'defaults' => [
                'name' => $user?->name,
                'phone' => $user?->phone ? PhoneNumber::format($user->phone) : null,
                'email' => $user?->email,
            ],
        ]);
    }

    public function store(ContactRequest $request, StoreSettings $settings, Analytics $analytics): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->details(),
            'user_id' => $request->user()?->getKey(),
            'ip' => $request->ip(),
        ]);

        if (filled($email = $settings->contact()['email'] ?? null)) {
            Notification::route('mail', $email)->notify(new ContactMessageReceived($message));
        }

        BackOfficeAlert::make()
            ->title("Message de {$message->name}")
            ->body("{$message->subject->getLabel()} : ".Str::limit($message->message, 80))
            ->icon('heroicon-o-envelope')
            ->actions([Action::make('open')->label('Voir les messages')->url(ContactMessageResource::getUrl('index'))])
            ->sendToDatabase(StaffRecipients::with(Permission::ManageOrders));

        $analytics->contact('formulaire');

        return redirect()->route('contact.show')->with('notice', 'Merci, votre message est bien envoyé. Nous vous répondons au plus vite, en général dans la journée.');
    }
}
