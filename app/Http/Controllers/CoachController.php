<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CoachController extends Controller
{
    /**
     * عرض كل الكوتشز
     */
    public function index(Request $request)
    {
        $coaches = Coach::with(['user', 'field'])
            ->filter($request->all())
        ->paginate(10);

        return response()->json($coaches, 200);
    }

    /**
     * إنشاء كوتش جديد
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'field_id'         => 'required|exists:fields,id',
            'name'             => 'nullable|string|max:255',
            'age'              => 'nullable|integer|min:10',
            'description'      => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'images.*'         => 'nullable|image|mimes:jpg,jpeg,png',
            'cv_file'          => 'nullable|mimes:pdf,doc,docx',
        ]);

        $data['user_id'] = Auth::id();

        // رفع الصور
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('coaches/images', 'public');
            }
            $data['images'] = $images;
        }

        // رفع الـ CV
        if ($request->hasFile('cv_file')) {
            $data['cv_file'] = $request->file('cv_file')
                ->store('coaches/cv', 'public');
        }

        $coach = Coach::create($data);

        return response()->json([
            'message' => 'تم إنشاء الكوتش بنجاح',
            'data'    => $coach
        ], 201);
    }

    /**
     * عرض كوتش واحد
     */
    public function show($id)
    {
        $coach = Coach::with(['user', 'field','ratings'])->findOrFail($id);

        return response()->json($coach, 200);
    }

    /**
     * تحديث كوتش
     */
    public function update(Request $request, $id)
    {
        $coach = Coach::where('user_id', Auth::id())->findOrFail($id);

        $data = $request->validate([
            'field_id'         => 'sometimes|exists:fields,id',
            'name'             => 'nullable|string|max:255',
            'age'              => 'nullable|integer|min:10',
            'description'      => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'images.*'         => 'nullable|image|mimes:jpg,jpeg,png',
            'cv_file'          => 'nullable|mimes:pdf,doc,docx',
        ]);

        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('coaches/images', 'public');
            }
            $data['images'] = $images;
        }

        if ($request->hasFile('cv_file')) {
            $data['cv_file'] = $request->file('cv_file')
                ->store('coaches/cv', 'public');
        }

        $coach->update($data);

        return response()->json([
            'message' => 'تم تحديث بيانات الكوتش',
            'data'    => $coach
        ], 200);
    }

/**
     * 📌 جلب كل المدربين الذين يعملون في ملاعب المستخدم الحالي (المالك)
     */
public function myFieldsCoaches()
{
    $user = Auth::user();

    // تحقق من وجود مستخدم مسجل دخول
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'يجب تسجيل الدخول أولاً'
        ], 401);
    }

    // جلب أرقام الملاعب التي يمتلكها هذا المستخدم
    $fieldIds = \App\Models\Field::where('owner_id', $user->id)->pluck('id');

    // جلب المدربين
    $coaches = Coach::with(['user', 'field'])
        ->whereIn('field_id', $fieldIds)
        ->get();

    return response()->json([
        'status' => true,
        'total'  => $coaches->count(),
        'data'   => $coaches
    ]);
}

    /**
     * حذف كوتش
     */
    public function destroy($id)
    {
        $coach = Coach::where('user_id', Auth::id())->findOrFail($id);
        $coach->delete();

        return response()->json([
            'message' => 'تم حذف الكوتش بنجاح'
        ], 200);
    }
}
