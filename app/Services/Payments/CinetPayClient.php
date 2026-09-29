<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CinetPay API v1 (F-060 to F-066), adapted from the MesRévisions integration: OAuth token cached 23 hours,
 * payment initiation (the customer then pays on CinetPay's page) and status check, the only source of truth
 * for a payment's outcome. An expired token is renewed and the call retried once. Keys come from the .env.
 *
 * @see "NOUVELLE DOCUMENTATION CYNETPAY.pdf" (POST /v1/oauth/login, POST /v1/payment, GET /v1/payment/{token})
 */
class CinetPayClient
{
    /** CinetPay codes (see the documentation's "Codes de statut"). */
    private const SUCCESS = 100;

    private const OK = 200;

    private const EXPIRED_TOKEN = 1003;

    private const TOKEN_TTL = 82800;

    public function isConfigured(): bool
    {
        return filled(config('services.cinetpay.api_key')) && filled(config('services.cinetpay.api_password'));
    }

    /**
     * Opens a payment on CinetPay; the customer must then be sent to its payment_url.
     *
     * @param  array{merchant_transaction_id: string, amount: int, designation: string, email: string, phone: ?string, first_name: string, last_name: string, success_url: string, failed_url: string, notify_url: string}  $payment
     *
     * @throws CinetPayException
     */
    public function initiate(array $payment, bool $retried = false): PaymentInitiation
    {
        $body = $this->request('post', '/v1/payment', array_filter([
            'currency' => $this->currency(),
            'merchant_transaction_id' => $payment['merchant_transaction_id'],
            'amount' => $payment['amount'],
            'lang' => 'fr',
            'designation' => $this->plain($payment['designation']),
            'client_email' => $payment['email'],
            'client_phone_number' => $payment['phone'],
            'client_first_name' => $this->plain($payment['first_name']),
            'client_last_name' => $this->plain($payment['last_name']),
            'success_url' => $payment['success_url'],
            'failed_url' => $payment['failed_url'],
            'notify_url' => $payment['notify_url'],
            'direct_pay' => false,
        ], fn ($value) => $value !== null && $value !== ''));

        $code = (int) ($body['code'] ?? 0);

        if ($code === self::EXPIRED_TOKEN && ! $retried) {
            $this->forgetToken();

            return $this->initiate($payment, true);
        }

        $details = $body['details'] ?? [];
        $detailCode = (int) ($details['code'] ?? 0);

        // A 200 may still carry a refusal in its details (e.g. 1004 INVALID_PARAMS).
        if ($code !== self::OK || ($detailCode >= 1000 && $detailCode < 2000 && $detailCode !== 1200) || blank($body['payment_url'] ?? null)) {
            Log::error('[CinetPay] Initiation refused', ['code' => $code, 'details' => $details, 'merchant_transaction_id' => $payment['merchant_transaction_id']]);

            throw new CinetPayException($this->message($detailCode ?: $code), $body);
        }

        return new PaymentInitiation(
            paymentUrl: $body['payment_url'],
            paymentToken: $body['payment_token'] ?? null,
            notifyToken: $body['notify_token'] ?? null,
            transactionId: $body['transaction_id'] ?? null,
            response: $body,
        );
    }

    /**
     * The payment's current state at CinetPay, by payment token, CinetPay transaction id or our reference.
     *
     * @throws CinetPayException when CinetPay cannot be reached or refuses the call: nothing must be concluded then
     */
    public function status(string $identifier, bool $retried = false): PaymentState
    {
        $body = $this->request('get', '/v1/payment/'.rawurlencode($identifier));
        $code = (int) ($body['code'] ?? 0);

        if ($code === self::EXPIRED_TOKEN && ! $retried) {
            $this->forgetToken();

            return $this->status($identifier, true);
        }

        if (in_array($code, [1002, 1004, 1005, 404], true)) {
            throw new CinetPayException($this->message($code), $body);
        }

        return new PaymentState(
            outcome: $this->outcome($code, (string) ($body['status'] ?? '')),
            code: $code,
            status: (string) ($body['status'] ?? ''),
            merchantTransactionId: $body['merchant_transaction_id'] ?? null,
            transactionId: $body['transaction_id'] ?? null,
            payerPhone: $body['user']['phone_number'] ?? null,
            operator: $body['payment_method'] ?? null,
            response: $body,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws CinetPayException
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->isConfigured()) {
            throw new CinetPayException('Le paiement en ligne n’est pas encore disponible.');
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->acceptJson()->withToken($this->token())->{$method}($this->url($path), $payload);
        } catch (CinetPayException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('[CinetPay] Unreachable', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new CinetPayException('Le service de paiement ne répond pas. Réessayez dans quelques instants.');
        }

        return $response->json() ?? [];
    }

    /**
     * @throws CinetPayException
     */
    private function token(): string
    {
        $token = Cache::get($this->tokenKey());

        if (filled($token)) {
            return $token;
        }

        try {
            $body = Http::timeout(30)->acceptJson()->post($this->url('/v1/oauth/login'), [
                'api_key' => config('services.cinetpay.api_key'),
                'api_password' => config('services.cinetpay.api_password'),
            ])->json() ?? [];
        } catch (Throwable $exception) {
            Log::error('[CinetPay] Token unreachable', ['error' => $exception->getMessage()]);

            throw new CinetPayException('Le service de paiement ne répond pas. Réessayez dans quelques instants.');
        }

        if ((int) ($body['code'] ?? 0) !== self::OK || blank($body['access_token'] ?? null)) {
            Log::error('[CinetPay] Token refused', ['code' => $body['code'] ?? null, 'status' => $body['status'] ?? null]);

            throw new CinetPayException('Le paiement en ligne est momentanément indisponible.', $body);
        }

        Cache::put($this->tokenKey(), $body['access_token'], self::TOKEN_TTL);

        return $body['access_token'];
    }

    private function forgetToken(): void
    {
        Cache::forget($this->tokenKey());
    }

    private function tokenKey(): string
    {
        return 'cinetpay:token:'.md5((string) config('services.cinetpay.api_key'));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.cinetpay.base_url'), '/').$path;
    }

    private function currency(): string
    {
        $currency = strtoupper((string) config('services.cinetpay.currency'));

        return in_array($currency, ['FCFA', 'CFA'], true) ? 'XOF' : $currency;
    }

    /**
     * The textual status wins over the numeric code (as in MesRévisions); anything unknown stays pending.
     */
    private function outcome(int $code, string $status): PaymentOutcome
    {
        return match (strtoupper(trim($status))) {
            'SUCCESS' => PaymentOutcome::Succeeded,
            'FAILED', 'INSUFFICIENT_BALANCE', 'EXPIRED', 'OTP_ERROR', 'OTP_EXPIRED', 'USER_NOT_FOUND', 'USER_IS_BLOCKED', 'NOT_ALLOWED' => PaymentOutcome::Failed,
            'INITIATED', 'PENDING', 'OK' => PaymentOutcome::Pending,
            default => match ($code) {
                self::SUCCESS => PaymentOutcome::Succeeded,
                2003, 2004, 2005, 2006, 2007, 2008, 2010, 2011 => PaymentOutcome::Failed,
                default => PaymentOutcome::Pending,
            },
        };
    }

    /**
     * CinetPay refuses a few characters in names and labels, and accented letters on some operators.
     */
    private function plain(string $text): string
    {
        $text = function_exists('transliterator_transliterate')
            ? (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $text)
            : (string) (iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text);

        return trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[#\/$_&]/', ' ', $text)));
    }

    private function message(int $code): string
    {
        return match ($code) {
            1002, 1003, 1005 => 'Le paiement en ligne est momentanément indisponible.',
            1004 => 'CinetPay a refusé les informations de paiement. Vérifiez votre nom, votre téléphone et votre e-mail.',
            1200 => 'Ce paiement a déjà été enregistré.',
            2005 => 'Solde insuffisant.',
            2010 => 'Le paiement a échoué.',
            2011 => 'Paiement refusé par CinetPay (adresse non autorisée).',
            default => 'Le paiement n’a pas pu être lancé. Réessayez dans quelques instants.',
        };
    }
}
