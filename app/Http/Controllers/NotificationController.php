<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmService;

class NotificationController extends Controller
{
    public function registerToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'sometimes|string|in:android,ios,web'
        ]);

        $user = Auth::user();
        $deviceType = $request->input('device_type', 'android');

        DeviceToken::where('token', $request->token)->delete();

        $user->deviceTokens()->create([
            'token' => $request->token,
            'device_type' => $deviceType
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل جهازك بنجاح'
        ]);
    }

    public function removeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        Auth::user()->deviceTokens()
            ->where('token', $request->token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة جهازك بنجاح'
        ]);
    }

    public function testNotification(FcmService $fcmService)
    {
        $user = Auth::user();

        $result = $fcmService->sendToUser(
            $user,
            'إختبار الإشعارات',
            'مرحباً ' . $user->name . '! هذا إشعار تجريبي من التطبيق'
        );

        if ($result === false) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد أجهزة مسجلة لهذا المستخدم'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار بنجاح',
            'data' => $result
        ]);
    }
}
