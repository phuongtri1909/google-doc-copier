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
            $table->string('google_id')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            $table->string('role')->default('user')->after('password');
            $table->text('access_token')->nullable()->after('role');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('access_token_expiry')->nullable()->after('refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'avatar',
                'role',
                'access_token',
                'refresh_token',
                'access_token_expiry',
            ]);
        });
    }
};
