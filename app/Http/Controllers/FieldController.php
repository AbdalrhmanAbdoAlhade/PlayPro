<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FieldController extends Controller
{
    /**
     * عرض كل الملاعب
     */
    public function index()
    {
        $fields = Field::with(['owner:id,name,phone', 'periods', 'icon', 'gallery'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $fields
        ]);
    }
/**
     * عرض ملاعب المستخدم المالك (Owner) الحالي فقط (مع السماح بالوصول للأدمن)
     */
    public function myFields()
    {
        $user = Auth::user();

        $allowedRoles = ['Owner', 'Admin'];

        if (! in_array($user->role, $allowedRoles)) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالوصول إلى هذه الموارد'
            ], 403);
        }

        if ($user->role === 'Owner') {
            $fields = Field::where('owner_id', $user->id);
        }
        
        if ($user->role === 'Admin') {
            $fields = Field::query(); 
        }

        $fields = $fields->with(['owner:id,name,phone', 'periods', 'icon', 'gallery'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $fields
        ]);
    }
    /**
     * إنشاء ملعب جديد (Owner فقط)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'Owner') {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بإنشاء ملعب'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'size' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',

            'periods' => 'required|array|min:1',
            'periods.*.start_time' => 'required|date_format:H:i',
            'periods.*.end_time' => 'required|date_format:H:i|after:periods.*.start_time',
            'periods.*.price_per_player' => 'required|numeric|min:0',
        ]);

        // إنشاء الملعب
        $field = Field::create([
            'name' => $data['name'],
            'size' => $data['size'],
            'capacity' => $data['capacity'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'city' => $data['city'],
            'address' => $data['address'],
            'description' => $data['description'] ?? null,
            'owner_id' => $user->id,
        ]);

        // إنشاء الفترات
        foreach ($data['periods'] as $period) {
            $field->periods()->create([
                'start_time' => $period['start_time'],
                'end_time' => $period['end_time'],
                'price_per_player' => $period['price_per_player'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الملعب بنجاح',
            'data' => $field->load('periods')
        ], 201);
    }

    /**
     * عرض ملعب واحد
     */
    public function show($id)
    {
        $field = Field::with(['owner:id,name,phone', 'periods'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $field
        ]);
    }

    /**
     * تعديل ملعب (Owner صاحب الملعب فقط)
     */
    public function update(Request $request, $id)
    {
        $field = Field::findOrFail($id);

        if (Auth::id() !== $field->owner_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذا الملعب'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:50',
            'capacity' => 'sometimes|integer|min:1',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'city' => 'sometimes|string|max:100',
            'address' => 'sometimes|string|max:255',
            'description' => 'nullable|string',

            'periods' => 'nullable|array',
            'periods.*.start_time' => 'required_with:periods|date_format:H:i',
            'periods.*.end_time' => 'required_with:periods|date_format:H:i',
            'periods.*.price_per_player' => 'required_with:periods|numeric|min:0',
        ]);

        $field->update($data);

        // لو بعت فترات → نحذف القديم ونضيف الجديد
        if ($request->has('periods')) {
            $field->periods()->delete();

            foreach ($data['periods'] as $period) {
                $field->periods()->create($period);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل الملعب بنجاح',
            'data' => $field->load('periods')
        ]);
    }

    /**
     * حذف ملعب
     */
    public function destroy($id)
    {
        $field = Field::findOrFail($id);

        if (Auth::id() !== $field->owner_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بحذف هذا الملعب'
            ], 403);
        }

        $field->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الملعب بنجاح'
        ]);
    }
}
