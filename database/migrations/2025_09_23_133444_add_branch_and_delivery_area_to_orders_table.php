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
    Schema::table('orders', function (Blueprint $table) {
        $table->foreignId('branch_id')
            ->nullable()
            ->constrained('branches')
            ->onDelete('set null');

        $table->foreignId('delivery_area_id')
            ->nullable()
            ->constrained('delivery_areas')
            ->onDelete('set null');

        $table->decimal('subtotal', 10, 2)->default(0.00);
        $table->decimal('delivery_fee', 8, 2)->default(0.00);
        $table->integer('estimated_delivery_time')->nullable();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
