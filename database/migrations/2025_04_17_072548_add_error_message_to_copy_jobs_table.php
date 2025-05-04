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
        Schema::table('copy_jobs', function (Blueprint $table) {
            // Add the error_message column, make it nullable
            $table->text('error_message')->nullable()->after('status'); // Or choose another position
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copy_jobs', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};