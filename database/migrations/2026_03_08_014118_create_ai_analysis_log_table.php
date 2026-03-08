<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_log', function (Blueprint $table) {
            $table->id();
            $table->string('image_path', 255)->nullable();
            $table->boolean('success')->default(false);
            $table->string('message', 255)->nullable();
            $table->integer('class')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->dateTime('request_timestamp', 6)->nullable();
            $table->dateTime('response_timestamp', 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_log');
    }
};
