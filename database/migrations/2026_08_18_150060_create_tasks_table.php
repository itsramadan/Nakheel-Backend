<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تاسك يضيفه المهندس يدوياً لمزرعة معينة، تحت برنامج (ري/تسميد/رش).
     * المزارع بيرفع دليل تنفيذ (صور/فيديو + ملاحظة) عن طريق
     * task_submissions، والمهندس يقبل أو يرفض.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('category'); // irrigation | fertilizing | spraying
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending | submitted | approved | rejected
            $table->timestamps();

            $table->index(['farm_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
