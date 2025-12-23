<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileapiController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\OfferController;

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ConversationController;



///////////////////////////////////////////////////////
//  Public Routes
///////////////////////////////////////////////////////

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/reset-password', [AuthController::class, 'sendResetLink']);
    Route::post('/update-password', [AuthController::class, 'resetPassword']);
    route::delete('/logout',[AuthController::class,'logout']);
});

Route::post('/forgot-password-otp', [AuthController::class, 'sendResetOtp']);
// إعادة تعيين كلمة المرور باستخدام OTP (منفصل)
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/update-password-after-verification', [AuthController::class, 'updatePasswordAfterVerification']);
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ?home screen-----------------
// ?Categories
Route::get('/categorie', [CategoryController::class, 'index']);
// ?العناصر الشائعة
Route::get('/products/top', [ProductController::class, 'topProducts']);
// ?البحث في الفئات
Route::get('/categories/search', [CategoryController::class, 'search']);
Route::get('/product/filters', [CategoryController::class, 'applyFilters']);
Route::get('/products/filter', [ProductController::class, 'applyFilters']);

// product by catogry
Route::get('/categories/{category}', [ProductController::class, 'productsByCategory']);  // {category-id}
// الحصول على جميع الفئات مع منتجاتها
Route::get('/categories-with-products', [CategoryController::class, 'categoriesWithProducts']);

// Products
Route::prefix('products')->group(function () {
    Route::get('/product/search', [ProductController::class, 'search']);
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{product}', [ProductController::class, 'show']);//1 in product details screen
    Route::get('/categories/{category}', [ProductController::class, 'productsByCategory']);
    Route::get('/variants/{variant}', [ProductController::class, 'getVariant']);
});

// Categories
// Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'getFavorites']);
    Route::get('/favorites/products', [FavoriteController::class, 'getFavoriteProducts']);
    Route::post('/favorites/{product}', [FavoriteController::class, 'toggleFavorite']);//2 in product details screen
    Route::get('top-favorites', [FavoriteController::class, 'getTopFavorites']);

    
});





// Cart (for guests and logged-in users)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'addToCart']);//3 in product details screen
    Route::put('/cart/{id}', [CartController::class, 'updateQuantity']);//in cart
    Route::delete('/remove/{cartItem}', [CartController::class, 'removeItem']);//in cart
    Route::post('/transfer', [CartController::class, 'transferGuestCart']);
});

///////////////////////////////////////////////////////
//  Authenticated User Routes
///////////////////////////////////////////////////////

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::delete('/auth/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileapiController::class, 'show']);
    Route::put('/profile', [ProfileapiController::class, 'update']);

    // Addresses
    Route::get('/addresses', [AddressController::class, 'index']);// saved addresses
    Route::post('/addresses', [AddressController::class, 'store']);//in location
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::post('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);
    Route::get('/addresses/{id}', [AddressController::class, 'show']);

    // Orders
    Route::post('/checkout', [OrderController::class, 'checkout']);// to make order
    Route::get('/orders/{order}/track', [OrderController::class, 'trackOrder']);
    Route::get('/user/orders', [OrderController::class, 'getUserOrders']);//my order
    Route::get('/orders/upcoming', [OrderController::class, 'getUpcomingOrders']);


    // Payment
    Route::post('/orders/{order}/pay', [PaymentController::class, 'initiatePayment']);
});

///////////////////////////////////////////////////////
//  Admin Routes
///////////////////////////////////////////////////////

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Categories
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Orders
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});

///////////////////////////////////////////////////////
//  Webhooks and Public Tracking
///////////////////////////////////////////////////////

// Payment Webhook
Route::prefix('payments')->group(function () {
    Route::post('/webhook', [PaymentController::class, 'handleWebhook']);
});

// Public Tracking
Route::get('/tracking/{order}/{hash}', [OrderController::class, 'publicTracking'])->name('order.tracking');

///////////////////////////////////////////////////////
//  Test Route
///////////////////////////////////////////////////////

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// //////////////////////////////////////////////////
// OTP Routes
Route::post('send-login-otp', [AuthController::class, 'sendLoginOtp']);
Route::post('login-with-otp', [AuthController::class, 'loginWithOtp']);

// تغيير كلمة المرور (تتطلب تسجيل دخول)
Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
use App\Http\Controllers\NotificationController;

// تسجيل وإدارة أجهزة المستخدم
Route::prefix('notifications')->group(function () {
    Route::post('register-token', [NotificationController::class, 'registerToken'])->middleware('auth:sanctum');
    Route::post('remove-token', [NotificationController::class, 'removeToken'])->middleware('auth:sanctum');
    Route::get('test', [NotificationController::class, 'testNotification'])->middleware('auth:sanctum');
});
/////////////////////////////////////////////////////////
// تقييمات المنتج (مع pagination)
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);

// جميع تقييمات المنتج (بدون pagination)
Route::get('/products/{productId}/reviews/all', [ReviewController::class, 'getAllReviews']);

// إنشاء تقييم جديد
Route::post('/products/{product}/addreviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');

// عرض تقييم محدد
Route::get('/review/{review}', [ReviewController::class, 'show']);

// تحديث التقييم
Route::put('/reviews/{review}', [ReviewController::class, 'update'])->middleware('auth:sanctum');

// حذف التقييم
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->middleware('auth:sanctum');

// تقييمات المستخدم الحالي
Route::get('/user/reviews', [ReviewController::class, 'userReviews'])->middleware('auth:sanctum');
Route::get('top-rated-products', [ReviewController::class, 'getTopRatedProducts']);


// إحصائيات التقييمات
Route::get('/products/{product}/reviews/stats', [ReviewController::class, 'stats']);
/////////////////////////////////////////////////////////////////
Route::group(['prefix' => 'offers'], function () {
    // Public routes
    Route::get('/', [OfferController::class, 'index']);
    Route::get('/promo-code/{promoCode}', [OfferController::class, 'findByPromoCode']);
    Route::get('/{offer}', [OfferController::class, 'show']);

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/validate', [OfferController::class, 'validatePromoCode']);
        Route::post('/apply', [OfferController::class, 'applyToOrder']);
        Route::get('/my/offers', [OfferController::class, 'myOffers']);
        Route::post('/offers/apply-instant', [OfferController::class, 'applyPromoCodeInstant']);

    });

    // Admin routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [OfferController::class, 'store']);
        Route::put('/{offer}', [OfferController::class, 'update']);
        Route::delete('/{offer}', [OfferController::class, 'destroy']);
    });
});
// ////////////////////////////
Route::group(['prefix' => 'delivery'], function () {
    Route::post('/find-area', [DeliveryController::class, 'findDeliveryArea']);
    Route::get('/branch/{branchId}/areas', [DeliveryController::class, 'getBranchAreas']);
    Route::get('/branch/{branchId}/areas-by-fee', [DeliveryController::class, 'getAreasByFee']);
    
    // الفروع
    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches/find-for-location', [BranchController::class, 'findBranchesForLocation']);
});

// routes للمشرفين
Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {
    Route::apiResource('branches', BranchController::class);
    Route::post('branches/{branch}/delivery-areas', [BranchController::class, 'addDeliveryArea']);
    Route::put('delivery-areas/{deliveryArea}', [BranchController::class, 'updateDeliveryArea']);
    Route::delete('delivery-areas/{deliveryArea}', [BranchController::class, 'deleteDeliveryArea']);
});
// ??????????//
 Route::post('/conversation/store', [ConversationController::class, 'storeMessageByUserId']);
Route::get('/conversation/user/{userId}', [ConversationController::class, 'getUserMessages']);
Route::get('/conversation/all', [ConversationController::class, 'getAllUserConversations']);

// روت اختبار شامل (اختياري)
Route::get('test-fcm', function (App\Services\FcmService $fcmService) {
    try {
        // اختبار إرسال لجهاز واحد
        $token = App\Models\DeviceToken::first()->token;
        $singleResult = $fcmService->sendToToken(
            $token,
            'إختبار إرسال لجهاز واحد',
            'هذا إشعار تجريبي لجهاز محدد'
        );

        // اختبار إرسال لمستخدم
        $user = App\Models\User::find(1);
        $userResult = $fcmService->sendToUser(
            $user,
            'إختبار إرسال لمستخدم',
            'مرحباً ' . $user->name . '! هذا إشعار تجريبي'
        );

        // اختبار إرسال عبر نظام الإشعارات
        $notificationData = [
            'title' => 'إختبار نظام الإشعارات',
            'body' => 'هذا إشعار باستخدام نظام الإشعارات المدمج في Laravel',
            'data' => ['action' => 'open_profile']
        ];

        $user->notify(new App\Notifications\FcmNotification(
            $notificationData['title'],
            $notificationData['body'],
            $notificationData['data']
        ));

        return response()->json([
            'success' => true,
            'single_device' => $singleResult ? 'تم الإرسال' : 'فشل الإرسال',
            'user_notification' => $userResult,
            'laravel_notification' => 'تم الإرسال'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
    
Route::get('/orders/shipped-products', [OrderController::class, 'getShippedProducts']);


})->middleware('auth:sanctum');

