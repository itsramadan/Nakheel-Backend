<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('city')->nullable();
            $table->unsignedInteger('palm_count')->default(0);
            $table->decimal('area', 8, 2)->nullable(); // بالفدان
            $table->string('location')->nullable(); // الموقع / الحدود

            // الاشتراك
            $table->string('subscription_cycle')->default('monthly');
            $table->date('subscription_next_payment_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
