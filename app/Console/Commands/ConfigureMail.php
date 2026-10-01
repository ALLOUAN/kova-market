<?php

namespace App\Console\Commands;

use App\Support\EnvFile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Sends the shop's e-mails from a professional address of the domain (e.g. commandes@kovamarket.ci, created in the
 * LWS panel): writes the SMTP settings into .env from prompts, the password hidden. LWS's outgoing server is
 * mail.<domain>, port 465 (SSL) or 587 (STARTTLS), signed in with the full address.
 */
#[Signature('mail:smtp')]
#[Description('Send the e-mails from a professional address (SMTP settings in .env)')]
class ConfigureMail extends Command
{
    public function handle(): int
    {
        $address = trim((string) $this->ask('Adresse d’envoi', config('mail.from.address')));
        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');

            return self::FAILURE;
        }

        $host = trim((string) $this->ask('Serveur d’envoi (SMTP)', 'mail.'.substr(strrchr($address, '@'), 1)));
        $port = (int) $this->choice('Port', ['465', '587'], '465');

        $password = (string) $this->secret('Mot de passe de la boîte e-mail (saisie masquée)');
        if ($password === '') {
            $this->error('Le mot de passe est obligatoire.');

            return self::FAILURE;
        }

        EnvFile::put([
            'MAIL_MAILER' => 'smtp',
            // 465 is SSL from the start; 587 switches to TLS on its own (STARTTLS).
            'MAIL_SCHEME' => $port === 465 ? 'smtps' : 'null',
            'MAIL_HOST' => $host,
            'MAIL_PORT' => (string) $port,
            'MAIL_USERNAME' => $address,
            'MAIL_PASSWORD' => $password,
            'MAIL_FROM_ADDRESS' => $address,
        ]);
        $this->callSilently('config:clear');

        $this->info('Envoi configuré depuis '.$address.'. Essai : php artisan mail:test votre@adresse.com');

        return self::SUCCESS;
    }
}
