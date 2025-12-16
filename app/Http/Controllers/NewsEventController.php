<?php

namespace App\Http\Controllers;

use App\Models\NewsEvent;
use Illuminate\Http\Request;

class NewsEventController extends Controller
{
    public function index()
    {
        return response()->json(
            NewsEvent::latest()->get()
        );
    }

    public function show(NewsEvent $newsEvent)
    {
        return response()->json($newsEvent);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $images = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('news-events', 'public');
            }
        }

        $newsEvent = NewsEvent::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'images'      => $images,
        ]);

        return response()->json($newsEvent, 201);
    }

    public function update(Request $request, NewsEvent $newsEvent)
    {
        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('news-events', 'public');
            }
            $data['images'] = $images;
        }

        $newsEvent->update($data);

        return response()->json($newsEvent);
    }

    public function destroy(NewsEvent $newsEvent)
    {
        $newsEvent->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}
