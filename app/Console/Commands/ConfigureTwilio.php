<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Writes the Twilio keys (F-134) into .env from hidden prompts, so they are never pasted anywhere else.
 */
#[Signature('whatsapp:twilio')]
#[Description('Store the Twilio Account SID, Auth Token and WhatsApp number in .env')]
class ConfigureTwilio extends Command
{
    public function handle(): int
    {
        $sid = trim((string) $this->ask('Account SID (AC…)'));
        if (! preg_match('/^AC[0-9a-f]{32}$/i', $sid)) {
            $this->error('L’Account SID commence par AC et compte 34 caractères.');

            return self::FAILURE;
        }

        $token = trim((string) $this->secret('Auth Token (saisie masquée)'));
        if (! preg_match('/^[0-9a-f]{32}$/i', $token)) {
            $this->error('L’Auth Token compte 32 caractères (lettres a-f et chiffres).');

            return self::FAILURE;
        }

        $from = trim((string) $this->ask('Numéro WhatsApp d’envoi', config('services.whatsapp.twilio.from') ?: '+17372508034'));
        $from = '+'.preg_replace('/\D/', '', $from);

        EnvFile::put([
            'WHATSAPP_DRIVER' => 'twilio',
            'TWILIO_ACCOUNT_SID' => $sid,
            'TWILIO_AUTH_TOKEN' => $token,
            'TWILIO_WHATSAPP_FROM' => $from,
        ]);
        $this->callSilently('config:clear');

        $this->info('Clés Twilio enregistrées dans .env. Essai : php artisan whatsapp:test 07XXXXXXXX');

        return self::SUCCESS;
    }
}
