<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حقول "بيانات المزرعة والمعطيات الأساسية" الإضافية من الديزاين الجديد.
 * `variety_type` بديل لنظام "الأصناف المتعددة" القديم (جدول
 * `farm_varieties` نفسه سيبناه كما هو من غير مسح - مش مستخدم دلوقتي، بس
 * أبسط وأأمن من مسح جدول فيه داتا قديمة).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            // "الصنف" - مجدول / صعيدي / غيره (نص مباشر من الفلاتر، مش id).
            $table->string('variety_type')->nullable()->after('location');
            $table->unsignedSmallInteger('palm_age_years')->nullable()->after('variety_type');
            $table->date('planted_at')->nullable()->after('palm_age_years');
            $table->string('soil_type')->nullable()->after('planted_at');
            $table->string('water_source')->nullable()->after('soil_type');
            $table->text('water_analysis')->nullable()->after('water_source');
            $table->text('general_notes')->nullable()->after('water_analysis');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn([
                'variety_type', 'palm_age_years', 'planted_at',
                'soil_type', 'water_source', 'water_analysis', 'general_notes',
            ]);
        });
    }
};
