<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Favorite;
use App\Models\Cart;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    public function register(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', PasswordRule::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    // تسجيل الدخول
    public function login(Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (!$user) {
        return response()->json(['message' => 'Email not found. Please register or try another email'], 401);
    }

    if (!Hash::check($credentials['password'], $user->password)) {
        return response()->json(['message' => 'Password is incorrect'], 401);
    }

    Auth::login($user);

    $token = $user->createToken('auth_token')->plainTextToken;

    $sessionFavorites = $request->session()->get('favorites', []);
    if (!empty($sessionFavorites)) {
        $user->favorites()->syncWithoutDetaching($sessionFavorites);
        session()->forget('favorites');
    }

    $guestUuid = $request->cookie('guest_uuid');
    if ($guestUuid) {
        Favorite::where('guest_uuid', $guestUuid)
                ->update([
                    'user_id' => $user->id,
                    'guest_uuid' => null
                ]);
    }

    if ($guestUuid) {
        Cart::where('guest_uuid', $guestUuid)
            ->update([
                'user_id' => $user->id,
                'guest_uuid' => null
            ]);
    }

    return response()->json([
        'user' => $user,
        'token' => $token
    ])->withoutCookie('guest_uuid');
}


    public function logout(Request $request) {
        $request->user()->tokens()->delete();

        $token = auth()->user()->currentAccessToken();

     if ($token && method_exists($token, 'delete')) {
        $token->delete();
        }
        return response()->json(['massage'=>'you logout come back']);

    }

    // إرسال رابط إعادة تعيين كلمة المرور
   public function sendResetLink(Request $request) {
    $request->validate(['email' => 'required|email']);

    // التحقق من وجود المستخدم أولاً
    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'لا يوجد مستخدم مسجل بهذا البريد الإلكتروني.'], 404);
    }

    try {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'تم إرسال رابط إعادة التعيين إلى بريدك الإلكتروني.']);
        } else {
            // تسجيل الخطأ بالتفصيل
            \Log::error('Password reset error: ' . $status);
            return response()->json(['message' => 'حدث خطأ أثناء إرسال الرابط. يرجى المحاولة مرة أخرى لاحقًا.'], 500);
        }
    } catch (\Exception $e) {
        // تسجيل الاستثناء بالكامل
        \Log::error('Password reset exception: ' . $e->getMessage());
        return response()->json(['message' => 'حدث خطأ في النظام: ' . $e->getMessage()], 500);
    }
}

    // تحديث كلمة المرور بعد إعادة التعيين
    public function resetPassword(Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'تم تحديث كلمة المرور'])
            : response()->json(['message' => 'فشل التحديث'], 500);
    }

    // ؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟؟

    public function sendLoginOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // توليد كود OTP (6 أرقام)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10); // صلاحية 10 دقائق

        // تخزين OTP في الكاش مع البريد الإلكتروني
        Cache::put('otp_'.$request->email, [
            'otp' => $otp,
            'expires_at' => $expiresAt
        ], $expiresAt);

        // إرسال البريد الإلكتروني
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني'
        ]);
    }

    /**
     * تسجيل الدخول باستخدام OTP
     */
    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        // التحقق من صحة OTP
        $cachedOtp = Cache::get('otp_'.$request->email);

        if (!$cachedOtp || $cachedOtp['otp'] !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية'
            ], 401);
        }

        // جلب المستخدم
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        // تسجيل دخول المستخدم
        Auth::login($user);

        // إنشاء التوكن
        $token = $user->createToken('auth_token')->plainTextToken;

        // دمج بيانات الضيف مع حساب المستخدم
        $this->mergeGuestData($request);

        // حذف OTP من الكاش بعد استخدامه
        Cache::forget('otp_'.$request->email);

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token
        ])->withoutCookie('guest_uuid');
    }

    /**
     * تغيير كلمة المرور (للمستخدم المسجل)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', PasswordRule::defaults()]
        ]);

        $user = $request->user();

        // التحقق من كلمة المرور الحالية
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 401);
        }

        // تحديث كلمة المرور
        $user->password = Hash::make($request->new_password);
        $user->save();

        // إرسال إشعار بتغيير كلمة المرور
        event(new PasswordReset($user));

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }

    /**
     * دمج بيانات الضيف مع حساب المستخدم (دالة مساعدة)
     */
    private function mergeGuestData(Request $request)
    {
        $user = Auth::user();

        // دمج المفضلة من الجلسة
        $sessionFavorites = $request->session()->get('favorites', []);
        if (!empty($sessionFavorites)) {
            $user->favorites()->syncWithoutDetaching($sessionFavorites);
            session()->forget('favorites');
        }

        // دمج المفضلة من guest_uuid
        $guestUuid = $request->cookie('guest_uuid');
        if ($guestUuid) {
            Favorite::where('guest_uuid', $guestUuid)
                    ->update([
                        'user_id' => $user->id,
                        'guest_uuid' => null
                    ]);

            Cart::where('guest_uuid', $guestUuid)
                ->update([
                    'user_id' => $user->id,
                    'guest_uuid' => null
                ]);
        }
    }

    // إرسال OTP لإعادة تعيين كلمة المرور
public function sendResetOtp(Request $request) {
    $request->validate(['email' => 'required|email']);

    // التحقق من وجود المستخدم
    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني'
        ], 404);
    }

    try {
        // توليد كود OTP (6 أرقام)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10); // صلاحية 10 دقائق

        // تخزين OTP في الكاش مع البريد الإلكتروني
        Cache::put('reset_otp_'.$request->email, [
            'otp' => $otp,
            'expires_at' => $expiresAt
        ], $expiresAt);

        // إرسال البريد الإلكتروني
        Mail::to($request->email)->send(new OtpMail($otp, 'reset'));

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني'
        ]);
    } catch (\Exception $e) {
        Log::error('Reset OTP sending failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إرسال كود التحقق'
        ], 500);
    }
}

/**
 * التحقق من صحة OTP لإعادة تعيين كلمة المرور
 */
public function verifyResetOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string|size:6',
    ]);

    // التحقق من صحة OTP
    $cachedOtp = Cache::get('reset_otp_'.$request->email);

    if (!$cachedOtp || $cachedOtp['otp'] !== $request->otp) {
        return response()->json([
            'success' => false,
            'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية'
        ], 401);
    }

    // إنشاء token مؤقت للتحقق (صالح لمرة واحدة ولمدة قصيرة)
    $verificationToken = Str::random(60);
    Cache::put('reset_verification_'.$request->email, $verificationToken, now()->addMinutes(10));

    return response()->json([
        'success' => true,
        'message' => 'تم التحقق من كود التحقق بنجاح',
        'verification_token' => $verificationToken
    ]);
}

/**
 * تحديث كلمة المرور بعد التحقق من OTP
 */
public function updatePasswordAfterVerification(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'verification_token' => 'required|string',
        'password' => ['required', 'confirmed', PasswordRule::defaults()],
    ]);

    // التحقق من صحة token التحقق
    $storedToken = Cache::get('reset_verification_'.$request->email);

    if (!$storedToken || $storedToken !== $request->verification_token) {
        return response()->json([
            'success' => false,
            'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'
        ], 401);
    }

    // العثور على المستخدم وتحديث كلمة المرور
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'المستخدم غير موجود'
        ], 404);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    // حذف tokens من الكاش بعد الاستخدام
    Cache::forget('reset_verification_'.$request->email);
    Cache::forget('reset_otp_'.$request->email);

    // إرسال إشعار بتغيير كلمة المرور
    event(new PasswordReset($user));

    return response()->json([
        'success' => true,
        'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
    ]);
}



}
