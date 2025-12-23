<?php
// database/seeders/DeliveryAreasSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DeliveryArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryAreasSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('delivery_areas')->truncate();
        DB::table('branches')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🎯 بدء إنشاء الفروع والمناطق...');

        DB::transaction(function () {
            $this->createPyramidBranch();      // فرع الهرم
            $this->createOctoberBranch();      // فرع أكتوبر
            $this->createSheikhZayedBranch();  // فرع الشيخ زايد
            $this->createAhramGardensBranch(); // فرع حدائق الأهرام
        });

        $this->command->info('✅ تم إنشاء الفروع والمناطق بنجاح!');
        $this->command->info('📊 إجمالي الفروع: ' . Branch::count());
        $this->command->info('📍 إجمالي المناطق: ' . DeliveryArea::count());
    }

    private function createPyramidBranch()
    {
        $branch = Branch::create([
            'name' => 'فرع مشعل الهرم',
            'address' => 'الهرم - منطقة تجارية',
            'phone' => '+201234567891',
            'email' => 'pyramid@restaurant.com',
            'latitude' => 29.9792,
            'longitude' => 31.1342,
            'delivery_radius_km' => 15,
            'delivery_fee_base' => 25,
            'opening_hours' => $this->getDefaultOpeningHours(),
            'is_active' => true
        ]);

        $areas = [
            // خدمة 10 ج
            ['area_name' => 'جولدن جيم ومحيطه', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 10, 'min_order_amount' => 40, 'estimated_delivery_time' => 25],
            ['area_name' => '4 عمارات أمام بندق', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 10, 'min_order_amount' => 40, 'estimated_delivery_time' => 25],
            ['area_name' => 'فرافيرو', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 10, 'min_order_amount' => 40, 'estimated_delivery_time' => 25],
            ['area_name' => 'هلا كافيه', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 10, 'min_order_amount' => 40, 'estimated_delivery_time' => 25],

            // خدمة 20 ج
            ['area_name' => 'سيد مرعي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],
            ['area_name' => 'مدينة بيتكو', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],
            ['area_name' => 'ش الأشول', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],

            // خدمة 25 ج
            ['area_name' => 'آخر فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'زغلول حتى تقاطع ترسا', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'الوفاء والأمل هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'ش صقر أول السيسي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'قسم الهرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],

            // خدمة 30 ج
            ['area_name' => 'حدائق الأهرام القديمة', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'أبو الهول السياحي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'زغلول بعد تقاطع ترسا', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'آخر نزلة السيسي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'الأميرة فادية', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'فندق سياج', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'ش محمود الخيال', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'أبراج سفنكس', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'سيد خطاب', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'كمين أبو الهول', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'موريسكا', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],
            ['area_name' => 'فندق قاعود', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 30, 'min_order_amount' => 70, 'estimated_delivery_time' => 40],

            // خدمة 35 ج
            ['area_name' => 'شارع المستشفي هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'سهل حمزة', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'الكوم الأخضر', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'اللبيني هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'نزلة السمان', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'الوفاء والأمل فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'المريوطية هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'المريوطية فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'عزبة جبريل', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],
            ['area_name' => 'نزلة البطران', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 35, 'min_order_amount' => 80, 'estimated_delivery_time' => 45],

            // خدمة 40 ج
            ['area_name' => 'شارع العمدة القديم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'المجزر الآلي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'العريش هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'عز الدين عمر', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'اللبيني فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'مرور حدائق الأهرام', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'كفر غطاطي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'الشوربجي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],
            ['area_name' => 'كفر الجبل', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 50],

            // خدمة 45 ج
            ['area_name' => 'شارع ضياء هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'طالبية هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'طوابق فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'المنصورية', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'الثلاثيني الجديد', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'شارع ترسا بعد عز الدين', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'شارع الإخلاص', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'شارع العروبة', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'كايرو مول', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'حسن محمد هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'حسن محمد فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'المطبعة هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'التعاون هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],
            ['area_name' => 'التعاون فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 55],

            // خدمة 50 ج
            ['area_name' => 'كعابيش', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'فاطمة رشدي', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'ناصر الثورة', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'المساحة هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'أريزونا هرم', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'طالبية فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'مطبعة فيصل', 'city' => 'الجيزة', 'district' => 'فيصل', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'منشية البكاري', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'كفر طهرمس', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'مسجد السلام', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'ابن بطوطة', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],
            ['area_name' => 'مدرسة مصر للغات', 'city' => 'الجيزة', 'district' => 'الهرم', 'delivery_fee' => 50, 'min_order_amount' => 110, 'estimated_delivery_time' => 60],

            // ... يمكن إضافة المزيد من المناطق
        ];

        foreach ($areas as $area) {
            $branch->deliveryAreas()->create($area);
        }

        $this->command->info("✅ تم إنشاء فرع الهرم مع " . count($areas) . " منطقة");
    }

    private function createOctoberBranch()
    {
        $branch = Branch::create([
            'name' => 'فرع حدائق أكتوبر',
            'address' => 'حدائق أكتوبر - المنطقة المركزية',
            'phone' => '+201234567892',
            'email' => 'october@restaurant.com',
            'latitude' => 30.0330,
            'longitude' => 30.9752,
            'delivery_radius_km' => 20,
            'delivery_fee_base' => 30,
            'opening_hours' => $this->getDefaultOpeningHours(),
            'is_active' => true
        ]);

        $areas = [
            // خدمة 20 ج
            ['area_name' => 'كمبوند جولف', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],
            ['area_name' => 'كمبوند بيتا جرينز', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],
            ['area_name' => 'الحي الاسباني', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],
            ['area_name' => 'مساكن دهشور', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 20, 'min_order_amount' => 50, 'estimated_delivery_time' => 30],

            // خدمة 25 ج
            ['area_name' => 'تاون فيو', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'بالم فيو', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'مدينة زاهر', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'كمبوند أرابيانو', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'واحة الريحان', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'كمبوند كنز', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'جنا جرينز', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'السياحية أ', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'جرين جاردنز', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'كمبوند الربوة', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'كمبوند يوتيوبيا', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'اللوتس', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'بيت المصرية', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'رؤية سيتي', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'روضة السالمية', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'الرباب سيتي', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'دجلة جاردنز', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'المنتزة', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'باراديس', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'فيو جاردن', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],
            ['area_name' => 'الدولية بلازا', 'city' => 'الجيزة', 'district' => 'أكتوبر', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 35],

            // ... يمكن إضافة المزيد من المناطق
        ];

        foreach ($areas as $area) {
            $branch->deliveryAreas()->create($area);
        }

        $this->command->info("✅ تم إنشاء فرع أكتوبر مع " . count($areas) . " منطقة");
    }

    private function createSheikhZayedBranch()
    {
        $branch = Branch::create([
            'name' => 'فرع الشيخ زايد',
            'address' => 'الشيخ زايد - المنطقة التجارية',
            'phone' => '+201234567893',
            'email' => 'sheikhzayed@restaurant.com',
            'latitude' => 30.0469,
            'longitude' => 30.9752,
            'delivery_radius_km' => 25,
            'delivery_fee_base' => 35,
            'opening_hours' => $this->getDefaultOpeningHours(),
            'is_active' => true
        ]);

        $areas = [
            // خدمة 40 ج
            ['area_name' => 'الحي 16 زايد', 'city' => 'الجيزة', 'district' => 'الشيخ زايد', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 40],
            ['area_name' => 'دار مصر 16 زايد', 'city' => 'الجيزة', 'district' => 'الشيخ زايد', 'delivery_fee' => 40, 'min_order_amount' => 90, 'estimated_delivery_time' => 40],

            // خدمة 45 ج
            ['area_name' => 'الحي 9 زايد', 'city' => 'الجيزة', 'district' => 'الشيخ زايد', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 45],
            ['area_name' => 'شاليهات بدر الدين', 'city' => 'الجيزة', 'district' => 'الشيخ زايد', 'delivery_fee' => 45, 'min_order_amount' => 100, 'estimated_delivery_time' => 45],

            // ... يمكن إضافة المزيد من المناطق
        ];

        foreach ($areas as $area) {
            $branch->deliveryAreas()->create($area);
        }

        $this->command->info("✅ تم إنشاء فرع الشيخ زايد مع " . count($areas) . " منطقة");
    }

    private function createAhramGardensBranch()
    {
        $branch = Branch::create([
            'name' => 'فرع حدائق الأهرام',
            'address' => 'حدائق الأهرام - المنطقة السكنية',
            'phone' => '+201234567894',
            'email' => 'ahramgardens@restaurant.com',
            'latitude' => 29.9900,
            'longitude' => 31.1500,
            'delivery_radius_km' => 8,
            'delivery_fee_base' => 25,
            'opening_hours' => $this->getDefaultOpeningHours(),
            'is_active' => true
        ]);

        $areas = [
            ['area_name' => 'منطقة أ', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ب', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ج', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة د', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ز', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ح', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ط', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة هـ', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة و', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ك', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ل', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة م', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ن', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ع', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة س', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'منطقة ص', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'مساكن ضباط الرماية', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
            ['area_name' => 'مساكن شباب الرماية', 'city' => 'الجيزة', 'district' => 'حدائق الأهرام', 'delivery_fee' => 25, 'min_order_amount' => 60, 'estimated_delivery_time' => 30],
        ];

        foreach ($areas as $area) {
            $branch->deliveryAreas()->create($area);
        }

        $this->command->info("✅ تم إنشاء فرع حدائق الأهرام مع " . count($areas) . " منطقة");
    }

    private function getDefaultOpeningHours()
    {
        return [
            'saturday' => ['open' => '10:00', 'close' => '02:00'],
            'sunday' => ['open' => '10:00', 'close' => '02:00'],
            'monday' => ['open' => '10:00', 'close' => '02:00'],
            'tuesday' => ['open' => '10:00', 'close' => '02:00'],
            'wednesday' => ['open' => '10:00', 'close' => '02:00'],
            'thursday' => ['open' => '10:00', 'close' => '02:00'],
            'friday' => ['open' => '12:00', 'close' => '02:00']
        ];
    }
}