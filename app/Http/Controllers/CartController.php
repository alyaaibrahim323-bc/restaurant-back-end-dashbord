<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\OptionValue;
use App\Http\Resources\CartResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\ProductOptionValue;
use Laravel\Sanctum\PersonalAccessToken;



class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = $this->getCartItems($request);
        $total = $this->calculateTotal($cartItems);
        $itemsCount = $cartItems->sum('quantity');

        return response()->json([
            'success' => true,
            'items_count' => $itemsCount,
            'items' => CartResource::collection($cartItems),
            'subtotal' => $total,
            'shipping' => 0,
            'discount' => 0,
            'total' => $total
        ]);
    }


public function addToCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1',
        'options' => 'nullable|array'
    ]);

    $user = null;
    if ($request->bearerToken()) {
        try {
            $user = Auth::guard('sanctum')->user();
        } catch (\Exception $e) {
        }
    }

    if (!$user && Auth::check()) {
        $user = Auth::user();
    }

    $product = Product::findOrFail($request->product_id);
    $basePrice = $product->price;
    $totalPrice = $basePrice;

    $userId = $user ? $user->id : null;
    $guestUuid = $request->header('Guest-UUID') ?? $request->input('guest_uuid');

    if (!$userId && !$guestUuid) {
        $guestUuid = (string) \Illuminate\Support\Str::uuid();
    }

    $selectedOptions = [];
    $optionsPrice = 0;

    if ($request->has('options') && !empty($request->options)) {
        foreach ($request->options as $optionValueId) {
            $optionValue = ProductOptionValue::with('option')->find($optionValueId);

            if ($optionValue) {
                $optionsPrice += $optionValue->price_modifier;

                $selectedOptions[] = [
                    'option_id' => $optionValue->option->id,
                    'option_value_id' => $optionValue->id,
                    'name' => $optionValue->option->name,
                    'value' => $optionValue->value,
                    'price_modifier' => $optionValue->price_modifier,
                ];
            }
        }
    }

    $totalPrice = ($basePrice + $optionsPrice) * $request->quantity;

    $cart = Cart::create([
        'user_id' => $userId,
        'guest_uuid' => $userId ? null : $guestUuid,
        'product_id' => $product->id,
        'quantity' => $request->quantity,
        'price' => $basePrice + $optionsPrice,
    ]);

    if (!empty($selectedOptions)) {
        foreach ($selectedOptions as $option) {
            $cart->optionValues()->attach($option['option_value_id']);
        }
    }

    $cart->load('product', 'optionValues.option');

    $response = [
        'success' => true,
        'message' => 'تمت إضافة المنتج إلى السلة',
        'data' => [
            'id' => $cart->id,
            'quantity' => $cart->quantity,
            'unit_price' => number_format($cart->price, 2),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
                'base_price' => $product->price,
            ],
            'selected_options' => $selectedOptions,
            'total_price' => number_format($totalPrice, 2),
        ]
    ];

    if (!$userId) {
        $response['data']['guest_uuid'] = $guestUuid;

        return response()->json($response)
            ->cookie('guest_uuid', $guestUuid, 60*24*30, null, null, false, true);
    }

    return response()->json($response);
}




    public function updateQuantity(Request $request, $id)
    {
        $cartItem = Cart::find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'العنصر غير موجود في السلة'
            ], 404);
        }

        $this->authorizeCartItem($cartItem);

        $availableStock = $cartItem->product->stock;
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $availableStock
        ]);

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الكمية بنجاح',
            'data' => new CartResource($cartItem->load('product'))
        ]);
    }

    public function removeItem(Cart $cartItem)
    {
        $this->authorizeCartItem($cartItem);

        $cartItem->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج من السلة'
        ]);
    }

    public function transferGuestCart(Request $request)
    {
        $request->validate([
            'guest_uuid' => 'required|string'
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $transferred = Cart::where('guest_uuid', $request->guest_uuid)
            ->update([
                'user_id' => Auth::id(),
                'guest_uuid' => null
            ]);

        return response()->json([
            'success' => true,
            'message' => 'تم نقل محتويات السلة إلى حسابك',
            'count' => $transferred
        ]);
    }


    private function getCartItems(Request $request)
    {
        $token = $request->bearerToken();
        $user = null;

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }


        if ($user) {
            return $user->cartItems()
                ->with(['product', 'optionValues.option'])
                ->get();
        }

        $guestUuid = $this->getGuestUuid($request);
        return $guestUuid
            ? Cart::with(['product', 'optionValues.option'])
                ->where('guest_uuid', $guestUuid)
                ->get()
            : collect();
    }



    private function calculateTotal($cartItems)
    {
        return $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    private function getGuestUuid(Request $request)
    {
        return $request->cookie('guest_uuid') ?? $request->input('guest_uuid');
    }

    private function authorizeCartItem(Cart $cartItem)
    {
        if (Auth::check()) {
            if ($cartItem->user_id !== Auth::id()) {
                abort(403, 'غير مصرح بتعديل هذا العنصر');
            }
        } else {
            $guestUuid = $this->getGuestUuid(request());
            if ($cartItem->guest_uuid !== $guestUuid) {
                abort(403, 'غير مصرح بتعديل هذا العنصر');
            }
        }
    }
}
