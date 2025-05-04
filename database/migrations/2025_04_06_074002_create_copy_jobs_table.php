<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('copy_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('source_doc_id');
            $table->string('destination_doc_id')->nullable();
            $table->string('folder_id')->nullable();
            $table->string('email');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->integer('total_sentences')->default(0);
            $table->integer('current_position')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('interval_seconds')->default(60);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('copy_jobs');
    }
};