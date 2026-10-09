<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One event for Meta's Conversions API (App\Services\Storefront\MetaConversions), in the background so that no
 * page waits for Meta. Retried a few times; a lost event is only logged, the shop goes on.
 */
class SendMetaConversionEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 300];

    /**
     * @param  array<string, mixed>  $event
     */
    public function __construct(public string $pixelId, public array $event) {}

    public function handle(): void
    {
        $version = config('services.meta.graph_version');

        Http::asJson()->timeout(10)
            ->post("https://graph.facebook.com/{$version}/{$this->pixelId}/events", array_filter([
                'data' => [$this->event],
                'access_token' => config('services.meta.conversions_token'),
                'test_event_code' => config('services.meta.test_event_code'),
            ]))
            ->throw();
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('[Meta] Conversions API event not sent', [
            'event' => $this->event['event_name'] ?? null,
            'id' => $this->event['event_id'] ?? null,
            'error' => $exception?->getMessage(),
        ]);
    }
}
