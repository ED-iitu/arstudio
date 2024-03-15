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
        Schema::create('ar_files', function (Blueprint $table) {
            $table->id();
            $table->integer('group_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('mind_file_path')->nullable();
            $table->string('status')->default('Ожидает подтверждения'); // Статус
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_files');
    }
};
