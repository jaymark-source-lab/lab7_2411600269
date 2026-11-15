<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// daily reorder check at 7:00 AM Philippine time
Schedule::command('inventory:check-reorder')
    ->dailyAt('07:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reorder-check.log'))
    ->onFailure(function () {
        Log::error('Daily reorder check failed. See storage/logs/reorder-check.log');
    });