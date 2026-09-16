<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('clickup:sync')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->then(fn () => Artisan::call('pms:export-vault'));
