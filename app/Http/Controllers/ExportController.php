<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

class ExportController extends Controller
{
    public function exportProducts()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }
}
