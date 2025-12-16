<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        return response()->json(
            Blog::latest()->get()
        );
    }

    public function show(Blog $blog)
    {
        return response()->json($blog);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'images.*'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $images = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('blogs', 'public');
            }
        }

        $blog = Blog::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'images'      => $images,
        ]);

        return response()->json($blog, 201);
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'images.*'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('blogs', 'public');
            }
            $data['images'] = $images;
        }

        $blog->update($data);

        return response()->json($blog);
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();
        return response()->json([
            'message' => 'تم حذف المقال بنجاح'
        ]);
    }
}
