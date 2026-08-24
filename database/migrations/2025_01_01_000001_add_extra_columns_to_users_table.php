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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('province', 100)->nullable()->after('city');
            $table->integer('failed_login_attempts')->default(0)->after('province');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->enum('account_status', ['active', 'deactivated'])->default('active')->after('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'address',
                'city',
                'province',
                'failed_login_attempts',
                'locked_until',
                'account_status',
            ]);
        });
    }
};
