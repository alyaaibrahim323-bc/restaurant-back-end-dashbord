<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class ProfileapiController extends Controller
{
    // عرض بيانات المستخدم الحالي
    public function show()
    {
        return response()->json([
            'user' => Auth::user()
        ]);
    }

    // تحديث بيانات المستخدم
    public function update(Request $request)
    {

      /** @var \App\Models\User $user */

        $user = Auth::user();

        $validated = $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'تم تحديث البيانات بنجاح',
            'user'    => $user
        ]);
    }
}

