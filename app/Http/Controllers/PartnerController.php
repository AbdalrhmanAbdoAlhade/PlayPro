<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * عرض كل الشركاء
     */
    public function index()
    {
        return response()->json(Partner::latest()->get());
    }

    /**
     * إضافة شريك جديد
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('partners', 'public');
        }

        $partner = Partner::create($data);

        return response()->json($partner, 201);
    }

    /**
     * عرض شريك واحد
     */
    public function show(Partner $partner)
    {
        return response()->json($partner);
    }

    /**
     * تحديث شريك
     */
    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('partners', 'public');
        }

        $partner->update($data);

        return response()->json($partner);
    }

    /**
     * حذف شريك
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();
        return response()->json(['message' => 'تم حذف الشريك بنجاح']);
    }
}
