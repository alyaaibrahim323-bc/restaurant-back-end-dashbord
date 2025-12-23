<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing table if it exists to avoid duplicate column errors
        Schema::dropIfExists('favorites');

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            // يمكن أن يكون إما user_id أو guest_uuid (واحد فقط مستخدم)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->uuid('guest_uuid')->nullable();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            // تأكد أن الضيف أو المستخدم لا يكرر نفس المنتج
            $table->unique(['user_id', 'product_id']);
            $table->unique(['guest_uuid', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
