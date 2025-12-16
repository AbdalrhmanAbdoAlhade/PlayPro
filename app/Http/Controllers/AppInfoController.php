<?php

namespace App\Http\Controllers;

use App\Models\AppInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppInfoController extends Controller
{
    /**
     * 🔹 عرض بيانات التطبيق
     */
    public function show()
    {
        $appInfo = AppInfo::first();

        return response()->json([
            'status' => true,
            'data' => $appInfo
        ]);
    }

    /**
     * 🔹 إنشاء أو تحديث بيانات التطبيق (سجل واحد)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'platform_name'     => 'required|string|max:255',

            'logo'              => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',

            'facebook'          => 'nullable|url',
            'instagram'         => 'nullable|url',
            'tiktok'            => 'nullable|url',
            'x'                 => 'nullable|url',
            'snapchat'          => 'nullable|url',

            'phone'             => 'nullable|string|max:50',
            'whatsapp'          => 'nullable|string|max:50',

            'management_name'   => 'nullable|string|max:255',
            'management_image'  => 'nullable|image|mimes:png,jpg,jpeg|max:2048',

            'address'           => 'nullable|string',
        ]);

        $appInfo = AppInfo::first();

        // 🔹 رفع اللوجو
        if ($request->hasFile('logo')) {
            if ($appInfo && $appInfo->logo) {
                Storage::delete($appInfo->logo);
            }
            $data['logo'] = $request->file('logo')->store('app_info');
        }

        // 🔹 رفع صورة الإدارة
        if ($request->hasFile('management_image')) {
            if ($appInfo && $appInfo->management_image) {
                Storage::delete($appInfo->management_image);
            }
            $data['management_image'] = $request->file('management_image')->store('app_info');
        }

        $appInfo = AppInfo::updateOrCreate(
            ['id' => 1],
            $data
        );

        return response()->json([
            'status' => true,
            'message' => 'App info updated successfully',
            'data' => $appInfo
        ]);
    }
}
