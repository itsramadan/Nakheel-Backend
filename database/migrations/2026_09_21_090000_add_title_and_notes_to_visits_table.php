<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فيتشر "الزيارات" الجديدة - عنوان ووصف (ملاحظات) بيتسجلوا وقت إنشاء
 * الزيارة (مش بعد ما تتم زي قبل كده). `report_title` القديم بقى بلا فايدة
 * (كان بيتسجل بس وقت "تمت الزيارة") فبنشيله؛ `report_note` فاضل زي ما هو
 * كملاحظة اختيارية وقت إتمام الزيارة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('title')->after('farm_id');
            $table->text('notes')->nullable()->after('scheduled_date');
            $table->dropColumn('report_title');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['title', 'notes']);
            $table->string('report_title')->nullable();
        });
    }
};
