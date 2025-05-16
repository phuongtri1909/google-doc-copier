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
            $table->string('source_title')->nullable()->after('source_doc_id');
            $table->string('destination_title')->nullable()->after('destination_doc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('copy_jobs', function (Blueprint $table) {
            $table->dropColumn('source_title');
            $table->dropColumn('destination_title');
        });
    }
};
