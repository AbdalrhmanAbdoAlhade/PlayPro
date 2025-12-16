<?php

namespace App\Http\Controllers;

use App\Models\ChairmanMessage;
use Illuminate\Http\Request;

class ChairmanMessageController extends Controller
{
    public function index()
    {
        return response()->json(
            ChairmanMessage::latest()->get()
        );
    }

    public function show(ChairmanMessage $chairmanMessage)
    {
        return response()->json($chairmanMessage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('chairman', 'public');
        }

        $message = ChairmanMessage::create($data);

        return response()->json($message, 201);
    }

    public function update(Request $request, ChairmanMessage $chairmanMessage)
    {
        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('chairman', 'public');
        }

        $chairmanMessage->update($data);

        return response()->json($chairmanMessage);
    }

    public function destroy(ChairmanMessage $chairmanMessage)
    {
        $chairmanMessage->delete();

        return response()->json([
            'message' => 'تم حذف رسالة رئيس مجلس الإدارة بنجاح'
        ]);
    }
}
