<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * أصناف النخيل في المزرعة (زغلول ١٢٠ · سماني ٩٠ ...).
     */
    public function up(): void
    {
        Schema::create('farm_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->string('name'); // زغلول، سماني، برحي، خلاص...
            $table->unsignedInteger('tree_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_varieties');
    }
};
