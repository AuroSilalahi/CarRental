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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->string('type', 50);
            $table->string('license_plate', 20)->unique();
            $table->tinyInteger('passenger_capacity')->unsigned();
            $table->string('colour', 50);
            $table->smallInteger('year')->unsigned();
            $table->unsignedInteger('daily_rate_idr');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_luxury_brand')->default(false);
            $table->decimal('luxury_multiplier', 3, 1)->default(1.0);
            $table->string('image_path', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
