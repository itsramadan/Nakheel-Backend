<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * زيارة ميدانية للمهندس لمزرعة معينة. بعد ما تتم، بيتسجل تقرير مختصر
     * (عنوان + ملاحظة) بيظهر للمزارع في "آخر الزيارات".
     */
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->string('status')->default('scheduled'); // scheduled | completed | cancelled

            // تقرير الزيارة (يتملى بعد ما تتم)
            $table->string('report_title')->nullable();
            $table->text('report_note')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
