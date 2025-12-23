<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    /**
     * حفظ رسالة جديدة مرتبطة بمستخدم
     */
    public function storeMessageByUserId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:user,assistant',
            'message' => 'required|string|max:5000',
            'session_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // البحث عن سجل المستخدم (حسب user_id فقط)
        $conversation = Conversation::where('user_id', $request->user_id)->first();

        // لو مفيش سجل، نعمل واحد جديد
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $request->user_id,
                'session_id' => $request->session_id,
                'message' => json_encode([]),
            ]);
        }

        // تحويل الرسائل القديمة إلى array
        $messages = json_decode($conversation->message, true) ?? [];

        // التأكد إن message_id مش مكرر
        foreach ($messages as $msg) {
            if (isset($msg['message_id']) && $msg['message_id'] === $request->message_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الـ message_id مستخدم من قبل'
                ], 409);
            }
        }

        // إضافة الرسالة الجديدة
        $messages[] = [
            'message_id' => $request->message_id,
            'role' => $request->role,
            'message' => $request->message,
            'timestamp' => now()->toDateTimeString(),
        ];

        // تحديث بيانات المستخدم
        $conversation->update([
            'message' => json_encode($messages)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الرسالة بنجاح',
            'data' => $conversation
        ]);
    }

    /**
     * استرجاع سجل الرسائل لمستخدم معين
     */
    public function getUserMessages($userId)
    {
        $conversation = Conversation::where('user_id', $userId)->first();

        if (!$conversation) {
            return response()->json([
                'success' => true,
                'message' => [],
                'message' => 'لا توجد محادثات لهذا المستخدم'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => json_decode($conversation->message, true)
        ]);
    }

    /**
     * استرجاع جميع المستخدمين الذين لديهم محادثات
     */
    public function getAllUserConversations()
    {
        $users = Conversation::select('user_id')
            ->selectRaw('COUNT(*) as total_message')
            ->orderByDesc('total_message')
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => $users->count()
        ]);
    }
}
