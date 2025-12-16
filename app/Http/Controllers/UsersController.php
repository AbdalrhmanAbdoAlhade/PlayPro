<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserNotificationMail;

class UsersController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */
public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'phone'    => 'required|string|unique:users,phone',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'phone'    => $request->phone,
        'role'     => 'User',
    ]);

    /** ================== WhatsApp ================== */
  /*  $cleanPhone = preg_replace('/[^0-9]/', '', $user->phone);

    if ($cleanPhone) {
        $message = "مرحباً {$user->name}، أكمل بياناتك عبر الرابط:
https://jewelry-admin.souqna-sa.com/update-profile";

        Http::post('https://sawaqna.xyz/api/sessions/jewelry_souqna_sa/send-text', [
            'to'      => $cleanPhone,
            'message' => $message,
        ]);
    }*/

    /** ================== Email (Queue) ================== */
Mail::to($user->email)->send(new UserNotificationMail(
    'تم استلام طلب التسجيل بنجاح',
    "عزيزي {$user->name}،

تم استلام طلب التسجيل الخاص بك بنجاح.
سيتم مراجعة بياناتك من قبل الإدارة في أقرب وقت.

شكراً لثقتك بنا،
فريق PlayPro"
));


    /** ================== Token ================== */
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message'      => 'User registered successfully',
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user'         => $user,
    ], 201);
}
 /**
     * تسجيل مستخدم جديد
     */

public function registerOwner(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'              => 'required|string|max:255',
        'email'             => 'required|email|unique:users,email',
        'password'          => 'required|string|min:6',
        'phone'             => 'required|string|unique:users,phone',
        'registration_role' => 'nullable|string|in:User,Coach,Owner',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = User::create([
        'name'              => $request->name,
        'email'             => $request->email,
        'password'          => Hash::make($request->password),
        'phone'             => $request->phone,
        'role'              => 'User', // دائمًا User
        'registration_role' => $request->registration_role ?? 'User',
    ]);

    /** ================== WhatsApp ================== */
 /*   $cleanPhone = preg_replace('/[^0-9]/', '', $user->phone);

    if ($cleanPhone) {
        $message = "مرحباً {$user->name}، أكمل بياناتك عبر الرابط:
https://jewelry-admin.souqna-sa.com/update-profile";

        Http::post('https://sawaqna.xyz/api/sessions/jewelry_souqna_sa/send-text', [
            'to'      => $cleanPhone,
            'message' => $message,
        ]);
    }*/

    /** ================== Email (Queue) ================== */
Mail::to($user->email)->send(new UserNotificationMail(
    'تم استلام طلب التسجيل بنجاح',
    "عزيزي {$user->name}،

تم استلام طلب التسجيل الخاص بك بنجاح.
سيتم مراجعة بياناتك من قبل الإدارة في أقرب وقت.

شكراً لثقتك بنا،
فريق PlayPro"
));



    /** ================== Token ================== */
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message'      => 'User registered successfully',
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user'         => $user,
    ], 201);
}


public function getPendingRegistrations()
{
    $users = User::whereNotNull('registration_role')
        ->where('status', 'pending')
        ->get();

    return response()->json([
        'status' => true,
        'data' => $users
    ]);
}

public function updateRole(Request $request, $userId)
{
    $data = $request->validate([
        'role' => 'required|string|in:User,Coach,Owner',
    ]);

    $user = User::findOrFail($userId);
 // إذا كان الـ status مازال pending يتم تغييره إلى active
    if ($user->status === 'pending') {
        $user->status = 'active';
    }

    // تحديث الرول
    $user->role = $data['role'];
    $user->save();

    return response()->json([
        'status' => true,
        'message' => 'تم تحديث الدور بنجاح',
        'user' => $user
    ]);
}

    /**
     * تسجيل دخول
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * تسجيل خروج
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * عرض بيانات البروفايل
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * تحديث بيانات المستخدم
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'required', 'string', Rule::unique('users')->ignore($user->id)],
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('name'))
            $user->name = $request->name;
        if ($request->has('email'))
            $user->email = $request->email;
        if ($request->has('phone'))
            $user->phone = $request->phone;

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = url('storage/' . $path);
        }

        $user->save();

        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }

    /**
     * عرض كل المستخدمين (Admin فقط)
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'Admin') {
            return response()->json(['message' => 'غير مصرح لك بتنفيذ هذا الإجراء.'], 403);
        }

        $users = User::all();
        return response()->json($users);
    }

    /**
     * حذف مستخدم (Admin فقط)
     */
    public function destroy($id, Request $request)
    {
        $user = User::findOrFail($id);

        if ($request->user()->role !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}
