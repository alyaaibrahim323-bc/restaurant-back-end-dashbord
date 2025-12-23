<?php

// app/Http/Controllers/Api/FavoriteController.php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProductResource;


class FavoriteController extends Controller
{
  public function toggleFavorite(Request $request, Product $product)
{
    if ($request->bearerToken()) {
        try {
            $user = Auth::guard('sanctum')->user();

            if ($user) {
                $existing = Favorite::where('user_id', $user->id)
                                    ->where('product_id', $product->id)
                                    ->first();

                if ($existing) {
                    $existing->delete();
                    return response()->json(['message' => 'تمت الإزالة من المفضلة']);
                } else {
                    Favorite::create([
                        'user_id' => $user->id,
                        'product_id' => $product->id
                    ]);
                    return response()->json(['message' => 'تمت الإضافة إلى المفضلة']);
                }
            }
        } catch (\Exception $e) {
        }
    }

    if (Auth::check()) {
        $user = Auth::user();
        $existing = Favorite::where('user_id', $user->id)
                            ->where('product_id', $product->id)
                            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'تمت الإزالة من المفضلة']);
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'product_id' => $product->id
            ]);
            return response()->json(['message' => 'تمت الإضافة إلى المفضلة']);
        }
    }

    $guestUuid = $request->cookie('guest_uuid') ?? Str::uuid();
    $existing = Favorite::where('guest_uuid', $guestUuid)
                        ->where('product_id', $product->id)
                        ->first();

    if ($existing) {
        $existing->delete();
        $response = response()->json(['message' => 'تمت الإزالة من المفضلة']);
    } else {
        Favorite::create([
            'guest_uuid' => $guestUuid,
            'product_id' => $product->id
        ]);
        $response = response()->json(['message' => 'تمت الإضافة إلى المفضلة']);
    }

    return $response->cookie('guest_uuid', $guestUuid, 60*24*30);
}

public function transferGuestFavoritesToUser($user, $guestUuid)
{
    if ($guestUuid) {
        $guestFavorites = Favorite::where('guest_uuid', $guestUuid)->get();

        foreach ($guestFavorites as $favorite) {
            $existing = Favorite::where('user_id', $user->id)
                                ->where('product_id', $favorite->product_id)
                                ->first();

            if (!$existing) {
                Favorite::create([
                    'user_id' => $user->id,
                    'product_id' => $favorite->product_id
                ]);
            }

            $favorite->delete();
        }
    }
}

 public function getFavorites(Request $request) {
    if (auth()->check()) {
        $favorites = Favorite::where('user_id', auth()->id())->with('product')->get();
    } else {
        $guestUuid = $request->cookie('guest_uuid');
        $favorites = Favorite::where('guest_uuid', $guestUuid)->with('product')->get();
    }

    $favorites->transform(function ($favorite) {
        if ($favorite->product && $favorite->product->images) {
            $favorite->product->images = collect($favorite->product->images)
                ->map(function ($image) {
                    if (str_starts_with($image, 'http')) {
                        return $image;
                    }
                    return asset('storage/' . $image);
                })
                ->toArray();
        }
        return $favorite;
    });

    return response()->json(['data' => $favorites]);
}

public function getFavoriteProducts(Request $request) {
    if (auth()->check()) {
        $favorites = Favorite::where('user_id', auth()->id())->with('product')->get();
    } else {
        $guestUuid = $request->cookie('guest_uuid');
        $favorites = Favorite::where('guest_uuid', $guestUuid)->with('product')->get();
    }

    $favorites->transform(function ($favorite) {
        if ($favorite->product) {
            $favorite->product = new ProductResource($favorite->product);
        }
        return $favorite;
    });

    return response()->json(['data' => $favorites]);
}
    public function getTopFavorites(Request $request)
{
    $limit = $request->get('limit', 2);

    $topProducts = Product::withCount('favorites')
        ->with(['category'])
        ->orderBy('favorites_count', 'DESC')
        ->limit($limit)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $topProducts,
        'message' => 'أفضل ' . $limit . ' منتج مفضل'
    ]);
}

}
