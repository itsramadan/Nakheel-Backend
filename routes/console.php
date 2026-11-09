<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تذكيرات الإشعارات اليومية (تاسكات/زيارات/اشتراكات) - الساعة ٨ الصبح.
// محتاج cron entry واحد بس على السيرفر عشان الـ scheduler نفسه يشتغل:
// * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('notifications:send-reminders')->dailyAt('08:00');
