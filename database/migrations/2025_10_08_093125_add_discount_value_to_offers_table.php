<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // إضافة الحقول الجديدة إذا لم تكن موجودة
            if (!Schema::hasColumn('offers', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('offers', 'promo_code')) {
                $table->string('promo_code')->unique()->nullable()->after('description');
            }
            if (!Schema::hasColumn('offers', 'image')) {
                $table->string('image')->nullable()->after('promo_code');
            }
            if (!Schema::hasColumn('offers', 'color')) {
                $table->string('color')->default('#3B82F6')->after('image');
            }
            if (!Schema::hasColumn('offers', 'usage_limit')) {
                $table->integer('usage_limit')->nullable()->after('valid_until');
            }
            if (!Schema::hasColumn('offers', 'used_count')) {
                $table->integer('used_count')->default(0)->after('usage_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // إزالة الحقول الجديدة في حالة التراجع
            $table->dropColumn([
                'discount_value',
                'promo_code',
                'image', 
                'color',
                'usage_limit',
                'used_count'
            ]);
        });
    }
};