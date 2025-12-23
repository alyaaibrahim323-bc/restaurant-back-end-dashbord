<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        // 1. التحقق أولاً إذا كان المستخدم مصادقاً عليه
        if (!Auth::check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        // 2. التحقق من أن المستخدم له دور مسموح
        $user = Auth::user();

        // تأكدي من أن المستخدم لديه أي من الأدوار المطلوبة
        if (!$user->hasAnyRole(['admin', 'super_admin'])) {
            Auth::logout(); // تسجيل الخروج
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'غير مصرح لك بالوصول إلى لوحة التحكم']);
        }

        return $next($request);
    }
}
