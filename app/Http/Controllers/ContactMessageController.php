<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * 🔹 إرسال رسالة Contact Us
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'country'   => 'required|string|max:255',
            'phone'     => 'required|string|max:50',
            'subject'   => 'required|string|max:255',
            'message'   => 'required|string',
        ]);

        $contact = ContactMessage::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Message sent successfully',
            'data' => $contact
        ], 201);
    }

    /**
     * 🔹 عرض كل الرسائل (للأدمن)
     */
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $messages
        ]);
    }

    /**
     * 🔹 عرض رسالة واحدة
     */
    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $message
        ]);
    }
}
