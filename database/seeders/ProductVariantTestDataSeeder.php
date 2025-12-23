<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Support\Str;


class ProductVariantTestDataSeeder extends Seeder
{
    public function run()
    {
        // إنشاء فئة
        $clothingCategory = Category::firstOrCreate(
    ['slug' => 'clothing'],
    ['name' => 'ملابس']
);


        // إنشاء منتج (قميص)
        $shirt = Product::create([
    'uuid' => (string) Str::uuid(),
    'name' => 'قميص رجالي',
    'description' => 'قميص قطني عالي الجودة',
    'price' => 99.99,
        'slug' => 'mens-shirt',

    'category_id' => $clothingCategory->id,
    'is_active' => true
]);

        // إضافة خيارات للمنتج
        $colorOption = ProductOption::create([
            'product_id' => $shirt->id,
            'name' => 'اللون',
            'required' => true
        ]);

        $sizeOption = ProductOption::create([
            'product_id' => $shirt->id,
            'name' => 'الحجم',
            'required' => true
        ]);

        // إضافة قيم للخيارات
        $redValue = ProductOptionValue::create([
            'option_id' => $colorOption->id,
            'value' => 'أحمر',
            'price_modifier' => 0
        ]);

        $blueValue = ProductOptionValue::create([
            'option_id' => $colorOption->id,
            'value' => 'أزرق',
            'price_modifier' => 0
        ]);

        $blackValue = ProductOptionValue::create([
            'option_id' => $colorOption->id,
            'value' => 'أسود',
            'price_modifier' => 5.00
        ]);

        $smallValue = ProductOptionValue::create([
            'option_id' => $sizeOption->id,
            'value' => 'S',
            'price_modifier' => 0
        ]);

        $mediumValue = ProductOptionValue::create([
            'option_id' => $sizeOption->id,
            'value' => 'M',
            'price_modifier' => 0
        ]);

        $largeValue = ProductOptionValue::create([
            'option_id' => $sizeOption->id,
            'value' => 'L',
            'price_modifier' => 0
        ]);

        // إنشاء متغيرات للمنتج
        $variants = [
            // أحمر
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-RED-S',
                'price' => 99.99,
                'stock' => 10,
                'image' => 'shirts/red-s.jpg',
                'option_values' => [$redValue->id, $smallValue->id]
            ],
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-RED-M',
                'price' => 99.99,
                'stock' => 5,
                'image' => 'shirts/red-m.jpg',
                'option_values' => [$redValue->id, $mediumValue->id]
            ],
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-RED-L',
                'price' => 99.99,
                'stock' => 0, // إنتهى المخزون
                'image' => 'shirts/red-l.jpg',
                'option_values' => [$redValue->id, $largeValue->id]
            ],

            // أزرق
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-BLUE-S',
                'price' => 99.99,
                'stock' => 3,
                'image' => 'shirts/blue-s.jpg',
                'option_values' => [$blueValue->id, $smallValue->id]
            ],
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-BLUE-M',
                'price' => 99.99,
                'stock' => 8,
                'image' => 'shirts/blue-m.jpg',
                'option_values' => [$blueValue->id, $mediumValue->id]
            ],

            // أسود
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-BLACK-S',
                'price' => 104.99, // سعر أعلى بسبب تعديل السعر
                'stock' => 15,
                'image' => 'shirts/black-s.jpg',
                'option_values' => [$blackValue->id, $smallValue->id]
            ],
            [
                'product_id' => $shirt->id,
                'sku' => 'SHIRT-BLACK-M',
                'price' => 104.99,
                'stock' => 20,
                'image' => 'shirts/black-m.jpg',
                'option_values' => [$blackValue->id, $mediumValue->id]
            ]
        ];

        foreach ($variants as $variantData) {
            $variant = ProductVariant::create([
                'product_id' => $variantData['product_id'],
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'stock' => $variantData['stock'],
                'image' => $variantData['image']
            ]);

            // ربط المتغير بقيم الخيارات
            $variant->optionValues()->attach($variantData['option_values']);
        }

        // منتج ثاني (هاتف)
        $phone = Product::create([
    'uuid' => (string) Str::uuid(),
    'name' => 'هاتف ذكي',
    'slug' => 'smartphone',
    'description' => 'أحدث هاتف ذكي بتقنيات متطورة',
    'price' => 2499.99,
    'category_id' => $clothingCategory->id,
    'is_active' => true
]);


        // خيارات الهاتف
        $storageOption = ProductOption::create([
            'product_id' => $phone->id,
            'name' => 'السعة',
            'required' => true
        ]);

        $colorPhoneOption = ProductOption::create([
            'product_id' => $phone->id,
            'name' => 'اللون',
            'required' => true
        ]);

        // قيم الخيارات
        $storage128 = ProductOptionValue::create([
            'option_id' => $storageOption->id,
            'value' => '128 جيجا',
            'price_modifier' => 0
        ]);

        $storage256 = ProductOptionValue::create([
            'option_id' => $storageOption->id,
            'value' => '256 جيجا',
            'price_modifier' => 200.00
        ]);

        $storage512 = ProductOptionValue::create([
            'option_id' => $storageOption->id,
            'value' => '512 جيجا',
            'price_modifier' => 400.00
        ]);

        $colorBlack = ProductOptionValue::create([
            'option_id' => $colorPhoneOption->id,
            'value' => 'أسود',
            'price_modifier' => 0
        ]);

        $colorSilver = ProductOptionValue::create([
            'option_id' => $colorPhoneOption->id,
            'value' => 'فضي',
            'price_modifier' => 0
        ]);

        $colorGold = ProductOptionValue::create([
            'option_id' => $colorPhoneOption->id,
            'value' => 'ذهبي',
            'price_modifier' => 100.00
        ]);

        // متغيرات الهاتف
        $phoneVariants = [
            // 128 جيجا
            [
                'product_id' => $phone->id,
                'sku' => 'PHONE-128-BLACK',
                'price' => 2499.99,
                'stock' => 15,
                'image' => 'phones/black-128.jpg',
                'option_values' => [$storage128->id, $colorBlack->id]
            ],
            [
                'product_id' => $phone->id,
                'sku' => 'PHONE-128-SILVER',
                'price' => 2499.99,
                'stock' => 10,
                'image' => 'phones/silver-128.jpg',
                'option_values' => [$storage128->id, $colorSilver->id]
            ],

            // 256 جيجا
            [
                'product_id' => $phone->id,
                'sku' => 'PHONE-256-BLACK',
                'price' => 2699.99,
                'stock' => 8,
                'image' => 'phones/black-256.jpg',
                'option_values' => [$storage256->id, $colorBlack->id]
            ],
            [
                'product_id' => $phone->id,
                'sku' => 'PHONE-256-SILVER',
                'price' => 2699.99,
                'stock' => 5,
                'image' => 'phones/silver-256.jpg',
                'option_values' => [$storage256->id, $colorSilver->id]
            ],
            [
                'product_id' => $phone->id,
                'sku' => 'PHONE-256-GOLD',
                'price' => 2799.99,
                'stock' => 3,
                'image' => 'phones/gold-256.jpg',
                'option_values' => [$storage256->id, $colorGold->id]
            ],

            // 512 جيجا
            [
                'product_id' => $phone->id,
                'sku' => 'PHONE-512-BLACK',
                'price' => 2899.99,
                'stock' => 0,
                'image' => 'phones/black-512.jpg',
                'option_values' => [$storage512->id, $colorBlack->id]
            ]
        ];

        foreach ($phoneVariants as $variantData) {
            $variant = ProductVariant::create([
                'product_id' => $variantData['product_id'],
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'stock' => $variantData['stock'],
                'image' => $variantData['image']
            ]);

            $variant->optionValues()->attach($variantData['option_values']);
        }

        $this->command->info('تم إنشاء 8 متغيرات للملابس و 6 متغيرات للهواتف بنجاح!');
    }
}
