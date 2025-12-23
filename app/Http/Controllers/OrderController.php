<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTracking;
use App\Models\Address;
use App\Models\Payment;
use App\Models\Offer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Session;
class OrderController extends Controller
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'in process';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    public function checkout(Request $request)
{
    return DB::transaction(function () use ($request) {

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = Branch::active()->find($request->branch_id);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'الفرع غير موجود أو غير مفعل'
            ], 400);
        }

        $cartItems = $this->getCartItems($request);

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'السلة فارغة'
            ], 400);
        }

        foreach ($cartItems as $cartItem) {
            if ($cartItem->product->stock < $cartItem->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متوفرة للمنتج: ' . $cartItem->product->name
                ], 400);
            }
        }

        $address = $this->processAddress($request);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'العنوان غير صالح'
            ], 400);
        }

        $subtotal = $this->calculateSubtotal($cartItems);
        $deliveryFee = $branch->delivery_fee_base;
        $paymentMethod = $request->input('payment_method', 'cash');

        $discountAmount = 0;
        $appliedOffer = null;

        $promoCode = Session::get('applied_promo_code') ?? $request->input('promo_code');
        if ($promoCode) {
            $discountResult = $this->applyPromoCodeAtCheckout($promoCode, $subtotal, $deliveryFee, Auth::user());
            $discountAmount = $discountResult['discount_amount'];
            $appliedOffer = $discountResult['offer'];

            Session::forget('applied_promo_code');
            Session::forget('promo_discount_amount');
        }

        $total = $subtotal + $deliveryFee - $discountAmount;

        $orderData = [
            'user_id' => Auth::id(),
            'guest_uuid' => Auth::check() ? null : $this->getGuestUuid($request),
            'subtotal' => $subtotal,
            'total' => $total,
            'delivery_fee' => $deliveryFee,
            'status' => self::STATUS_PENDING,
            'address_id' => $address->id,
            'tracking_number' => $this->generateTrackingNumber(),
            'payment_method' => $paymentMethod,
            'branch_id' => $branch->id,
        ];

        if (\Schema::hasColumn('orders', 'discount_amount')) {
            $orderData['discount_amount'] = $discountAmount;
        }

        if (\Schema::hasColumn('orders', 'applied_offer_id') && $appliedOffer) {
            $orderData['applied_offer_id'] = $appliedOffer->id;
        }

        $order = Order::create($orderData);

        foreach ($cartItems as $cartItem) {
            $selectedOptions = [];

            if (method_exists($cartItem, 'optionValues') && $cartItem->optionValues->isNotEmpty()) {
                $selectedOptions = $cartItem->optionValues->map(function ($optionValue) {
                    return [
                        'option_id' => $optionValue->option->id,
                        'option_name' => $optionValue->option->name,
                        'value_id' => $optionValue->id,
                        'value' => $optionValue->value,
                        'price_modifier' => $optionValue->price_modifier
                    ];
                })->toArray();
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'options' => json_encode($selectedOptions),
            ]);

            $cartItem->product->decrement('stock', $cartItem->quantity);
        }


        if ($appliedOffer && Auth::check()) {
            Auth::user()->offers()->attach($appliedOffer->id, [
                'used_at' => now(),
                'order_id' => $order->id,
                'discount_amount' => $discountAmount
            ]);

            $appliedOffer->increment('used_count');
        }


        $paymentResponse = $this->initiatePayment($order, $paymentMethod);


        $this->clearCart($request);


        OrderTracking::create([
            'order_id' => $order->id,
            'status' => self::STATUS_PENDING,
            'notes' => 'تم إنشاء الطلب'
        ]);


        $order->load('orderItems.product', 'address', 'branch');


        $response = [
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => [
                'order' => $order,
                'tracking_link' => $this->generateTrackingLink($order),
                'payment' => $paymentResponse,
                'summary' => [
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'discount_amount' => $discountAmount,
                    'total' => $total
                ],
                'branch' => $order->branch
            ]
        ];

        if ($appliedOffer) {
            $response['data']['applied_offer'] = $this->formatOfferResponse($appliedOffer);
        }

        return response()->json($response);
    });
}


    private function applyPromoCodeAtCheckout($promoCode, $subtotal, $deliveryFee, $user)
    {
        try {
            $offer = Offer::byPromoCode($promoCode)->first();

            if (!$offer) {
                return [
                    'discount_amount' => 0,
                    'offer' => null
                ];
            }


            if (!$offer->is_available) {
                return [
                    'discount_amount' => 0,
                    'offer' => null
                ];
            }
            if ($user && $user->hasUsedPromoCode($offer->promo_code)) {
                return [
                    'discount_amount' => 0,
                    'offer' => null
                ];
            }


            $discountAmount = $offer->applyDiscount($subtotal, $deliveryFee);
            $discountAmount = is_numeric($discountAmount) ? $discountAmount : 0;

            return [
                'discount_amount' => $discountAmount,
                'offer' => $offer
            ];

        } catch (\Exception $e) {
            Log::error('Error applying promo code at checkout: ' . $e->getMessage());
            return [
                'discount_amount' => 0,
                'offer' => null
            ];
        }
    }


    private function formatOfferResponse($offer)
    {
        return [
            'id' => $offer->id,
            'title' => $offer->title,
            'description' => $offer->description,
            'promo_code' => $offer->promo_code,
            'discount_type' => $offer->discount_type,
            'discount_value' => $offer->discount_value,
            'discount_description' => $offer->discount_description,
        ];
    }


      private function initiatePayment(Order $order, $paymentMethod = 'cash')
    {
        switch ($paymentMethod) {
            case 'card':
                return $this->processCardPayment($order);
            case 'bank_transfer':
                return $this->processBankTransfer($order);
            case 'cash':
            default:
                return $this->processCashOnDelivery($order);
        }
    }

    private function processCashOnDelivery(Order $order)
    {
        try {
            $transactionId = 'CASH-' . now()->format('YmdHis') . '-' . $order->id;

            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => 'cash',
                'status' => 'pending',
                'transaction_id' => $transactionId,
                'payment_details' => json_encode([
                    'method' => 'cash_on_delivery',
                    'notes' => 'الدفع نقداً عند الاستلام'
                ])
            ]);

            return [
                'status' => 'pending',
                'message' => 'سيتم الدفع نقداً عند الاستلام',
                'payment_method' => 'cash',
                'requires_action' => false,
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create payment record: ' . $e->getMessage());

            return [
                'status' => 'pending',
                'message' => 'سيتم الدفع نقداً عند الاستلام',
                'payment_method' => 'cash',
                'requires_action' => false,
                'payment_id' => null,
                'warning' => 'تم إنشاء الطلب ولكن هناك مشكلة في سجل الدفع'
            ];
        }
    }



    private function processCardPayment(Order $order)
    {
        $payment = \App\Models\Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total + $order->shipping_cost,
            'payment_method' => 'card',
            'status' => 'pending',

        ]);

        $paymentController = new PaymentController();
        return $paymentController->initiatePayment($request, $order);
    }

    private function processBankTransfer(Order $order)
    {
        $payment = \App\Models\Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total + $order->shipping_cost,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'payment_details' => json_encode([
                'method' => 'bank_transfer',
                'notes' => 'في انتظار التحويل البنكي'
            ])
        ]);

        return [
            'status' => 'pending',
            'message' => 'يرجى إتمام التحويل البنكي',
            'payment_method' => 'bank_transfer',
            'bank_account' => [
                'bank_name' => 'Bank Name',
                'account_number' => '1234567890',
                'account_name' => 'Your Company Name'
            ],
            'requires_action' => true,
            'payment_id' => $payment->id
        ];
    }

     public function trackOrder($orderId)
{
    try {
        $order = Order::with(['orderItems.product', 'payment', 'trackingHistory', 'address'])
                    ->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود'
            ], 404);
        }

        if (Auth::check() && Auth::id() !== $order->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول لهذا الطلب'
            ], 403);
        }

        $items = $order->orderItems->map(function ($item) {
            $product = $item->product;

            if ($product) {
                if (is_array($product->images) && !empty($product->images)) {
                    $imagePath = $product->images[0];
                }
                elseif (!empty($product->image)) {
                    $imagePath = $product->image;
                }
            }

            $imageUrl = $imagePath
                ? asset('storage/' . ltrim($imagePath, '/'))
                : asset('images/default-product.png');

            return [
                'id' => $item->id,
                'product_id' => $product->id ?? null,
                'name' => $product->name ?? 'منتج محذوف',
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->price ?? 0,
                'total_price' => ($item->price ?? 0) * ($item->quantity ?? 1),
                'image' => $imageUrl,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'current_status' => $order->status,
                'tracking_number' => $order->tracking_number,
                'tracking_link' => $this->generateTrackingLink($order),
                'last_updated' => $order->updated_at,
                'tracking_history' => $order->trackingHistory,
                'order_details' => [
                    'total' => $order->total,
                    'delivery_fee' => $order->delivery_fee,
                    'shipping_cost' => $order->delivery_fee,
                    'items' => $items,
                    'payment_status' => $order->payment->status ?? 'unknown',
                    'address' => $order->address,
                ]
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Order tracking error: ' . $e->getMessage(), [
            'order_id' => $orderId,
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تتبع الطلب، برجاء المحاولة لاحقًا.'
        ], 500);
    }
}

    public function getUpcomingOrders(Request $request)
{
    try {
        $user = Auth::user();

        $upcomingStatuses = [

            self::STATUS_SHIPPED
        ];

        $query = Order::whereIn('status', $upcomingStatuses);

        if (Auth::check()) {
            $query->where('user_id', $user->id);
        }

        $orders = $query->with([
            'orderItems.product',
            'payment',
            'address',
            'trackingHistory' => function ($query) {
                $query->latest();
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        $orders->transform(function ($order) {
            $order->status_label = $this->getStatusLabel($order->status);
            $order->is_upcoming = true;

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الطلبات القادمة بنجاح',
            'data' => [
                'orders' => $orders,
                'summary' => [
                    'total_upcoming' => $orders->count(),
                    'pending_count' => $orders->where('status', self::STATUS_PENDING)->count(),
                    'processing_count' => $orders->where('status', self::STATUS_PROCESSING)->count(),
                    'shipped_count' => $orders->where('status', self::STATUS_SHIPPED)->count()
                ]
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getUpcomingOrders: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ في جلب الطلبات القادمة'
        ], 500);
    }
}
    private function getStatusLabel($status)
    {
        $labels = [
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_PROCESSING => 'قيد المعالجة',
            self::STATUS_SHIPPED => 'تم الشحن',
            self::STATUS_DELIVERED => 'تم التسليم',
            self::STATUS_CANCELLED => 'ملغي'
        ];

        return $labels[$status] ?? $status;
    }


     public function getUserOrders(Request $request)
    {
        $query = Order::query();

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        $orders = $query->with([
            'orderItems.variant.product',
            'payment',
            'trackingHistory' => function ($query) {
                $query->latest();
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        $orders->transform(function ($order) {

            if ($order->status === self::STATUS_DELIVERED) {
                $deliveredEvent = $order->trackingHistory
                    ->where('status', self::STATUS_DELIVERED)
                    ->first();

            }



            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }



    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
            'carrier' => 'nullable|string|max:50'
        ]);

        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        $order->update(['status' => $request->status]);

        $tracking = OrderTracking::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'notes' => $request->notes ?? 'تحديث حالة الطلب',
            'carrier' => $request->carrier
        ]);

        if ($request->tracking_number) {
            $order->update(['tracking_number' => $request->tracking_number]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => [
                'new_status' => $order->status,
                'tracking_number' => $order->tracking_number,
                'tracking_link' => $this->generateTrackingLink($order),
                'tracking_record' => $tracking
            ]
        ]);
    }



    private function processAddress(Request $request)
    {
        if (Auth::check()) {
            return $this->handleAuthenticatedUserAddress($request);
        }

        return $this->handleGuestAddress($request);
    }

    private function handleAuthenticatedUserAddress(Request $request)
    {
        if ($request->has('address_id')) {
            $address = Address::where('user_id', Auth::id())
                            ->find($request->address_id);

            if ($address) return $address;
        }

        if ($request->has('street')) {
            return $this->createNewAddressForUser($request);
        }

        return $this->getDefaultUserAddress();
    }

    private function handleGuestAddress(Request $request)
    {
        $validated = $request->validate([
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'building_number' => 'required|string|max:20',
            'phone_number' => 'required|string|max:20',
            'apartment_number' => 'nullable|string|max:20',
            'floor_number' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        return Address::create($validated);
    }

    private function createNewAddressForUser(Request $request)
    {
        $validated = $request->validate([
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'building_number' => 'required|string|max:20',
            'phone_number' => 'required|string|max:20',
            'apartment_number' => 'nullable|string|max:20',
            'floor_number' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        $address = Address::create(array_merge($validated, ['user_id' => Auth::id()]));

        if ($request->is_default) {
            Address::where('user_id', Auth::id())
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        return $address;
    }

    private function getDefaultUserAddress()
    {
        $address = Address::where('user_id', Auth::id())
                        ->where('is_default', true)
                        ->first();

        if (!$address) {
            $address = Address::where('user_id', Auth::id())->first();
        }

        return $address;
    }

    private function calculateShipping(Address $address, $subtotal)
    {
        try {
            $deliveryController = new DeliveryController();

            $areaRequest = new Request([
                'area_name' => $address->city,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'city' => $address->city,
                'district' => $address->state
            ]);

            $areaResponse = $deliveryController->findDeliveryArea($areaRequest);
            $areaData = $areaResponse->getData();

            if (!$areaData->success || empty($areaData->data)) {
                Log::warning('No delivery area found', ['address' => $address->toArray()]);
                return 30;
            }

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

            Log::warning('Failed to calculate delivery fee', ['area' => $deliveryArea->area_name]);
            return $deliveryArea->delivery_fee ?? 30;

        } catch (\Exception $e) {
            Log::error('Delivery calculation error: ' . $e->getMessage());
            return 30;
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles * 1.609344;}

    private function getShippingCostByDistance($distance)
    {
        if ($distance < 5) return 15;
        if ($distance < 10) return 20;
        if ($distance < 20) return 30;
        if ($distance < 50) return 50;
        if ($distance < 100) return 80;
        return 120;
    }

    private function generateTrackingNumber() {
        return 'TRK-' . Str::uuid();
    }

    private function generateTrackingLink(Order $order)
    {
        $trackingHash = md5($order->id . $order->created_at . config('app.key'));
        return url("/orders/{$order->id}/track/{$trackingHash}");
    }

     private function getCartItems(Request $request)
    {
        if (Auth::check()) {
            return Auth::user()->cartItems()
                ->with(['product' => function($query) {
                    $query->where('is_active', true);
                }, 'optionValues.option'])
                ->whereHas('product', function($query) {
                    $query->where('is_active', true);
                })
                ->get();
        }

        $guestUuid = $this->getGuestUuid($request);
        return $guestUuid ?
            Cart::with(['product' => function($query) {
                    $query->where('is_active', true);
                }, 'optionValues.option'])
                ->whereHas('product', function($query) {
                    $query->where('is_active', true);
                })
                ->where('guest_uuid', $guestUuid)
                ->get() :
            collect();
    }

    private function validateStock($cartItems)
    {
        foreach ($cartItems as $item) {
            if ($item->variant->stock < $item->quantity) {
                $productName = $item->variant->product->name;
                $variantOptions = $item->variant->optionValues->pluck('value')->implode(', ');

                return response()->json([
                    'success' => false,
                    'message' => "الكمية غير متوفرة للمنتج: {$productName} ({$variantOptions})",
                    'data' => [
                        'variant_id' => $item->variant_id,
                        'available_stock' => $item->variant->stock
                    ]
                ], 422);
            }
        }
        return true;
    }

   private function calculateTotal($cartItems)
    {
        return $cartItems->sum(function ($item) {
            if (!$item->product || !$item->price) {
                Log::warning('Cart item missing product or price', [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'price' => $item->price
                ]);
                return 0;
            }

            return $item->price * $item->quantity;
        });
    }

    private function getGuestUuid(Request $request)
    {
        return $request->cookie('guest_uuid')
            ?? $request->input('guest_uuid')
            ?? $request->header('X-Guest-Uuid');
    }

    private function clearCart(Request $request)
    {
        if (Auth::check()) {
            Cart::where('user_id', Auth::id())->delete();
        } else {
            $guestUuid = $this->getGuestUuid($request);
            if ($guestUuid) {
                Cart::where('guest_uuid', $guestUuid)->delete();
            }
        }
    }
     private function calculateSubtotal($cartItems)
        {
            return $cartItems->sum(function ($item) {
                if (!$item->product || !$item->price) {
                    Log::warning('Cart item missing product or price', [
                        'cart_id' => $item->id,
                        'product_id' => $item->product_id,
                        'price' => $item->price
                    ]);
                    return 0;
                }
                return $item->price * $item->quantity;
            });
        }

    private function calculateFinalTotal($subtotal, $shippingCost, $discountAmount)
    {
        return $subtotal + $shippingCost - $discountAmount;
    }

    public function checkDeliveryAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'nullable|exists:addresses,id',
            'street' => 'required_without:address_id|string|max:255',
            'city' => 'required_without:address_id|string|max:100',
            'state' => 'required_without:address_id|string|max:100',
            'country' => 'required_without:address_id|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $address = $this->processAddress($request);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'العنوان غير صالح'
            ], 400);
        }

        $cartItems = $this->getCartItems($request);
        $subtotal = $this->calculateSubtotal($cartItems);

        $deliveryController = new DeliveryController();
        $areaRequest = new Request([
            'area_name' => $address->city,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'city' => $address->city,
            'district' => $address->state
        ]);

        $areaResponse = $deliveryController->findDeliveryArea($areaRequest);
        $areaData = $areaResponse->getData();

        if (!$areaData->success || empty($areaData->data)) {
            return response()->json([
                'success' => false,
                'message' => 'عفواً، لا يوجد توصيل لهذه المنطقة',
                'delivery_available' => false
            ]);
        }

        $deliveryArea = $areaData->data[0];
        $feeRequest = new Request([
            'area_name' => $deliveryArea->area_name,
            'order_amount' => $subtotal
        ]);

        $feeResponse = $deliveryController->calculateDeliveryFee($feeRequest);
        $feeData = $feeResponse->getData();

        return response()->json([
            'success' => true,
            'delivery_available' => true,
            'data' => [
                'delivery_area' => $deliveryArea,
                'delivery_fee' => $feeData->success ? $feeData->data->delivery_fee : $deliveryArea->delivery_fee,
                'estimated_delivery_time' => $deliveryArea->estimated_delivery_time,
                'min_order_amount' => $deliveryArea->min_order_amount,
                'current_order_amount' => $subtotal,
                'meets_minimum' => $subtotal >= $deliveryArea->min_order_amount
            ]
        ]);
    }
}
