<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $credentialsPath = config('services.firebase.credentials');

        // بنسجّل الـ binding بس لو ملف الـ service account موجود فعلاً.
        // لو مش موجود، مبنعملوش binding خالص - FcmChannel بيتأكد بـ
        // app()->bound(Messaging::class) قبل ما يستخدمه، فمفيش أي error،
        // بس push notifications هتفضل متوقفة لحد ما الملف يتحط في مكانه.
        if ($credentialsPath && is_file($credentialsPath)) {
            $this->app->singleton(Messaging::class, fn () => (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging());
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
