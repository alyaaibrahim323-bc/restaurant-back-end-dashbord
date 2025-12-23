<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private $errors = [];
    private $importedCount = 0;
    private $skippedCount = 0;

    public function model(array $row)
    {
        // تنظيف وتحضير البيانات
        $name = trim($row['name'] ?? '');
        $categoryName = trim($row['category'] ?? '');
        $price = $this->cleanPrice($row['price'] ?? 0);
        
        // التحقق من التكرار بناءً على الاسم والفئة
        $existingProduct = Product::where('name', $name)
            ->whereHas('category', function($query) use ($categoryName) {
                $query->where('name', $categoryName);
            })
            ->first();

        if ($existingProduct) {
            $this->errors[] = "السطر {$this->getCurrentRow()}: المنتج '{$name}' موجود بالفعل في الفئة '{$categoryName}'";
            $this->skippedCount++;
            return null;
        }

        // البحث عن الفئة أو إنشاؤها
        $category = Category::where('name', $categoryName)->first();
        
        if (!$category) {
            $category = Category::create([
                'name' => $categoryName,
                'description' => 'تم إنشاؤها تلقائياً من خلال الاستيراد',
                'slug' => Str::slug($categoryName)
            ]);
        }

        // إنشاء المنتج
        $product = new Product([
            'name' => $name,
            'description' => $this->cleanDescription($row['description'] ?? ''),
            'price' => $price,
            'category_id' => $category->id,
            'stock_quantity' => $this->cleanStockQuantity($row['stock_quantity'] ?? 0),
            'is_available' => $this->cleanAvailability($row['is_available'] ?? 'yes'),
        ]);

        $this->importedCount++;
        return $product;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $cleaned = $this->cleanPrice($value);
                    if ($cleaned <= 0) {
                        $fail("السعر يجب أن يكون أكبر من الصفر");
                    }
                }
            ],
            'category' => 'required|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'is_available' => 'nullable|string|in:yes,no,true,false,1,0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'name.required' => 'حقل اسم المنتج مطلوب',
            'name.string' => 'اسم المنتج يجب أن يكون نصاً',
            'name.max' => 'اسم المنتج يجب ألا يتجاوز 255 حرفاً',
            
            'price.required' => 'حقل السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'price.min' => 'السعر يجب أن يكون أكبر من الصفر',
            
            'category.required' => 'حقل الفئة مطلوب',
            'category.string' => 'الفئة يجب أن تكون نصاً',
            'category.max' => 'اسم الفئة يجب ألا يتجاوز 255 حرفاً',
            
            'stock_quantity.integer' => 'الكمية في المخزون يجب أن تكون رقماً صحيحاً',
            'stock_quantity.min' => 'الكمية في المخزون يجب أن تكون صفر أو أكثر',
            
            'is_available.in' => 'حقل التوفر يجب أن يكون: yes, no, true, false, 1, أو 0',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $row = $failure->row();
            $errors = implode(', ', $failure->errors());
            
            $this->errors[] = "السطر {$row}: {$errors}";
            $this->skippedCount++;
        }
    }

    /**
     * دالة لتنظيف ومعالجة السعر
     */
    private function cleanPrice($price)
    {
        if (is_string($price)) {
            // إزالة أي رموز غير مرغوب فيها
            $price = preg_replace('/[^\d.]/', '', $price);
        }
        
        return floatval($price);
    }

    /**
     * دالة لتنظيف الوصف
     */
    private function cleanDescription($description)
    {
        return trim($description);
    }

    /**
     * دالة لتنظيف كمية المخزون
     */
    private function cleanStockQuantity($quantity)
    {
        $quantity = intval($quantity);
        return max(0, $quantity);
    }

    /**
     * دالة لتنظيف حقل التوفر
     */
    private function cleanAvailability($availability)
    {
        $availability = strtolower(trim($availability));
        
        $trueValues = ['yes', 'true', '1', 'نعم', 'متاح'];
        $falseValues = ['no', 'false', '0', 'لا', 'غير متاح'];
        
        if (in_array($availability, $trueValues)) {
            return true;
        }
        
        if (in_array($availability, $falseValues)) {
            return false;
        }
        
        return true; // القيمة الافتراضية
    }

    /**
     * الحصول على رقم السطر الحالي
     */
    private function getCurrentRow()
    {
        return $this->importedCount + $this->skippedCount + 1;
    }

    /**
     * الحصول على جميع الأخطاء
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * الحصول على عدد المنتجات المستوردة
     */
    public function getImportedCount()
    {
        return $this->importedCount;
    }

    /**
     * الحصول على عدد المنتجات المتخطاة
     */
    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    /**
     * الحصول على إحصائيات الاستيراد
     */
    public function getImportStats()
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'total_processed' => $this->importedCount + $this->skippedCount,
            'errors' => $this->errors
        ];
    }
}