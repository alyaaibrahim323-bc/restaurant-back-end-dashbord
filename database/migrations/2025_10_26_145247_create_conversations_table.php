<?php
// database/migrations/2024_01_01_000001_create_conversations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('admin_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->enum('role', ['user', 'assistant'])->default('user');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            
            // فهارس لتحسين الأداء
            $table->index('session_id');
            $table->index(['user_id', 'created_at']);
            $table->index(['is_closed', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};