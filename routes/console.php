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

// Sitemap for the search engines (F-154), regenerated every night.
Schedule::command('seo:sitemap')->dailyAt('03:00');
