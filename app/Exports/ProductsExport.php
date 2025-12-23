<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $products;

    public function __construct($products = null)
    {
        $this->products = $products;
    }

    public function collection()
    {
        if ($this->products) {
            return $this->products;
        }

        return Product::with(['category', 'reviews'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Price',
            'Category',
            'Average Rating',
            'Total Reviews',
            'Stock Quantity',
            'Is Available',
            'Created At',
            'Updated At'
        ];
    }

  public function map($product): array
{
    return [
        $product->id,
        $product->name ?? 'غير محدد',
        $product->description ?? 'لا يوجد وصف',
        $product->price ?? 0,
        $product->category->name ?? 'لا توجد فئة',
        round($product->reviews->avg('rating') ?? 0, 2),
        $product->reviews->count(),
        $product->stock_quantity ?? 0,
        $product->is_available ? 'نعم' : 'لا',
        $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : 'غير محدد',
        $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : 'غير محدد',
    ];
}

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE0E0E0']
                ]
            ],
        ];
    }
}
