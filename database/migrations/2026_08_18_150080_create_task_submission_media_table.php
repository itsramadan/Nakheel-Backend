<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_submission_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_submission_id')->constrained('task_submissions')->cascadeOnDelete();
            $table->string('type'); // image | video
            $table->string('path'); // storage disk path
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_submission_media');
    }
};
