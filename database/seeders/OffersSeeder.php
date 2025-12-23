<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;

class OffersSeeder extends Seeder
{
    public function run()
    {
        Offer::create([
            'title' => 'خصم 10% على كل المنتجات',
            'description' => 'احصل على خصم 10% على مشترياتك القادمة',
            'points_required' => 100,
            'discount_percentage' => 10,
            'is_active' => true,
        ]);

        Offer::create([
            'title' => 'خصم 50 جنيه',
            'description' => 'احصل على خصم 50 جنيه على مشترياتك',
            'points_required' => 200,
            'fixed_discount' => 50,
            'is_active' => true,
        ]);

        Offer::create([
            'title' => 'شحن مجاني',
            'description' => 'احصل على شحن مجاني لطلباتك',
            'points_required' => 150,
            'is_active' => true,
        ]);
    }
}
