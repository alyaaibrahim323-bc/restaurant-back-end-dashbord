<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Branch;

class AddressController extends Controller
{

    public function index()
    {
        $addresses = Address::forUser(Auth::id())
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }


    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'street' => 'required|string|max:255',
        'city' => 'required|string|max:100',
        'state' => 'required|string|max:100',
        'country' => 'required|string|max:100',
        'postal_code' => 'required|string|max:20',
        'building_number' => 'required|string|max:20',
        'apartment_number' => 'nullable|string|max:20',
        'floor_number' => 'nullable|string|max:20',
        'phone_number' => 'required|string|max:20',
        'is_default' => 'sometimes|boolean',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'branch_id' => 'required|exists:branches,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $branch = Branch::active()->find($request->branch_id);

    if (!$branch) {
        return response()->json([
            'success' => false,
            'message' => 'الفرع غير موجود أو غير مفعل'
        ], 400);
    }

    $addressData = $request->only([
        'street', 'city', 'state', 'country', 'postal_code',
        'building_number', 'apartment_number', 'floor_number',
        'phone_number', 'latitude', 'longitude'
    ]);
    $addressData['user_id'] = Auth::id();

    $address = Address::create($addressData);

    if ($request->is_default) {
        $this->setDefaultAddress($address);
    }

    return response()->json([
        'success' => true,
        'message' => 'تم إنشاء العنوان بنجاح',
        'data' => [
            'address' => $address,
            'delivery_fee' => $branch->delivery_fee_base
        ]
    ], 201);
}



    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بتعديل هذا العنوان'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'building_number' => 'sometimes|string|max:20',
            'apartment_number' => 'nullable|string|max:20',
            'floor_number' => 'nullable|string|max:20',
            'phone_number' => 'sometimes|string|max:20',
            'is_default' => 'sometimes|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $address->update($request->all());

        if ($request->is_default) {
            $this->setDefaultAddress($address);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العنوان بنجاح',
            'data' => $address
        ]);
    }


    public function destroy(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بحذف هذا العنوان'
            ], 403);
        }

        if ($address->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العنوان الافتراضي'
            ], 400);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنوان بنجاح'
        ]);
    }


    public function setDefault(Address $address)
    {

        if ($address->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بتعديل هذا العنوان'
            ], 403);
        }

        $this->setDefaultAddress($address);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين العنوان كافتراضي'
        ]);
    }


    private function setDefaultAddress(Address $address)
    {
        Address::where('user_id', Auth::id())
            ->where('id', '!=', $address->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);
    }
    public function show($id)
{
    $address = Address::where('id', $id)
        ->where('user_id', Auth::id())
        ->first();

    if (!$address) {
        return response()->json([
            'success' => false,
            'message' => 'العنوان غير موجود'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $address
    ]);
}

}
