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
    // الحصول على محتويات السلة
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

// إضافة منتج إلى السلة مع الخيارات

// إضافة منتج إلى السلة مع الخيارات ومعالجة التوكن
public function addToCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1',
        'options' => 'nullable|array'
    ]);

    // محاولة المصادقة باستخدام التوكن إذا كان موجوداً
    $user = null;
    if ($request->bearerToken()) {
        try {
            $user = Auth::guard('sanctum')->user();
        } catch (\Exception $e) {
            // التوكن غير صالح، نتعامل كزائر
        }
    }

    // إذا لم يتم المصادقة بالتوكن، نستخدم المصادقة العادية
    if (!$user && Auth::check()) {
        $user = Auth::user();
    }

    $product = Product::findOrFail($request->product_id);
    $basePrice = $product->price;
    $totalPrice = $basePrice;

    // تحديد إذا كان يوزر أو جيست
    $userId = $user ? $user->id : null;
    $guestUuid = $request->header('Guest-UUID') ?? $request->input('guest_uuid');

    if (!$userId && !$guestUuid) {
        // لو مفيش uuid عند الجيست، نولده
        $guestUuid = (string) \Illuminate\Support\Str::uuid();
    }

    // حساب السعر الإجمالي مع الخيارات
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

    // إنشاء عنصر السلة
    $cart = Cart::create([
        'user_id' => $userId,
        'guest_uuid' => $userId ? null : $guestUuid,
        'product_id' => $product->id,
        'quantity' => $request->quantity,
        'price' => $basePrice + $optionsPrice, // سعر الوحدة مع الخيارات
    ]);

    // إضافة الخيارات إلى العلاقة إذا كانت موجودة
    if (!empty($selectedOptions)) {
        foreach ($selectedOptions as $option) {
            $cart->optionValues()->attach($option['option_value_id']);
        }
    }

    // تحميل العلاقات للحصول على البيانات الكاملة
    $cart->load('product', 'optionValues.option');

    // بناء الرد
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

    // إضافة guest_uuid للرد إذا كان زائراً
    if (!$userId) {
        $response['data']['guest_uuid'] = $guestUuid;

        return response()->json($response)
            ->cookie('guest_uuid', $guestUuid, 60*24*30, null, null, false, true);
    }

    return response()->json($response);
}




    // تحديث كمية المنتج في السلة
    public function updateQuantity(Request $request, $id)
    {
        $cartItem = Cart::find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'العنصر غير موجود في السلة'
            ], 404);
        }

        // التحقق من الملكية
        $this->authorizeCartItem($cartItem);

        // التحقق من توفر الكمية
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

    // حذف عنصر من السلة
    public function removeItem(Cart $cartItem)
    {
        // التحقق من الملكية
        $this->authorizeCartItem($cartItem);

        $cartItem->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج من السلة'
        ]);
    }

    // نقل سلة الزائر إلى المستخدم عند تسجيل الدخول
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

    // ------ الدوال المساعدة ------ //
    private function getCartItems(Request $request)
    {
        // نحاول نجيب المستخدم من التوكين لو موجود
        $token = $request->bearerToken();
        $user = null;
    
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable; // المستخدم المرتبط بالتوكين
            }
        }
    
        // لو المستخدم موجود (عن طريق التوكين أو Auth)
        if ($user) {
            return $user->cartItems()
                ->with(['product', 'optionValues.option'])
                ->get();
        }
    
        // لو المستخدم زائر
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
