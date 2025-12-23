<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $cacheKey = 'categories_all';
        $minutes = 60;

        $categories = Cache::remember($cacheKey, $minutes, function () {
            return Category::with('children')->whereNull('parent_id')->get();
        });

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $imagePath = asset("storage/$imagePath");
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'],
            'description' => $validated['description'],
            'image' => $imagePath,
        ]);

        Cache::forget('categories_all');

        return new CategoryResource($category);
    }

    public function show(Category $category, Request $request)
    {
        $cacheKey = 'category_' . $category->id . '_' . md5(json_encode($request->query()));
        $minutes = 30;
        $data = Cache::remember($cacheKey, $minutes, function () use ($category, $request) {
            $query = $category->products()
                ->with(['variants', 'category'])
                ->where('is_active', true);

            $this->applyFilters($query, $request);

            $products = $query->paginate($request->get('per_page', 12));

            return [
                'category' => $category,
                'products' => ProductResource::collection($products),
                'meta' => $this->getPaginationMeta($products)
            ];
        });

        return response()->json([
            'success' => true,
            ...$data
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = $category->image;
        if ($request->hasFile('image')) {
            if ($category->image) {
                $oldImage = str_replace(asset('storage/'), '', $category->image);
                Storage::disk('public')->delete($oldImage);
            }

            $imagePath = $request->file('image')->store('categories', 'public');
            $imagePath = asset("storage/$imagePath");
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'],
            'description' => $validated['description'],
            'image' => $imagePath,
        ]);

        Cache::forget('categories_all');
        Cache::forget('category_' . $category->id . '_*');

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        if ($category->image) {
            $imagePath = str_replace(asset('storage/'), '', $category->image);
            Storage::disk('public')->delete($imagePath);
        }

        $category->delete();

        Cache::forget('categories_all');
        Cache::forget('category_' . $category->id . '_*');

        return response()->json(null, 204);
    }

public function applyFilters(Request $request)
{
    $query = \App\Models\Product::with(['category', 'variants'])
        ->where('is_active', true);


    if ($request->filled('min_price')) {
        $query->whereHas('variants', function ($q) use ($request) {
            $q->where('price', '>=', $request->min_price);
        });
    }

    if ($request->filled('max_price')) {
        $query->whereHas('variants', function ($q) use ($request) {
            $q->where('price', '<=', $request->max_price);
        });
    }


    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    $sort = $request->get('sort', 'newest');

    switch ($sort) {
        case 'newest':
            $query->orderBy('created_at', 'desc');
            break;

        case 'oldest':
            $query->orderBy('created_at', 'asc');
            break;

        case 'offer':
            $query->whereNotNull('discount_price')
                  ->where('discount_price', '>', 0)
                  ->orderByRaw('(price - discount_price) / price DESC');
            break;

        case 'rating':

            $query->withAvg('reviews', 'rating')
                  ->orderBy('reviews_avg_rating', 'desc')
                  ->orderBy('created_at', 'desc');
            break;

        case 'highprice':

            $query->with(['variants' => function($q) {
                $q->orderBy('price', 'desc');
            }])->get()->sortByDesc(function($product) {
                return $product->variants->max('price');
            });
            break;

        case 'lowprice':

            $query->with(['variants' => function($q) {
                $q->orderBy('price', 'asc');
            }])->get()->sortBy(function($product) {
                return $product->variants->min('price');
            });
            break;

        case 'category':
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name')
                  ->select('products.*');
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

    $products = $query->paginate($request->get('per_page', 12));

    return response()->json([
        'success' => true,
        'data' => ProductResource::collection($products),
        'meta' => $this->getPaginationMeta($products)
    ]);
}


    private function getPaginationMeta($paginator)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }


    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $searchTerm = $request->input('query');

        $categories = Category::where(function($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('slug', 'LIKE', "%{$searchTerm}%"); // ← إضافة جديدة: بحث في الـ slug

            })
            ->with('children')
            ->get();

        return response()->json([
            'success' => true,
            'query' => $searchTerm,
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'count' => $categories->count()
            ]
        ]);
    }


    public function categoriesWithProducts(Request $request)
    {
        $categories = Category::with(['products' => function ($query) {
            $query->where('is_active', true)
                  ->with('variants')
                  ->select('id', 'name', 'slug', 'price', 'discount_price', 'images', 'category_id', 'stock');
        }])
        ->whereNull('parent_id')
        ->select('id', 'name', 'slug', 'parent_id')
        ->get();

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => Str::slug($category->slug),
                'products' => $category->products->map(function ($product) {

                    $minPrice = $product->variants->min('price') ?? $product->discount_price ?? $product->price;
                    $maxPrice = $product->variants->max('price') ?? $product->discount_price ?? $product->price;


                    $images = collect();

                    if (!empty($product->images)) {
                        if (is_array($product->images)) {
                            $images = collect($product->images);
                        } elseif (is_string($product->images)) {
                            $decoded = json_decode($product->images, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $images = collect($decoded);
                            } else {
                                $images = collect([$product->images]);
                            }
                        }
                    }


                    $imageUrls = $images->map(function ($img) {
                        $img = ltrim($img, '/');
                        if (Str::startsWith($img, ['http://', 'https://'])) {
                            return $img;
                        }
                        return asset('storage/' . $img);
                    });

                    $firstImage = $imageUrls->first() ?? asset('images/default-product.png');

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => Str::slug(trim($product->slug)),
                        'image' => $firstImage,
                        'images' => $imageUrls,
                        'min_price' => (float) $minPrice,
                        'max_price' => (float) $maxPrice,
                        'in_stock' => $product->variants->sum('stock') > 0 || $product->stock > 0,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'categories' => $data,
        ]);
    }



}
