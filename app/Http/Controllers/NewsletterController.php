<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterWelcome;
use App\Services\Storefront\Analytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Newsletter sign-up (footer form and invitation window) and one-click unsubscribe from the link of every e-mail.
 */
class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // A filled hidden field means a robot: it is thanked like anyone and nothing is saved.
        if (filled($request->input('website'))) {
            return $this->answer($request, 'Merci ! Vous êtes inscrit à notre newsletter.');
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', Rule::in(array_keys(NewsletterSubscriber::SOURCES))],
        ], [
            'email.required' => 'Indiquez votre adresse e-mail.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
        ]);

        $wasActive = NewsletterSubscriber::where('email', mb_strtolower(trim($data['email'])))->whereNull('unsubscribed_at')->exists();
        $subscriber = NewsletterSubscriber::subscribe($data['email'], $data['source'] ?? 'footer');

        if (! $wasActive) {
            Notification::route('mail', $subscriber->email)->notify(new NewsletterWelcome($subscriber));
            app(Analytics::class)->lead($data['source'] ?? 'footer');
        }

        return $this->answer($request, $wasActive
            ? 'Vous êtes déjà inscrit à notre newsletter, merci !'
            : 'Merci ! Vous êtes inscrit à notre newsletter. Un e-mail de bienvenue vient de vous être envoyé.');
    }

    /**
     * The link of the e-mails asks for a confirmation first: mail scanners that open links do not unsubscribe anyone.
     */
    public function confirm(NewsletterSubscriber $subscriber): View
    {
        return view('pages.newsletter-unsubscribe', ['subscriber' => $subscriber, 'done' => ! $subscriber->isActive()]);
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): View
    {
        $subscriber->unsubscribe();

        return view('pages.newsletter-unsubscribe', ['subscriber' => $subscriber, 'done' => true]);
    }

    private function answer(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'analytics' => app(Analytics::class)->currentEvents()])
            : back()->with('newsletter_status', $message);
    }
}
