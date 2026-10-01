<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\TwilioWhatsAppGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Meta's webhook for WhatsApp (F-134). GET: the subscription check (the verify token set in Meta's app). POST: the
 * delivery reports — sent, delivered, read, failed — signed with the app secret; failures are logged with Meta's
 * reason (number not on WhatsApp, template paused…) in storage/logs/whatsapp.log.
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $token = (string) config('services.whatsapp.verify_token');

        abort_unless($request->query('hub_mode') === 'subscribe' && $token !== '' && hash_equals($token, (string) $request->query('hub_verify_token')), 403);

        return response((string) $request->query('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
    }

    public function receive(Request $request): Response
    {
        $secret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        abort_unless($secret !== '' && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $signature), 403);

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['statuses'] ?? [] as $status) {
                    if (($status['status'] ?? null) === 'failed') {
                        $error = $status['errors'][0] ?? [];
                        Log::channel('whatsapp')->warning('Échec de remise ['.($status['recipient_id'] ?? '?').'] '
                            .($error['title'] ?? 'erreur inconnue').' (code '.($error['code'] ?? '?').')');
                    }
                }
            }
        }

        return response('', 200);
    }

    /**
     * Twilio's status callback for a WhatsApp message: signed with the auth token; undelivered and failed messages
     * are logged with Twilio's error code (63016: outside the 24-hour window, 63024: not a WhatsApp number…).
     */
    public function twilio(Request $request): Response
    {
        abort_unless(TwilioWhatsAppGateway::validSignature(
            (string) config('services.whatsapp.twilio.token'),
            $request->fullUrl(),
            $request->post(),
            (string) $request->header('X-Twilio-Signature'),
        ), 403);

        if (in_array($request->post('MessageStatus'), ['failed', 'undelivered'], true)) {
            Log::channel('whatsapp')->warning('Échec de remise Twilio ['.$request->post('To', '?').'] '
                .$request->post('MessageStatus').' (code '.$request->post('ErrorCode', '?').')');
        }

        return response('', 204);
    }
}
