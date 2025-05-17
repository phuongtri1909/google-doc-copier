<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('copy_jobs', function (Blueprint $table) {
            $table->string('parent_folder_id')->nullable()->after('folder_id');
            $table->string('document_folder_id')->nullable()->after('parent_folder_id');
        });
    }

    public function down()
    {
        Schema::table('copy_jobs', function (Blueprint $table) {
            $table->dropColumn(['parent_folder_id', 'document_folder_id']);
        });
    }
};