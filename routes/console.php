<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Modul SDM: status kontrak, mutasi terjadwal, pengingat kontrak 90/60/30/7 hari.
Schedule::command('hr:daily-maintenance')->dailyAt('06:00')->withoutOverlapping();
