<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expired guest carts (30 days without activity) and old back-in-stock alerts are deleted every night.
Schedule::command('model:prune')->daily();
