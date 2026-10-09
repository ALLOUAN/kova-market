<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expired guest carts (30 days without activity) and old back-in-stock alerts are deleted every night.
Schedule::command('model:prune')->daily();

// Dated sale prices (F-090): product prices follow the opening and closing of sale windows.
Schedule::command('catalog:refresh-sale-prices')->everyMinute()->withoutOverlapping();

// "N personnes ont vu ce produit" badge of the product cards: real visits of the last 15 minutes.
Schedule::command('catalog:refresh-viewers')->everyMinute()->withoutOverlapping();

// Sitemap for the search engines (F-154), regenerated every night.
Schedule::command('seo:sitemap')->dailyAt('03:00');

// Queued notifications (SMS, e-mails) on shared hosting (F-170): no permanent worker, so the scheduler, run every
// minute by the host's cron, empties the queue. Sentry raises an alert when these runs stop (F-172).
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->when(fn () => config('queue.default') !== 'sync')
    ->sentryMonitor('file-attente');

// Online orders left unpaid are cancelled and their stock put back on sale (F-056), after a last check with CinetPay.
Schedule::command('payments:expire-unpaid')->everyFiveMinutes()->withoutOverlapping();

// Newsletter campaigns scheduled in the back-office leave at their date; the queue then sends them in batches.
Schedule::command('newsletter:send-scheduled')->everyMinute()->withoutOverlapping();
