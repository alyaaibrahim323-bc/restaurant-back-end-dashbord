<?php
namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CategoriesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use Importable, SkipsErrors, SkipsFailures;

    private $existingCategories;

    public function __construct()
    {
        // جلب جميع التصنيفات الموجودة مسبقاً لتجنب التكرار
        $this->existingCategories = Category::all()->pluck('name', 'id')->toArray();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // إذا كان التصنيف موجود مسبقاً، تخطيه
            if (in_array($row['name'], $this->existingCategories)) {
                continue;
            }

            // البحث عن التصنيف الأب بالاسم
            $parentId = null;
            if (!empty($row['parent_category'])) {
                $parent = Category::where('name', $row['parent_category'])->first();
                $parentId = $parent ? $parent->id : null;
            }

            // إنشاء التصنيف الجديد
            Category::create([
                'name' => $row['name'],
                'slug' => $row['slug'] ?? Str::slug($row['name']),
                'parent_id' => $parentId,
                'description' => $row['description'] ?? '',
                'image' => $row['image_path'] ?? null,
            ]);

            // تحديث قائمة التصنيفات الموجودة
            $this->existingCategories[] = $row['name'];
        }
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|unique:categories,name',
            '*.slug' => 'nullable|unique:categories,slug',
            '*.parent_category' => 'nullable|exists:categories,name',
            '*.description' => 'nullable',
            '*.image_path' => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.name.required' => 'حقل الاسم مطلوب في الصف :attribute',
            '*.name.unique' => 'التصنيف موجود مسبقاً: :input',
            '*.slug.unique' => 'الرابط موجود مسبقاً: :input',
            '*.parent_category.exists' => 'التصنيف الأب غير موجود: :input',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
