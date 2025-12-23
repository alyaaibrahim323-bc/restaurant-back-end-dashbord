<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Http\Resources\ProductResource;
use App\Http\Resources\VariantResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // عرض جميع المنتجات (للضيوف والمستخدمين)
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variants'])
            ->where('is_active', true);

        // تطبيق الفلاتر
        $this->applyFilters($query, $request);

        $products = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    // عرض منتج واحد مع تفاصيل الخيارات
    public function show(Product $product)
    {
        $product->load([
            'category',
            'options.values',
            'variants.optionValues.option'
        ]);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product)
        ]);
    }

    // عرض المنتجات حسب الفئة مع فلترة متقدمة
    public function productsByCategory(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $query = $category->products()
            ->where('is_active', true)
            ->with('variants');

        $this->applyFilters($query, $request);

        $products = $query->paginate($request->get('per_page', 12));

        $data = $products->map(function ($product) {
            // ✅ معالجة الصور بشكل آمن
            $imageUrl = asset('images/default-product.png'); // صورة افتراضية
            if (!empty($product->images)) {
                if (is_array($product->images)) {
                    $firstImage = $product->images[0] ?? null;
                    if ($firstImage) {
                        $imageUrl = asset('storage/' . ltrim($firstImage, '/'));
                    }
                } elseif (is_string($product->images)) {
                    $decoded = json_decode($product->images, true);
                    if (is_array($decoded) && !empty($decoded[0])) {
                        $imageUrl = asset('storage/' . ltrim($decoded[0], '/'));
                    } elseif (!empty($product->images)) {
                        $imageUrl = asset('storage/' . ltrim($product->images, '/'));
                    }
                }
            }

            // ✅ حساب الأسعار بشكل دقيق
            $minPrice = $product->variants->min('price') ?? $product->price;
            $maxPrice = $product->variants->max('price') ?? $product->price;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'image' => $imageUrl,
                'base_price' => (float) $product->price,
                'min_price' => (float) $minPrice,
                'max_price' => (float) $maxPrice,
                'is_active' => (bool) $product->is_active,
                'views' => $product->views,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'products' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ]);
    }

    // دالة البحث في المنتجات
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $searchTerm = $request->input('query');

        $query = Product::with(['category', 'variants'])
            ->where('is_active', true)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });

        // 🔹 تطبيق الفلاتر إن وجدت
        $this->applyFilters($query, $request);

        $products = $query->paginate($request->get('per_page', 10));

        // 🔹 تجهيز البيانات قبل الإرسال
        $products->getCollection()->transform(function ($product) {
            // ✅ معالجة الصور بنفس المنهج في باقي الـ APIs
            $imageUrl = asset('images/default-product.png');
            if (!empty($product->images)) {
                if (is_array($product->images)) {
                    $firstImage = $product->images[0] ?? null;
                    if ($firstImage) {
                        $imageUrl = asset('storage/' . ltrim($firstImage, '/'));
                    }
                } elseif (is_string($product->images)) {
                    $decoded = json_decode($product->images, true);
                    if (is_array($decoded) && !empty($decoded[0])) {
                        $imageUrl = asset('storage/' . ltrim($decoded[0], '/'));
                    } elseif (!empty($product->images)) {
                        $imageUrl = asset('storage/' . ltrim($product->images, '/'));
                    }
                }
            }

            // ✅ حساب الأسعار بدقة
            $minPrice = $product->variants->min('price') ?? $product->discount_price ?? $product->price;
            $maxPrice = $product->variants->max('price') ?? $product->discount_price ?? $product->price;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'image' => $imageUrl,
                'base_price' => (float) $product->price,
                'min_price' => (float) $minPrice,
                'max_price' => (float) $maxPrice,
                'is_active' => (bool) $product->is_active,
                'category' => $product->category?->name ?? null,
            ];
        });

        // 🔹 استجابة منظمة وواضحة
        return response()->json([
            'success' => true,
            'query' => $searchTerm,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ]);
    }

    // تطبيق الفلاتر والترتيب
  // دالة عامة للفلترة (تستخدم الدالة الخاصة داخلياً)
public function applyFilters($query, Request $request)
{
    // فلترة بالسعر
    if ($request->filled('min_price') || $request->filled('max_price')) {
        $query->where(function($q) use ($request) {
            // للمنتجات التي لديها variants
            $q->whereHas('variants', function ($variantQuery) use ($request) {
                if ($request->filled('min_price')) {
                    $variantQuery->where('price', '>=', $request->min_price);
                }
                if ($request->filled('max_price')) {
                    $variantQuery->where('price', '<=', $request->max_price);
                }
            })
            // أو للمنتجات بدون variants
            ->orWhere(function($orQuery) use ($request) {
                $orQuery->whereDoesntHave('variants');
                if ($request->filled('min_price')) {
                    $orQuery->where('price', '>=', $request->min_price);
                }
                if ($request->filled('max_price')) {
                    $orQuery->where('price', '<=', $request->max_price);
                }
            });
        });
    }

    // الترتيب المبسط
    $sort = $request->get('sort', 'newest');
    switch ($sort) {
        case 'price_asc':
            $query->orderBy('price', 'asc');
            break;
        case 'price_desc':
        case 'highprice':
            $query->orderBy('price', 'desc');
            break;
        case 'popular':
            $query->orderBy('views', 'desc');
            break;
        case 'best_selling':
            $query->orderBy('sales_count', 'desc');
            break;
        default:
            $query->orderBy('created_at', 'desc');
    }
}

    // عرض العناصر الشائعة (Popular Items)
    public function topProducts(Request $request)
    {
        $limit = $request->get('limit', 10); // عدد المنتجات الراجعة

        $products = Product::with(['category', 'variants'])
            ->withCount('orderItems') // يعد الطلبات
            ->orderBy('order_items_count', 'desc') // يرتب حسب عدد الطلبات
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    // إنشاء منتج جديد (للمسؤولين فقط)
    public function store(Request $request) {
        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|lt:price',
            'category_id' => 'required|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // معالجة الصور
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $images[] = asset("storage/$path");
            }
        }

        // إنشاء المنتج
        $product = Product::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'category_id' => $request->category_id,
            'images' => $images,
        ]);

        return response()->json([
            'success' => true,
            'data' => $product,
        ], 201);
    }

    // تحديث المنتج (للمسؤولين فقط)
    public function update(Request $request, Product $product) {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|lt:price',
            'category_id' => 'sometimes|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // تحديث البيانات
        $product->update($request->except('images'));

        // تحديث الصور (إذا وُجِدَت)
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $images[] = asset("storage/$path");
            }
            $product->update(['images' => $images]);
        }

        return response()->json([
            'success' => true,
            'data' => $product->fresh(),
        ]);
    }

    // حذف المنتج (للمسؤولين فقط)
    public function destroy(Product $product) {
        if (!empty($product->images)) {
            $images = is_array($product->images) ? $product->images : json_decode($product->images, true);

            if (is_array($images)) {
                foreach ($images as $imageUrl) {
                    $path = str_replace(asset('storage/'), '', $imageUrl);
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $product->delete();

        return response()->json(null, 204);
    }

    // إضافة/إزالة المنتج من المفضلة (للمستخدمين والضيوف)
    public function toggleFavorite(Request $request, Product $product) {
        $favorites = $request->session()->get('favorites', []);
        if (in_array($product->id, $favorites)) {
            $favorites = array_diff($favorites, [$product->id]);
        } else {
            $favorites[] = $product->id;
        }
        $request->session()->put('favorites', $favorites);

        return response()->json(['message' => 'تم التحديث']);
    }

    // الحصول على متغير معين
    public function getVariant(ProductVariant $variant)
    {
        $variant->load('product', 'optionValues.option');
        return new VariantResource($variant);
    }
}