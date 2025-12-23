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

   public function sendResetLink(Request $request) {
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'لا يوجد مستخدم مسجل بهذا البريد الإلكتروني.'], 404);
    }

    try {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'تم إرسال رابط إعادة التعيين إلى بريدك الإلكتروني.']);
        } else {
            \Log::error('Password reset error: ' . $status);
            return response()->json(['message' => 'حدث خطأ أثناء إرسال الرابط. يرجى المحاولة مرة أخرى لاحقًا.'], 500);
        }
    } catch (\Exception $e) {
        \Log::error('Password reset exception: ' . $e->getMessage());
        return response()->json(['message' => 'حدث خطأ في النظام: ' . $e->getMessage()], 500);
    }
}

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


        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        Cache::put('otp_'.$request->email, [
            'otp' => $otp,
            'expires_at' => $expiresAt
        ], $expiresAt);

        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني'
        ]);
    }

    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        $cachedOtp = Cache::get('otp_'.$request->email);

        if (!$cachedOtp || $cachedOtp['otp'] !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية'
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        Auth::login($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->mergeGuestData($request);

        Cache::forget('otp_'.$request->email);

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token
        ])->withoutCookie('guest_uuid');
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', PasswordRule::defaults()]
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        event(new PasswordReset($user));

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }


    private function mergeGuestData(Request $request)
    {
        $user = Auth::user();

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

            Cart::where('guest_uuid', $guestUuid)
                ->update([
                    'user_id' => $user->id,
                    'guest_uuid' => null
                ]);
        }
    }


public function sendResetOtp(Request $request) {
    $request->validate(['email' => 'required|email']);


    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني'
        ], 404);
    }

    try {

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);


        Cache::put('reset_otp_'.$request->email, [
            'otp' => $otp,
            'expires_at' => $expiresAt
        ], $expiresAt);


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


public function verifyResetOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string|size:6',
    ]);

    $cachedOtp = Cache::get('reset_otp_'.$request->email);

    if (!$cachedOtp || $cachedOtp['otp'] !== $request->otp) {
        return response()->json([
            'success' => false,
            'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية'
        ], 401);
    }

    $verificationToken = Str::random(60);
    Cache::put('reset_verification_'.$request->email, $verificationToken, now()->addMinutes(10));

    return response()->json([
        'success' => true,
        'message' => 'تم التحقق من كود التحقق بنجاح',
        'verification_token' => $verificationToken
    ]);
}


public function updatePasswordAfterVerification(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'verification_token' => 'required|string',
        'password' => ['required', 'confirmed', PasswordRule::defaults()],
    ]);

    $storedToken = Cache::get('reset_verification_'.$request->email);

    if (!$storedToken || $storedToken !== $request->verification_token) {
        return response()->json([
            'success' => false,
            'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'
        ], 401);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'المستخدم غير موجود'
        ], 404);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    Cache::forget('reset_verification_'.$request->email);
    Cache::forget('reset_otp_'.$request->email);

    event(new PasswordReset($user));

    return response()->json([
        'success' => true,
        'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
    ]);
}



}
