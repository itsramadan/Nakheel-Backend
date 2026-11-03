<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FCM token (توكن إشعارات الموبايل) - لازم نخزّنه عشان الباك اند يقدر يبعت
 * push notifications للمستخدم. بيتسجل وقت verify-otp (لو الموبايل بعته)
 * و/أو عن طريق endpoint منفصل `POST /auth/fcm-token` لتحديثه بعد الدخول
 * (التوكن ده بيتغيّر من وقت للتاني حتى من غير ما المستخدم يعمل تسجيل دخول
 * جديد).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('phone_verified_at');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
