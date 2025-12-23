<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
        $table->integer('rating')->unsigned()->between(1, 5);
        $table->text('comment')->nullable();
        $table->boolean('is_approved')->default(false);
        $table->timestamps();

        $table->unique(['user_id', 'product_id']); // منع تقييم مكرر لنفس المنتج
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
