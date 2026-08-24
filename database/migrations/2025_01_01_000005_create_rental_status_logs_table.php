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
        Schema::create('rental_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->enum('status', [
                'pending',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'expired',
                'review_required',
            ]);
            $table->timestamp('changed_at');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('notes')->nullable();
            // No timestamps() — use changed_at only
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_status_logs');
    }
};
