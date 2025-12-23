<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_COMPLETED = 'completed';
    const PAYMENT_STATUS_FAILED = 'failed';

    const ORDER_STATUS_PROCESSING = 'processing';
    const ORDER_STATUS_CANCELLED = 'cancelled';

    private function getAuthToken()
    {
        Log::info('Getting PayMob Auth Token', [
            'api_key' => config('services.paymob.api_key') ? 'EXISTS' : 'MISSING'
        ]);

        $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => config('services.paymob.api_key')
        ]);

        Log::info('PayMob Auth Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            Log::error('PayMob Auth Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('فشل في المصادقة مع PayMob: ' . $response->body());
        }

        return $response->json('token');
    }

    private function createPaymobOrder($token, Order $order)
    {
        $orderData = [
            'auth_token' => $token,
            'delivery_needed' => false,
            'amount_cents' => (int)($order->total * 100),
            'currency' => 'EGP',
            'merchant_order_id' => $order->id,
        ];

        Log::info('Creating PayMob Order', $orderData);

        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', $orderData);

        Log::info('PayMob Order Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            Log::error('PayMob Order Creation Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('فشل في إنشاء طلب PayMob: ' . $response->body());
        }

        return $response->json('id');
    }

    private function getPaymentKey($token, $paymobOrderId, Order $order)
    {
        $billingData = [
            'first_name' => $order->user->name ?? 'Guest',
            'last_name' => 'User',
            'email' => $order->user->email ?? 'guest@example.com',
            'phone_number' => $order->user->phone ?? $order->address->phone_number ?? '01000000000',
            'city' => $order->address->city ?? 'Cairo',
            'country' => 'EG',
            'street' => $order->address->street ?? 'N/A',
            'building' => $order->address->building_number ?? 'N/A',
            'floor' => $order->address->floor_number ?? 'N/A',
            'apartment' => $order->address->apartment_number ?? 'N/A',
            'postal_code' => $order->address->postal_code ?? '11111',
        ];

        $paymentData = [
            'auth_token' => $token,
            'amount_cents' => (int)($order->total * 100),
            'expiration' => 3600,
            'order_id' => $paymobOrderId,
            'billing_data' => $billingData,
            'currency' => 'EGP',
            'integration_id' => (int)config('services.paymob.integration_id'),
        ];

        Log::info('Getting Payment Key', [
            'payment_data' => $paymentData,
            'integration_id' => config('services.paymob.integration_id')
        ]);

        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', $paymentData);

        Log::info('PayMob Payment Key Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            Log::error('PayMob Payment Key Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('فشل في الحصول على مفتاح الدفع: ' . $response->body());
        }

        return $response->json('token');
    }

    public function initiatePayment(Request $request, Order $order)
    {
        $request->validate([
            'payment_method' => 'required|in:card,cash_on_delivery'
        ]);

        Log::info('Initiating Payment', [
            'order_id' => $order->id,
            'payment_method' => $request->payment_method,
            'order_total' => $order->total
        ]);

        // إنشاء سجل الدفع
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $request->payment_method,
            'amount' => $order->total,
            'status' => self::PAYMENT_STATUS_PENDING,
        ]);

        $order->payment_id = $payment->id;
        $order->save();

        // الدفع عند الاستلام
        if ($request->payment_method === 'cash_on_delivery') {
            $payment->update([
                'transaction_id' => 'COD-' . Str::uuid(),
                'status' => self::PAYMENT_STATUS_COMPLETED
            ]);

            $order->update(['status' => self::ORDER_STATUS_PROCESSING]);

            OrderTracking::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_PROCESSING,
                'notes' => 'تم تأكيد الطلب - الدفع عند التسليم'
            ]);

            return response()->json([
                'status' => 'completed',
                'method' => 'cash_on_delivery',
                'message' => 'تم تأكيد الطلب - الدفع عند التسليم'
            ]);
        }

        // الدفع الإلكتروني
        try {
            // التحقق من الـ Config
            if (!config('services.paymob.api_key')) {
                throw new \Exception('PAYMOB_API_KEY مفقود في ملف .env');
            }

            if (!config('services.paymob.integration_id')) {
                throw new \Exception('PAYMOB_CARD_INTEGRATION_ID مفقود في ملف .env');
            }

            $token = $this->getAuthToken();
            Log::info('Got Auth Token', ['token_length' => strlen($token)]);

            $paymobOrderId = $this->createPaymobOrder($token, $order);
            Log::info('Created PayMob Order', ['paymob_order_id' => $paymobOrderId]);

            $paymentKey = $this->getPaymentKey($token, $paymobOrderId, $order);
            Log::info('Got Payment Key', ['payment_key_length' => strlen($paymentKey)]);

            $payment->update([
                'transaction_id' => 'PMB-' . $paymobOrderId,
                'payment_details' => json_encode(['payment_key' => $paymentKey])
            ]);

            $paymentUrl = 'https://accept.paymob.com/api/acceptance/iframes/912193?payment_token=' . $paymentKey;

            Log::info('Payment URL Generated', ['url' => $paymentUrl]);

            return response()->json([
                'status' => 'pending',
                'method' => 'card',
                'payment_url' => $paymentUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Paymob Error Details', [
                'order_id' => $order->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update payment status to failed
            $payment->update(['status' => self::PAYMENT_STATUS_FAILED]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();
        Log::info('Paymob Webhook Received', $data);

        // التحقق من HMAC
        $hmac = $request->header('x-hmac');
        $calculatedHmac = hash_hmac('sha512', json_encode($data), config('services.paymob.hmac_secret'));

        if (!hash_equals($hmac ?? '', $calculatedHmac)) {
            Log::error('Invalid HMAC', [
                'received_hmac' => $hmac,
                'calculated_hmac' => $calculatedHmac
            ]);
            return response()->json(['error' => 'Invalid HMAC'], 403);
        }

        // معالجة الدفع الناجح
        if (isset($data['obj']) && $data['obj']['success'] === true) {
            $merchantOrderId = $data['obj']['order']['merchant_order_id'] ?? null;

            if (!$merchantOrderId) {
                Log::error('Merchant order ID missing in webhook', $data);
                return response()->json(['error' => 'Merchant order ID missing'], 400);
            }

            $order = Order::find($merchantOrderId);

            if (!$order) {
                Log::error('Order not found', ['merchant_order_id' => $merchantOrderId]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            $payment = Payment::where('order_id', $order->id)->first();

            if ($payment) {
                $payment->update([
                    'transaction_id' => $data['obj']['id'] ?? 'unknown',
                    'status' => self::PAYMENT_STATUS_COMPLETED,
                    'payment_details' => json_encode($data['obj'])
                ]);
            }

            $order->update(['status' => self::ORDER_STATUS_PROCESSING]);

            OrderTracking::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_PROCESSING,
                'notes' => 'تم الدفع بنجاح'
            ]);

            Log::info('Payment succeeded', [
                'order_id' => $order->id,
                'payment_id' => $payment->id ?? 'unknown',
                'transaction_id' => $data['obj']['id'] ?? 'unknown'
            ]);

            return response()->json(['success' => true]);
        }

        // معالجة الدفع الفاشل
        if (isset($data['obj'])) {
            $merchantOrderId = $data['obj']['order']['merchant_order_id'] ?? null;

            if ($merchantOrderId) {
                $order = Order::find($merchantOrderId);
                if ($order) {
                    $payment = Payment::where('order_id', $order->id)->first();
                    if ($payment) {
                        $payment->update(['status' => self::PAYMENT_STATUS_FAILED]);
                    }
                }
            }
        }

        Log::error('Payment failed', $data);
        return response()->json(['error' => 'Payment failed'], 400);
    }

    private function ipInRange($ip, $range)
    {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }

        list($range, $netmask) = explode('/', $range, 2);
        $ipDecimal = ip2long($ip);
        $rangeDecimal = ip2long($range);
        $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
        $netmaskDecimal = ~ $wildcardDecimal;

        return (($ipDecimal & $netmaskDecimal) === ($rangeDecimal & $netmaskDecimal));
    }
}
