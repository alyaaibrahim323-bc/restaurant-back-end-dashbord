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
    Schema::create('branches', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100);
        $table->text('address')->nullable();
        $table->string('phone', 20)->nullable();
        $table->string('email', 100)->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->boolean('is_active')->default(true);
        $table->decimal('delivery_radius_km', 5, 2)->default(10.00);
        $table->decimal('delivery_fee_base', 8, 2)->default(10.00);
        $table->json('opening_hours')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
