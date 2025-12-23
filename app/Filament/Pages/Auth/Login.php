<?php

namespace App\Filament\Pages\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.throttled', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();

        // التحقق من وجود المستخدم بالإيميل أولاً
        $user = \App\Models\User::where('email', $data['email'])->first();

        if (!$user) {
            // رسالة خطأ للإيميل
            throw ValidationException::withMessages([
                'data.email' => 'هذا البريد الإلكتروني غير مسجل في النظام',
            ]);
        }

        // محاولة تسجيل الدخول
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $data['remember'] ?? false)) {
            // رسالة خطأ لكلمة المرور
            throw ValidationException::withMessages([
                'data.password' => 'كلمة المرور غير صحيحة',
            ]);
        }

        return app(LoginResponse::class);
    }
}
