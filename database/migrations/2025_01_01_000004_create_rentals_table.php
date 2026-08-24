<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('pickup_location');
            $table->text('return_location');
            $table->unsignedInteger('total_cost_idr');
            $table->enum('status', [
                'pending',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'expired',
                'review_required',
            ])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
