<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Point;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointController extends Controller
{
    /**
     * عرض نقاط المستخدم الحالي
     */
    public function index()
    {
        $user = Auth::user();
        $points = $user->points()->with('user')->latest()->get();

        return response()->json([
            'success' => true,
            'total_points' => $user->total_points,
            'available_points' => $user->available_points,
            'points_history' => $points
        ]);
    }

    /**
     * إضافة نقاط للمستخدم
     */
    public function store(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'source' => 'required|string|in:purchase,referral,bonus,other',
            'description' => 'nullable|string'
        ]);

        $user = Auth::user();
        $point = $user->addPoints(
            $request->points,
            $request->source,
            $request->description
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة النقاط بنجاح',
            'points' => $point,
            'new_balance' => $user->fresh()->available_points
        ], 201);
    }

    /**
     * عرض تفاصيل نقطة محددة
     */
    public function show(Point $point)
    {
        // التحقق من أن النقطة تخص المستخدم الحالي
        if ($point->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول إلى هذه النقطة'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'point' => $point->load('user')
        ]);
    }
}
