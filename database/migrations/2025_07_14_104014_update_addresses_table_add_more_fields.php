<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAddressesTableAddMoreFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            // إضافة الحقول الجديدة
            $table->string('building_number')->nullable()->after('postal_code');
            $table->string('apartment_number')->nullable()->after('building_number');
            $table->string('floor_number')->nullable()->after('apartment_number');
            $table->string('phone_number')->after('floor_number');
            $table->string('location_url')->nullable()->after('phone_number');
            $table->boolean('is_default')->default(false)->after('location_url');

            // إضافة حقول الموقع الجغرافي
            $table->decimal('latitude', 10, 7)->nullable()->after('is_default');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'building_number',
                'apartment_number',
                'floor_number',
                'phone_number',
                'location_url',
                'is_default',
                'latitude',
                'longitude'
            ]);
        });
    }
}
