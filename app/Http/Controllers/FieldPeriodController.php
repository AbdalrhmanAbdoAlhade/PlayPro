<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Field;
use App\Models\FieldPeriod;
use Illuminate\Support\Facades\Auth;

class FieldPeriodController extends Controller
{
    /**
     * عرض كل فترات ملعب معين
     */
    public function index($fieldId)
    {
        $field = Field::findOrFail($fieldId);

        return response()->json([
            'status' => true,
            'data' => $field->periods
        ]);
    }

    /**
     * إنشاء فترة جديدة (Owner فقط)
     */
    public function store(Request $request, $fieldId)
    {
        $field = Field::findOrFail($fieldId);

        if (Auth::id() !== $field->owner_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        $data = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price_per_player' => 'required|numeric|min:0'
        ]);

        $period = $field->periods()->create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الفترة بنجاح',
            'data' => $period
        ], 201);
    }

    /**
     * عرض فترة محددة
     */
    public function show($fieldId, $periodId)
    {
        $period = FieldPeriod::where('field_id', $fieldId)
                             ->findOrFail($periodId);

        return response()->json([
            'status' => true,
            'data' => $period
        ]);
    }

    /**
     * تحديث فترة (Owner فقط)
     */
    public function update(Request $request, $fieldId, $periodId)
    {
        $field = Field::findOrFail($fieldId);
        $period = FieldPeriod::where('field_id', $fieldId)
                             ->findOrFail($periodId);

        if (Auth::id() !== $field->owner_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        $data = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price_per_player' => 'required|numeric|min:0'
        ]);

        $period->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الفترة بنجاح',
            'data' => $period
        ]);
    }

    /**
     * حذف فترة (Owner فقط)
     */
    public function destroy($fieldId, $periodId)
    {
        $field = Field::findOrFail($fieldId);
        $period = FieldPeriod::where('field_id', $fieldId)
                             ->findOrFail($periodId);

        if (Auth::id() !== $field->owner_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        $period->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الفترة بنجاح'
        ]);
    }
}
