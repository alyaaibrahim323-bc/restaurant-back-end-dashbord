<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Category::with('parent')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Parent Category',
            'Description',
            'Image Path',
            'Created At',
            'Updated At'
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->slug,
            $category->parent ? $category->parent->name : '',
            strip_tags($category->description),
            $category->image,
            $category->created_at,
            $category->updated_at
        ];
    }
}
