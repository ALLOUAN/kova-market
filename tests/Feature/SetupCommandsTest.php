<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Setup commands: the professional mail settings written into .env, test e-mail.
 */
class SetupCommandsTest extends TestCase
{
    public function test_the_professional_mail_settings_are_written_into_the_env_file(): void
    {
        $directory = sys_get_temp_dir().'/kova-env-'.uniqid();
        mkdir($directory);
        file_put_contents($directory.'/.env', "APP_NAME=\"KOVA MARKET\"\r\nMAIL_MAILER=log\r\nMAIL_PASSWORD=null\r\n");
        $this->app->useEnvironmentPath($directory);

        $this->artisan('mail:smtp')
            ->expectsQuestion('Adresse d’envoi', 'commandes@kovamarket.ci')
            ->expectsQuestion('Serveur d’envoi (SMTP)', 'mail.kovamarket.ci')
            ->expectsChoice('Port', '465', ['465', '587'])
            ->expectsQuestion('Mot de passe de la boîte e-mail (saisie masquée)', 'mot de passe#1')
            ->assertSuccessful();

        $this->assertSame(
            "APP_NAME=\"KOVA MARKET\"\r\nMAIL_MAILER=smtp\r\nMAIL_PASSWORD=\"mot de passe#1\"\r\nMAIL_SCHEME=smtps\r\nMAIL_HOST=mail.kovamarket.ci\r\nMAIL_PORT=465\r\nMAIL_USERNAME=commandes@kovamarket.ci\r\nMAIL_FROM_ADDRESS=commandes@kovamarket.ci\r\n",
            file_get_contents($directory.'/.env'),
        );

        $this->artisan('mail:smtp')
            ->expectsQuestion('Adresse d’envoi', 'pas-une-adresse')
            ->assertFailed();

        unlink($directory.'/.env');
        rmdir($directory);
    }

    public function test_a_test_e_mail_can_be_sent(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'pas-une-adresse'])->assertFailed();
        $this->artisan('mail:test', ['email' => 'client@example.com'])->assertSuccessful();
    }
}
