<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Checks the mail settings without placing an order: sends a short e-mail right away (no queue).
 */
#[Signature('mail:test {email : Adresse du destinataire}')]
#[Description('Send a test e-mail through the configured mailer')]
class SendTestMail extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');

            return self::FAILURE;
        }

        try {
            Mail::raw('Ceci est un e-mail d’essai de KOVA MARKET : l’envoi des e-mails fonctionne.', function ($message) use ($email) {
                $message->to($email)->subject('Essai d’envoi – KOVA MARKET');
            });
        } catch (Throwable $exception) {
            $this->error('Refusé : '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('E-mail envoyé à '.$email.' (envoi : '.config('mail.default').').');

        return self::SUCCESS;
    }
}
