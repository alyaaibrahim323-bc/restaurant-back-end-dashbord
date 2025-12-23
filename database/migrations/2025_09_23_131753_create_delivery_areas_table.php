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
    Schema::create('delivery_areas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
        $table->string('area_name', 100);
        $table->string('city', 100)->nullable();
        $table->string('district', 100)->nullable();
        $table->decimal('delivery_fee', 8, 2);
        $table->decimal('min_order_amount', 8, 2)->default(0.00);
        $table->integer('estimated_delivery_time')->default(30); // بالدقايق
        $table->json('polygon_coordinates')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_areas');
    }
};
