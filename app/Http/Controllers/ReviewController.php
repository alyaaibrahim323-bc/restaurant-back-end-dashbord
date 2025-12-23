<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ReviewResource;

class ReviewController extends Controller
{

    public function index(Request $request, Product $product)
    {
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort', 'newest');

        $query = $product->reviews()->with('user');


        switch ($sort) {
            case 'verified':
                $query->where('is_verified', true);
                break;
            case 'unverified':
                $query->where('is_verified', false);
                break;
            case 'highest':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
                'last_page' => $reviews->lastPage(),
            ],
            'rating_stats' => [
                'average' => round($product->reviews()->avg('rating') ?: 0, 1),
                'count' => $product->reviews()->count(),
                'verified_count' => $product->reviews()->where('is_verified', true)->count(),
                'unverified_count' => $product->reviews()->where('is_verified', false)->count(),
            ]
        ]);
    }


    public function store(Request $request, Product $product)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول لإضافة تقييم'
            ], 401);
        }

        if (!Review::canUserReview(Auth::id(), $product->id)) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذا المنتج مسبقاً'
            ], 403);
        }

        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $review = Review::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'order_id' => null,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التقييم بنجاح',
            'data' => new ReviewResource($review->load('user'))
        ], 201);
    }

    public function show(Review $review)
    {
        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review->load('user', 'product'))
        ]);
    }


    public function update(Request $request, Review $review)
    {
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بتعديل هذا التقييم'
            ], 403);
        }

        $request->validate([
            'rating' => 'sometimes|integer|between:1,5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $review->update($request->only(['rating', 'comment']));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التقييم بنجاح',
            'data' => new ReviewResource($review->fresh()->load('user'))
        ]);
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بحذف هذا التقييم'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقييم بنجاح'
        ]);
    }


    public function userReviews(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $reviews = Auth::user()->reviews()
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
                'last_page' => $reviews->lastPage(),
            ]
        ]);
    }


    public function stats(Product $product)
    {
        $ratingDistribution = [
            '1' => $product->reviews()->where('rating', 1)->count(),
            '2' => $product->reviews()->where('rating', 2)->count(),
            '3' => $product->reviews()->where('rating', 3)->count(),
            '4' => $product->reviews()->where('rating', 4)->count(),
            '5' => $product->reviews()->where('rating', 5)->count(),
        ];

        $totalReviews = $product->reviews()->count();
        $ratingPercentages = [];

        if ($totalReviews > 0) {
            foreach ($ratingDistribution as $rating => $count) {
                $ratingPercentages[$rating] = round(($count / $totalReviews) * 100, 2);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'average_rating' => round($product->reviews()->avg('rating') ?: 0, 1),
                'reviews_count' => $totalReviews,
                'rating_distribution' => $ratingDistribution,
                'rating_percentages' => $ratingPercentages,
                'verified_reviews_count' => $product->reviews()->where('is_verified', true)->count()
            ]
        ]);
    }

    public function getAllReviews(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $reviews = Review::with('user')
                            ->where('product_id', $productId)
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'data' => ReviewResource::collection($reviews),
                'rating_stats' => [
                    'average_rating' => round($reviews->avg('rating') ?: 0, 1),
                    'total_reviews' => $reviews->count(),
                    'rating_distribution' => [
                        '1_star' => $reviews->where('rating', 1)->count(),
                        '2_star' => $reviews->where('rating', 2)->count(),
                        '3_star' => $reviews->where('rating', 3)->count(),
                        '4_star' => $reviews->where('rating', 4)->count(),
                        '5_star' => $reviews->where('rating', 5)->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب التقييمات'
            ], 500);
        }
    }

public function getTopRatedProducts(Request $request)
{
    $limit = $request->get('limit', 10);
    $minReviews = $request->get('min_reviews', 1);

    $topRatedProducts = Product::with(['category'])
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->having('reviews_avg_rating', '>', 0)
        ->having('reviews_count', '>=', $minReviews)
        ->orderBy('reviews_avg_rating', 'DESC')
        ->orderBy('reviews_count', 'DESC')
        ->limit($limit)
        ->get()
        ->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'category' => $product->category,
                'average_rating' => round($product->reviews_avg_rating, 1),
                'reviews_count' => $product->reviews_count,
                'rating' => round($product->reviews_avg_rating, 1),
                'full_stars' => floor($product->reviews_avg_rating),
                'has_half_star' => ($product->reviews_avg_rating - floor($product->reviews_avg_rating)) >= 0.3
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $topRatedProducts,
        'message' => 'أكثر المنتجات تقييماً'
    ]);
}
}
