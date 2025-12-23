<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\User;
use App\Models\Order;
use App\Models\Address;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OfferController extends Controller
{
    /**
     * عرض جميع العروض المتاحة
     */
    public function index(Request $request)
    {
        try {
            Log::info('Fetching offers list', ['filters' => $request->all()]);

            $query = Offer::active()->withCount('users');

            if ($request->has('promo_code')) {
                $query->byPromoCode($request->promo_code);
            }

            if ($request->has('discount_type')) {
                $query->where('discount_type', $request->discount_type);
            }

            $offers = $query->get()->map(function($offer) {
                return $this->formatOfferResponse($offer);
            });

            Log::info('Successfully fetched offers', ['count' => $offers->count()]);

            return response()->json([
                'success' => true,
                'offers' => $offers
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching offers: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب العروض'
            ], 500);
        }
    }

    /**
     * عرض العروض التي استخدمها المستخدم
     */
    public function myOffers()
    {
        try {
            // 🔐 التحقق من تسجيل الدخول
            $user = Auth::user();
            if (!$user) {
                Log::warning('Unauthorized access to myOffers');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لعرض العروض المستخدمة'
                ], 401);
            }

            Log::info('Fetching user offers', ['user_id' => $user->id]);

            $offers = $user->offers()
                ->withPivot('used_at', 'order_id', 'discount_amount')
                ->orderBy('user_offers.created_at', 'desc')
                ->get()
                ->map(function($offer) {
                    return $this->formatOfferResponse($offer);
                });

            Log::info('Successfully fetched user offers', [
                'user_id' => $user->id,
                'count' => $offers->count()
            ]);

            return response()->json([
                'success' => true,
                'offers' => $offers,
                'total_used' => $offers->count(),
                'total_savings' => $offers->sum('pivot.discount_amount')
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user offers: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب العروض المستخدمة'
            ], 500);
        }
    }

    /**
     * عرض تفاصيل عرض محدد
     */
    public function show(Offer $offer)
    {
        try {
            Log::info('Fetching offer details', ['offer_id' => $offer->id]);

            return response()->json([
                'success' => true,
                'offer' => $this->formatOfferResponse($offer)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching offer details: ' . $e->getMessage(), [
                'offer_id' => $offer->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب تفاصيل العرض'
            ], 500);
        }
    }

    /**
     * البحث عن عرض بالبرومو كود
     */
    public function findByPromoCode($promoCode)
    {
        try {
            Log::info('Finding offer by promo code', ['promo_code' => $promoCode]);

            $offer = Offer::active()->byPromoCode($promoCode)->first();

            if (!$offer) {
                Log::warning('Promo code not found', ['promo_code' => $promoCode]);
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم غير موجود أو منتهي الصلاحية'
                ], 404);
            }

            Log::info('Successfully found offer by promo code', [
                'promo_code' => $promoCode,
                'offer_id' => $offer->id
            ]);

            return response()->json([
                'success' => true,
                'offer' => $this->formatOfferResponse($offer)
            ]);

        } catch (\Exception $e) {
            Log::error('Error finding offer by promo code: ' . $e->getMessage(), [
                'promo_code' => $promoCode,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في البحث عن كود الخصم'
            ], 500);
        }
    }

    /**
     * إنشاء عرض جديد (للمشرفين)
     */
    public function store(Request $request)
    {
        try {
            // 🔐 التحقق من صلاحية المشرف
            $user = Auth::user();
            if (!$user || !$user->is_admin) {
                Log::warning('Unauthorized admin access attempt', ['user_id' => $user?->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بهذا الإجراء'
                ], 403);
            }

            Log::info('Admin creating new offer', ['admin_id' => $user->id, 'data' => $request->all()]);

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'promo_code' => 'required|string|unique:offers,promo_code|max:50',
                'discount_type' => 'required|in:percentage,fixed,free_delivery',
                'discount_value' => 'required_if:discount_type,percentage,fixed|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'color' => 'nullable|string|max:7',
                'valid_until' => 'nullable|date|after:today',
                'usage_limit' => 'nullable|integer|min:1'
            ]);

            $data = $request->only([
                'title', 'description', 'promo_code', 'discount_type', 
                'discount_value', 'color', 'valid_until', 'usage_limit'
            ]);

            $data['is_active'] = $request->get('is_active', true);

            if ($data['discount_type'] === 'free_delivery') {
                $data['discount_value'] = 0;
            }

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('offers', 'public');
                $data['image'] = $imagePath;
            }

            if (empty($data['promo_code'])) {
                $data['promo_code'] = $this->generatePromoCode();
            }

            $offer = Offer::create($data);

            Log::info('Successfully created offer', [
                'admin_id' => $user->id,
                'offer_id' => $offer->id,
                'promo_code' => $offer->promo_code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العرض بنجاح',
                'offer' => $this->formatOfferResponse($offer)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error creating offer', [
                'errors' => $e->errors(),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error creating offer: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء العرض'
            ], 500);
        }
    }

    /**
     * تحديث عرض (للمشرفين)
     */
    public function update(Request $request, Offer $offer)
    {
        try {
            // 🔐 التحقق من صلاحية المشرف
            $user = Auth::user();
            if (!$user || !$user->is_admin) {
                Log::warning('Unauthorized admin access attempt', ['user_id' => $user?->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بهذا الإجراء'
                ], 403);
            }

            Log::info('Admin updating offer', [
                'admin_id' => $user->id,
                'offer_id' => $offer->id,
                'data' => $request->all()
            ]);

            $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'promo_code' => 'sometimes|string|unique:offers,promo_code,' . $offer->id . '|max:50',
                'discount_type' => 'sometimes|in:percentage,fixed,free_delivery',
                'discount_value' => 'sometimes|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'color' => 'nullable|string|max:7',
                'valid_until' => 'nullable|date|after:today',
                'usage_limit' => 'nullable|integer|min:1',
                'is_active' => 'sometimes|boolean'
            ]);

            $data = $request->only([
                'title', 'description', 'promo_code', 'discount_type',
                'discount_value', 'color', 'valid_until', 'usage_limit', 'is_active'
            ]);

            if (isset($data['discount_type']) && $data['discount_type'] === 'free_delivery') {
                $data['discount_value'] = 0;
            }

            if ($request->hasFile('image')) {
                if ($offer->image) {
                    Storage::disk('public')->delete($offer->image);
                }
                
                $imagePath = $request->file('image')->store('offers', 'public');
                $data['image'] = $imagePath;
            }

            $offer->update($data);

            Log::info('Successfully updated offer', [
                'admin_id' => $user->id,
                'offer_id' => $offer->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث العرض بنجاح',
                'offer' => $this->formatOfferResponse($offer->fresh())
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error updating offer', [
                'errors' => $e->errors(),
                'offer_id' => $offer->id,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error updating offer: ' . $e->getMessage(), [
                'offer_id' => $offer->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث العرض'
            ], 500);
        }
    }

    /**
     * حذف عرض (للمشرفين)
     */
    public function destroy(Offer $offer)
    {
        try {
            // 🔐 التحقق من صلاحية المشرف
            $user = Auth::user();
            if (!$user || !$user->is_admin) {
                Log::warning('Unauthorized admin access attempt', ['user_id' => $user?->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بهذا الإجراء'
                ], 403);
            }

            Log::info('Admin deleting offer', [
                'admin_id' => $user->id,
                'offer_id' => $offer->id
            ]);

            if ($offer->image) {
                Storage::disk('public')->delete($offer->image);
            }

            $offer->delete();

            Log::info('Successfully deleted offer', [
                'admin_id' => $user->id,
                'offer_id' => $offer->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العرض بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting offer: ' . $e->getMessage(), [
                'offer_id' => $offer->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف العرض'
            ], 500);
        }
    }

    /**
     * تطبيق العرض على السلة - للمستخدمين المسجلين فقط
     */
    public function applyPromoCodeInstant(Request $request)
    {
        try {
            Log::info('applyPromoCodeInstant called', $request->all());

            $request->validate([
                'promo_code' => 'required|string|max:50',
                'address_id' => 'nullable|exists:addresses,id'
            ]);

            // 🔐 التحقق من أن المستخدم مسجل دخول
            $user = Auth::user();
            if (!$user) {
                Log::warning('Unauthorized promo code application attempt');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لاستخدام كود الخصم'
                ], 401);
            }

            // 🛒 جلب عناصر السلة للمستخدم المسجل فقط
            $cartItems = $user->cartItems()
                ->with(['product' => function($query) {
                    $query->where('is_active', true);
                }])
                ->whereHas('product', function($query) {
                    $query->where('is_active', true);
                })
                ->get();

            if ($cartItems->isEmpty()) {
                Log::warning('Empty cart for promo code application', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'السلة فارغة'
                ], 400);
            }

            // 💰 حساب المجموع
            $subtotal = $this->calculateSubtotal($cartItems);

            // 🚚 حساب رسوم الشحن
            $deliveryFee = 30;
            if ($request->filled('address_id')) {
                $address = Address::find($request->address_id);
                if ($address) {
                    $deliveryFee = $this->calculateShipping($address, $subtotal);
                }
            }

            // 🎟️ جلب العرض بالبرومو كود
            $offer = Offer::byPromoCode($request->promo_code)->first();

            if (!$offer) {
                Log::warning('Invalid promo code', [
                    'user_id' => $user->id,
                    'promo_code' => $request->promo_code
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم غير صحيح'
                ], 404);
            }

            if (!$offer->is_available) {
                Log::warning('Unavailable promo code', [
                    'user_id' => $user->id,
                    'promo_code' => $request->promo_code,
                    'offer_id' => $offer->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العرض غير متاح حالياً'
                ], 400);
            }

            // 🧩 التحقق إذا استخدم الكود قبل كده
            if ($user->hasUsedPromoCode($offer->promo_code)) {
                Log::warning('User already used promo code', [
                    'user_id' => $user->id,
                    'promo_code' => $request->promo_code
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استخدمت هذا الكود من قبل'
                ], 400);
            }

            // 🧮 حساب الخصم والإجمالي
            $discountAmount = $offer->applyDiscount($subtotal, $deliveryFee);
            $discountAmount = is_numeric($discountAmount) ? $discountAmount : 0;
            $total = $subtotal + $deliveryFee - $discountAmount;

            // 💾 حفظ الكود في Cache للمستخدم المسجل فقط
            $this->storeAppliedPromoCode($user, $request->promo_code, $discountAmount);

            Log::info('Promo code applied successfully', [
                'user_id' => $user->id,
                'promo_code' => $request->promo_code,
                'discount_amount' => $discountAmount,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تطبيق الكود بنجاح',
                'data' => [
                    'discount_amount' => $discountAmount,
                    'new_total' => $total,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'summary' => [
                        'subtotal' => $subtotal,
                        'delivery_fee' => $deliveryFee,
                        'discount_amount' => $discountAmount,
                        'total' => $total
                    ],
                    'offer' => $this->formatOfferResponse($offer),
                    'promo_code' => $request->promo_code,
                    'cart_items_count' => $cartItems->count()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in applyPromoCodeInstant', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in applyPromoCodeInstant: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تطبيق الكود: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ❌ إلغاء البرومو كود - للمستخدمين المسجلين فقط
     */
    public function removePromoCode(Request $request)
    {
        try {
            // 🔐 التحقق من أن المستخدم مسجل دخول
            $user = Auth::user();
            if (!$user) {
                Log::warning('Unauthorized promo code removal attempt');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول'
                ], 401);
            }

            Log::info('Removing promo code', ['user_id' => $user->id]);

            // 🗑️ حذف الكود المخزن من Cache
            $this->forgetAppliedPromoCode($user);

            // إعادة حساب المجموع بدون خصم
            $cartItems = $user->cartItems()
                ->with(['product' => function($query) {
                    $query->where('is_active', true);
                }])
                ->whereHas('product', function($query) {
                    $query->where('is_active', true);
                })
                ->get();

            $subtotal = $this->calculateSubtotal($cartItems);
            
            $deliveryFee = 30;
            if ($request->has('address_id') && $request->address_id) {
                $address = Address::find($request->address_id);
                if ($address) {
                    $deliveryFee = $this->calculateShipping($address, $subtotal);
                }
            }

            $total = $subtotal + $deliveryFee;

            Log::info('Promo code removed successfully', [
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الكود',
                'data' => [
                    'discount_amount' => 0,
                    'new_total' => $total,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing promo code: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إلغاء الكود'
            ], 500);
        }
    }

    /**
     * تطبيق العرض على طلب موجود
     */
    public function applyToOrder(Request $request)
    {
        try {
            // 🔐 التحقق من تسجيل الدخول
            $user = Auth::user();
            if (!$user) {
                Log::warning('Unauthorized order promo application attempt');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول'
                ], 401);
            }

            Log::info('Applying promo to order', [
                'user_id' => $user->id,
                'order_id' => $request->order_id,
                'promo_code' => $request->promo_code
            ]);

            $request->validate([
                'promo_code' => 'required|string|max:50',
                'order_id' => 'required|exists:orders,id'
            ]);

            $order = Order::where('user_id', $user->id)->findOrFail($request->order_id);
            
            if ($order->status !== 'pending') {
                Log::warning('Cannot apply promo to non-pending order', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'status' => $order->status
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تطبيق الخصم على طلب تم تأكيده'
                ], 400);
            }

            $offer = Offer::byPromoCode($request->promo_code)->first();

            if (!$offer || !$offer->is_available) {
                Log::warning('Invalid promo code for order', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'promo_code' => $request->promo_code
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم غير صالح'
                ], 404);
            }

            if ($user->hasUsedPromoCode($offer->promo_code)) {
                Log::warning('User already used promo code for order', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'promo_code' => $request->promo_code
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استخدمت هذا الكود من قبل'
                ], 400);
            }

            $discountAmount = $this->applyOfferToOrder($offer, $order);

            $user->offers()->attach($offer->id, [
                'used_at' => now(),
                'order_id' => $order->id,
                'discount_amount' => $discountAmount
            ]);

            $offer->incrementUsage();

            Log::info('Successfully applied promo to order', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'offer_id' => $offer->id,
                'discount_amount' => $discountAmount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تطبيق الكود بنجاح',
                'discount_amount' => $discountAmount,
                'new_total' => $order->fresh()->total,
                'subtotal' => $order->subtotal,
                'shipping_cost' => $order->delivery_fee,
                'offer' => $this->formatOfferResponse($offer)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in applyToOrder', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in applyToOrder: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تطبيق الكود على الطلب'
            ], 500);
        }
    }

    /**
     * التحقق من صحة البرومو كود
     */
    public function validatePromoCode(Request $request)
    {
        try {
            // 🔐 التحقق من تسجيل الدخول
            $user = Auth::user();
            if (!$user) {
                Log::warning('Unauthorized promo validation attempt');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول'
                ], 401);
            }

            Log::info('Validating promo code', [
                'user_id' => $user->id,
                'promo_code' => $request->promo_code
            ]);

            $request->validate([
                'promo_code' => 'required|string|max:50'
            ]);

            $offer = Offer::byPromoCode($request->promo_code)->first();

            if (!$offer) {
                Log::warning('Invalid promo code during validation', [
                    'user_id' => $user->id,
                    'promo_code' => $request->promo_code
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم غير صحيح'
                ], 404);
            }

            if (!$offer->is_available) {
                Log::warning('Unavailable promo code during validation', [
                    'user_id' => $user->id,
                    'promo_code' => $request->promo_code,
                    'offer_id' => $offer->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العرض غير متاح حالياً'
                ], 400);
            }

            if ($user->hasUsedPromoCode($offer->promo_code)) {
                Log::warning('User already used promo code during validation', [
                    'user_id' => $user->id,
                    'promo_code' => $request->promo_code
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استخدمت هذا الكود من قبل'
                ], 400);
            }

            Log::info('Promo code validated successfully', [
                'user_id' => $user->id,
                'promo_code' => $request->promo_code,
                'offer_id' => $offer->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'الكود صالح للاستخدام',
                'offer' => $this->formatOfferResponse($offer),
                'discount_description' => $offer->discount_description
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in validatePromoCode', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in validatePromoCode: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في التحقق من الكود'
            ], 500);
        }
    }

    /**
     * ✅ التحقق من وجود كود مخزن في Cache - للمستخدمين المسجلين فقط
     */
    public function checkStoredPromoCode(Request $request)
    {
        try {
            // 🔐 التحقق من أن المستخدم مسجل دخول
            $user = Auth::user();
            if (!$user) {
                Log::warning('Unauthorized stored promo check attempt');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول'
                ], 401);
            }

            Log::info('Checking stored promo code', ['user_id' => $user->id]);

            $storedPromo = $this->getAppliedPromoCode($user);
            
            if (!$storedPromo) {
                Log::info('No stored promo code found', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد كود خصم مُطبق'
                ], 404);
            }

            $offer = Offer::byPromoCode($storedPromo['promo_code'])->first();
            
            if (!$offer || !$offer->is_available) {
                Log::warning('Stored promo code no longer valid', [
                    'user_id' => $user->id,
                    'stored_promo' => $storedPromo
                ]);
                $this->forgetAppliedPromoCode($user);
                
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم المُطبق لم يعد صالحاً'
                ], 400);
            }

            Log::info('Stored promo code found and valid', [
                'user_id' => $user->id,
                'promo_code' => $storedPromo['promo_code']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'يوجد كود خصم مُطبق',
                'data' => [
                    'promo_code' => $storedPromo['promo_code'],
                    'discount_amount' => $storedPromo['discount_amount'],
                    'offer' => $this->formatOfferResponse($offer)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking stored promo code: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في التحقق من الكود المخزن'
            ], 500);
        }
    }

    // ===== الدوال المساعدة =====

    private function applyOfferToOrder(Offer $offer, Order $order)
    {
        try {
            $subtotal = $order->subtotal ?? 0;
            $deliveryFee = $order->delivery_fee ?? 0;
            
            $discountAmount = $offer->applyDiscount($subtotal, $deliveryFee);
            $discountAmount = is_numeric($discountAmount) ? $discountAmount : 0;

            $finalTotal = $subtotal + $deliveryFee - $discountAmount;

            $order->update([
                'discount_amount' => $discountAmount,
                'total' => $finalTotal,
                'applied_offer_id' => $offer->id
            ]);

            Log::info('Offer applied to order successfully', [
                'order_id' => $order->id,
                'offer_id' => $offer->id,
                'discount_amount' => $discountAmount,
                'final_total' => $finalTotal
            ]);

            return $discountAmount;

        } catch (\Exception $e) {
            Log::error('Error applying offer to order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'offer_id' => $offer->id,
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    private function calculateShipping(Address $address, $subtotal)
    {
        try {
            if (class_exists('App\Http\Controllers\DeliveryController')) {
                $deliveryController = new \App\Http\Controllers\DeliveryController();
                
                $areaRequest = new Request([
                    'area_name' => $address->city,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'city' => $address->city,
                    'district' => $address->state
                ]);

                $areaResponse = $deliveryController->findDeliveryArea($areaRequest);
                $areaData = $areaResponse->getData();

                if ($areaData->success && !empty($areaData->data)) {
                    $deliveryArea = $areaData->data[0];
                    
                    $feeRequest = new Request([
                        'area_name' => $deliveryArea->area_name,
                        'order_amount' => $subtotal
                    ]);

                    $feeResponse = $deliveryController->calculateDeliveryFee($feeRequest);
                    $feeData = $feeResponse->getData();

                    if ($feeData->success) {
                        return $feeData->data->delivery_fee;
                    }

                    return $deliveryArea->delivery_fee ?? 30;
                }
            }
            
            return 30;
            
        } catch (\Exception $e) {
            Log::error('Error in calculateShipping: ' . $e->getMessage(), [
                'address_id' => $address->id,
                'subtotal' => $subtotal,
                'trace' => $e->getTraceAsString()
            ]);
            return 30;
        }
    }

    private function calculateSubtotal($cartItems)
    {
        try {
            $subtotal = $cartItems->sum(function ($item) {
                if (!$item->product || !$item->price) {
                    return 0;
                }
                return $item->price * $item->quantity;
            });

            Log::debug('Calculated subtotal', [
                'cart_items_count' => $cartItems->count(),
                'subtotal' => $subtotal
            ]);

            return $subtotal;

        } catch (\Exception $e) {
            Log::error('Error in calculateSubtotal: ' . $e->getMessage(), [
                'cart_items_count' => $cartItems->count(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    private function formatOfferResponse($offer)
    {
        return [
            'id' => $offer->id,
            'title' => $offer->title,
            'title_ar' => $offer->title_ar,
            'description_ar' => $offer->description_ar,


            'description' => $offer->description,
            'promo_code' => $offer->promo_code,
            'image_url' => $offer->image_url,
            'color' => $offer->color,
            'discount_type' => $offer->discount_type,
            'discount_value' => $offer->discount_value,
            'discount_description' => $offer->discount_description,
            'is_active' => $offer->is_active,
            'is_available' => $offer->is_available,
            'valid_until' => $offer->valid_until,
            'usage_limit' => $offer->usage_limit,
            'used_count' => $offer->used_count,
            'remaining_uses' => $offer->usage_limit ? $offer->usage_limit - $offer->used_count : null,
            'users_count' => $offer->users_count ?? $offer->users()->count(),
            'created_at' => $offer->created_at,
            'updated_at' => $offer->updated_at
        ];
    }

    private function generatePromoCode()
    {
        try {
            do {
                $promoCode = strtoupper(Str::random(8));
            } while (Offer::where('promo_code', $promoCode)->exists());

            Log::debug('Generated new promo code', ['promo_code' => $promoCode]);

            return $promoCode;

        } catch (\Exception $e) {
            Log::error('Error generating promo code: ' . $e->getMessage());
            return 'FALLBACK' . Str::random(6);
        }
    }

    // ===== دوال Cache للمستخدمين المسجلين فقط =====

    /**
     * 💾 تخزين الكود للمستخدمين المسجلين فقط
     */
    private function storeAppliedPromoCode($user, $promoCode, $discountAmount)
    {
        try {
            $key = "user_promo:{$user->id}";
            $data = [
                'promo_code' => $promoCode,
                'discount_amount' => $discountAmount,
                'stored_at' => now()->toISOString()
            ];

            Cache::put($key, $data, now()->addHours(2));

            Log::debug('Stored promo code in cache', [
                'user_id' => $user->id,
                'promo_code' => $promoCode,
                'key' => $key
            ]);

        } catch (\Exception $e) {
            Log::error('Error storing promo code in cache: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'promo_code' => $promoCode
            ]);
        }
    }

    /**
     * 🔍 استرجاع الكود المخزن
     */
    private function getAppliedPromoCode($user)
    {
        try {
            $key = "user_promo:{$user->id}";
            $data = Cache::get($key);

            Log::debug('Retrieved promo code from cache', [
                'user_id' => $user->id,
                'key' => $key,
                'found' => !is_null($data)
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('Error retrieving promo code from cache: ' . $e->getMessage(), [
                'user_id' => $user->id
            ]);
            return null;
        }
    }

    /**
     * 🗑️ حذف الكود المخزن
     */
    private function forgetAppliedPromoCode($user)
    {
        try {
            $key = "user_promo:{$user->id}";
            Cache::forget($key);

            Log::debug('Forgot promo code from cache', [
                'user_id' => $user->id,
                'key' => $key
            ]);

        } catch (\Exception $e) {
            Log::error('Error forgetting promo code from cache: ' . $e->getMessage(), [
                'user_id' => $user->id
            ]);
        }
    }
}