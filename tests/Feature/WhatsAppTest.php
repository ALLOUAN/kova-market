<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\Settings;
use App\Models\Setting;
use App\Models\User;
use App\Services\Account\PasswordRecovery;
use App\Services\WhatsApp\CloudWhatsAppGateway;
use App\Services\WhatsApp\TwilioWhatsAppGateway;
use App\Services\WhatsApp\WhatsAppException;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\WhatsAppMessage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * WhatsApp Business (Meta Cloud API and Twilio, F-134): template messages, verification codes, delivery reports and the
 * back-office switches. SMS stays off by default.
 */
class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_receives_the_template_its_language_and_its_parameters(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);
        $gateway = new CloudWhatsAppGateway('token-secret', '1234567890', 'v21.0', 'fr');

        $gateway->send('+2250701020304', WhatsAppMessage::template('delivery_date', ['Awa', 'KM-261001-0001', 'jeudi 2 octobre']));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/1234567890/messages'
            && $request->hasHeader('Authorization', 'Bearer token-secret')
            && $request['to'] === '2250701020304'
            && $request['template']['name'] === 'kova_date_livraison'
            && $request['template']['language']['code'] === 'fr'
            && array_column($request['template']['components'][0]['parameters'], 'text') === ['Awa', 'KM-261001-0001', 'jeudi 2 octobre']);
    }

    public function test_verification_codes_repeat_the_code_in_the_copy_button(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        (new CloudWhatsAppGateway('t', '1', 'v21.0', 'fr'))->send('+2250701020304', WhatsAppMessage::template('verification_code', ['482913']));

        Http::assertSent(fn (Request $request) => $request['template']['name'] === 'kova_code'
            && $request['template']['components'][1] === ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => '482913']]]);
    }

    public function test_a_refusal_from_meta_is_reported_so_the_message_is_tried_again(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template name does not exist', 'code' => 132001]], 400)]);

        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('Template name does not exist (code 132001)');

        (new CloudWhatsAppGateway('t', '1', 'v21.0', 'fr'))->send('+2250701020304', WhatsAppMessage::template('back_in_stock', ['Enceinte', 'https://kovamarket.ci/produit/enceinte']));
    }

    public function test_twilio_sends_an_approved_template_by_its_content_sid(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201)]);
        Setting::store(['whatsapp.twilio_content.delivery_date' => 'HX'.str_repeat('a', 32)]);

        (new TwilioWhatsAppGateway('AC123', 'auth-token', '+14155238886'))
            ->send('+2250701020304', WhatsAppMessage::template('delivery_date', ['Awa', 'KM-261001-0001', 'jeudi 2 octobre']));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('AC123:auth-token'))
            && $request['From'] === 'whatsapp:+14155238886'
            && $request['To'] === 'whatsapp:+2250701020304'
            && $request['ContentSid'] === 'HX'.str_repeat('a', 32)
            && json_decode($request['ContentVariables'], true) === ['1' => 'Awa', '2' => 'KM-261001-0001', '3' => 'jeudi 2 octobre']
            && ! isset($request['Body'])
            && $request['StatusCallback'] === route('webhooks.twilio.whatsapp'));
    }

    public function test_without_a_content_sid_twilio_gets_the_plain_text_and_its_refusals_are_reported(): void
    {
        Http::fake(['api.twilio.com/*' => Http::sequence()
            ->push(['sid' => 'SM1'], 201)
            ->push(['code' => 63016, 'message' => 'Failed to send freeform message because you are outside the allowed window'], 400)]);
        $gateway = new TwilioWhatsAppGateway('AC123', 'auth-token', '+14155238886');

        $gateway->send('+2250701020304', WhatsAppMessage::template('back_in_stock', ['Enceinte JBL', 'https://kovamarket.ci/produit/enceinte']));
        Http::assertSent(fn (Request $request) => $request['Body'] === 'Bonne nouvelle : Enceinte JBL est de nouveau disponible. Commandez-le ici : https://kovamarket.ci/produit/enceinte À bientôt sur KOVA MARKET.');

        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('(code 63016)');
        $gateway->send('+2250701020304', WhatsAppMessage::template('back_in_stock', ['Enceinte JBL', 'https://kovamarket.ci/produit/enceinte']));
    }

    public function test_twilio_delivery_reports_must_carry_its_signature(): void
    {
        config(['services.whatsapp.twilio.token' => 'auth-token']);
        $url = route('webhooks.twilio.whatsapp');
        $fields = ['MessageSid' => 'SM1', 'MessageStatus' => 'undelivered', 'ErrorCode' => '63024', 'To' => 'whatsapp:+2250701020304'];
        ksort($fields);
        $signature = base64_encode(hash_hmac('sha1', $url.collect($fields)->map(fn ($value, $key) => $key.$value)->join(''), 'auth-token', true));

        $this->post($url, $fields, ['X-Twilio-Signature' => $signature])->assertNoContent();
        $this->post($url, $fields, ['X-Twilio-Signature' => 'faux'])->assertForbidden();
    }

    public function test_a_test_message_can_be_sent_from_the_command_line(): void
    {
        config(['services.whatsapp.driver' => 'twilio', 'services.whatsapp.twilio' => ['sid' => '', 'token' => '', 'from' => '+17372508034']]);
        $this->artisan('whatsapp:test', ['phone' => '0701020304'])->expectsOutputToContain('TWILIO_ACCOUNT_SID')->assertFailed();

        $gateway = $this->fakeGateway();
        config(['services.whatsapp.driver' => 'log']);
        $this->artisan('whatsapp:test', ['phone' => '07 01 02 03 04'])->assertSuccessful();
        $this->artisan('whatsapp:test', ['phone' => '+33 6 12 34 56 78'])->assertSuccessful();

        $this->assertSame(['+2250701020304', '+33612345678'], array_column($gateway->sent, 0));
        $this->assertSame('kova_commande_recue', $gateway->sent[0][1]->name());
    }

    public function test_a_message_must_fill_every_variable_of_its_template(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WhatsAppMessage::template('order_placed', ['Awa', 'KM-1']);
    }

    public function test_the_forgotten_password_code_arrives_on_whatsapp(): void
    {
        $gateway = $this->fakeGateway();
        User::factory()->customer()->create(['phone' => '0701020304']);

        $this->post('/mot-de-passe-oublie', ['login' => '07 01 02 03 04'])->assertRedirect(route('password.code'));
        $this->get(route('password.code'))->assertSeeText('un code à 6 chiffres vient de vous être envoyé par WhatsApp');

        [$phone, $message] = $gateway->sent[0];
        $this->assertSame(['+2250701020304', 'kova_code'], [$phone, $message->name()]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $message->parameters[0]);

        // With no phone channel at all, no code goes out and the page says how to get help.
        Setting::store(['notifications.whatsapp' => '0', 'notifications.sms' => '0']);
        app(PasswordRecovery::class)->request('0701020304');
        $this->assertCount(1, $gateway->sent);
    }

    public function test_meta_checks_the_webhook_and_its_reports_are_signed(): void
    {
        config(['services.whatsapp.verify_token' => 'verif-kova', 'services.whatsapp.app_secret' => 'app-secret']);

        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=verif-kova&hub.challenge=4242')->assertOk()->assertSee('4242');
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=faux&hub.challenge=4242')->assertForbidden();

        $payload = json_encode(['entry' => [['changes' => [['value' => ['statuses' => [['status' => 'failed', 'recipient_id' => '2250701020304', 'errors' => [['code' => 131026, 'title' => 'Message undeliverable']]]]]]]]]]);
        $this->call('POST', '/webhooks/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $payload, 'app-secret')], $payload)->assertOk();
        $this->call('POST', '/webhooks/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=faux'], $payload)->assertForbidden();
    }

    public function test_the_back_office_switches_the_channels(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->staff(Role::SuperAdmin)->create());

        Livewire::test(Settings::class)
            ->assertSeeText('kova_commande_recue')
            ->assertSeeText('Mode test')
            ->fillForm(['whatsapp_content.order_placed' => 'pas-un-sid'])
            ->call('save')
            ->assertHasFormErrors(['whatsapp_content.order_placed']);

        Livewire::test(Settings::class)
            ->fillForm(['notifications.whatsapp' => false, 'notifications.sms' => true, 'whatsapp_content.order_placed' => 'HX'.str_repeat('b', 32)])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['0', '1'], [Setting::get('notifications.whatsapp'), Setting::get('notifications.sms')]);
        $this->assertSame('HX'.str_repeat('b', 32), TwilioWhatsAppGateway::contentSid('order_placed'));
    }

    private function fakeGateway(): WhatsAppGateway
    {
        $gateway = new class implements WhatsAppGateway
        {
            /** @var list<array{string, WhatsAppMessage}> */
            public array $sent = [];

            public function send(string $phone, WhatsAppMessage $message): void
            {
                $this->sent[] = [$phone, $message];
            }
        };
        $this->app->instance(WhatsAppGateway::class, $gateway);

        return $gateway;
    }
}
