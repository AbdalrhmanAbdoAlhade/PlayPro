<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TransferRequest;
use App\Models\Field;
use App\Models\FieldPeriod;
use App\Models\FieldBooking; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Mail; 
use App\Mail\UserNotificationMail;
use App\Models\User;

class TransferRequestController extends Controller
{
    // =================================================================
    // 1. دالة عرض الطلبات (Index) - مقيدة بالصلاحيات
    // =================================================================

    public function index()
    {
        $user = Auth::user();
        $query = TransferRequest::query();

        // 1. فحص الصلاحيات وتحديد الاستعلام
        if ($user->role === 'Admin') {
            // المسؤول يرى جميع طلبات النقل
            // لا حاجة لتعديل الـ query
        } elseif ($user->role === 'Owner') {
            // المالك يرى الطلبات التي تستهدف أي ملعب يمتلكه
            // نفترض علاقة user->fields() للمالك
            $ownedFieldIds = $user->fields()->pluck('id'); 
            if ($ownedFieldIds->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'ليس لديك صلاحية لعرض أي طلبات نقل.'], 403);
            }
            $query->whereIn('target_field_id', $ownedFieldIds);
        } elseif ($user->role === 'Coach') {
            // المدرب يرى الطلبات التي تستهدف الملعب المحدد له
            // نفترض أن Field لديه coach_id يشير للمدرب
            $coachFieldIds = Field::where('coach_id', $user->id)->pluck('id');
            if ($coachFieldIds->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'ليس لديك صلاحية لعرض أي طلبات نقل.'], 403);
            }
            $query->whereIn('target_field_id', $coachFieldIds);
        } else {
            // باقي الأدوار غير مصرح لها بالعرض
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بالوصول إلى هذه الموارد.'], 403);
        }

        // 2. جلب الطلبات مع العلاقات
        $requests = $query->with([
            'user:id,name', 
            'currentBooking.field:id,name', // جلب الملعب الحالي
            'currentBooking.period:id,start_time,end_time', // جلب فترة الحجز الحالي
            'targetField:id,name', // جلب الملعب المستهدف
            'targetPeriod:id,start_time,end_time' // جلب الفترة المستهدفة
        ])->latest()->get();

        return response()->json(['status' => true, 'data' => $requests]);
    }

 /**
     * إنشاء طلب نقل حجز جديد
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. التحقق من صحة البيانات المدخلة
        $data = $request->validate([
            // ************ التصحيح الأول ************
            // يجب أن يشير إلى جدول field_bookings وليس bookings
            'current_booking_id' => 'required|exists:field_bookings,id',
            
            // يجب أن يكون الملعب المستهدف موجوداً
            'target_field_id' => 'required|exists:fields,id', 
            
            // ************ التصحيح الثاني ************
            // يجب أن يشير إلى جدول field_periods وليس periods
            'target_period_id' => 'required|exists:field_periods,id', 
            
            'notes' => 'nullable|string',
        ]);
        
        // 2. التحقق من أن المستخدم هو مالك الحجز الحالي
        // ************ التصحيح الثالث ************
        // استخدام نموذج FieldBooking بدلاً من Booking
        $booking = FieldBooking::find($data['current_booking_id']); 
        
        if (!$booking || $booking->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'الحجز الحالي غير موجود أو غير مملوك لك.'
            ], 403);
        }

        // 3. إنشاء طلب النقل
        $transferRequest = TransferRequest::create([
            'user_id' => $user->id,
            'current_booking_id' => $data['current_booking_id'],
            'target_field_id' => $data['target_field_id'],
            'target_period_id' => $data['target_period_id'],
            'status' => 'Pending', // يبدأ الطلب معلقاً للمراجعة
            'notes' => $data['notes'] ?? null, 
        ]);

        // 4. الرد بنجاح
        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب نقل الحجز بنجاح. سنعلمك بحالة الطلب قريباً.',
            'data' => $transferRequest
        ], 201);
    }

    // =================================================================
    // 2. دالة حذف الطلب (Destroy) - مقيدة بالصلاحيات
    // =================================================================

    public function destroy(TransferRequest $transferRequest)
    {
        $user = Auth::user();

        // السماح للمسؤول أو المستخدم الذي أنشأ الطلب بالحذف
        if ($user->role !== 'Admin' && $transferRequest->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بحذف هذا الطلب.'], 403);
        }

        // منع حذف الطلبات التي تم معالجتها
        if ($transferRequest->status !== 'Pending') {
            return response()->json(['status' => false, 'message' => 'لا يمكن حذف طلب نقل تم معالجته بالفعل. يرجى التواصل مع الدعم الفني.'], 400);
        }

        $transferRequest->delete();

        return response()->json(['status' => true, 'message' => 'تم حذف طلب النقل بنجاح.']);
    }

    // =================================================================
    // 3. دالة الموافقة على الطلب (Approve) - مقيدة بالصلاحيات
    // =================================================================

public function approve(Request $request, TransferRequest $transferRequest)
{
    $user = Auth::user();

    Log::info("Attempting to approve Transfer Request ID: {$transferRequest->id} by User ID: {$user->id}");

    // 1. التحقق من حالة الطلب
    if ($transferRequest->status !== 'Pending') {
        Log::warning("Approval failed for Request ID: {$transferRequest->id}. Status is not Pending ({$transferRequest->status}).");
        return response()->json([
            'status' => false,
            'message' => 'لا يمكن الموافقة على طلب غير معلق.'
        ], 400);
    }
    
    $booking = FieldBooking::find($transferRequest->current_booking_id);

    if (!$booking) {
        Log::error("Approval failed for Request ID: {$transferRequest->id}. Original Booking ID: {$transferRequest->current_booking_id} not found.");
        return response()->json(['status' => false, 'message' => 'الحجز الأصلي غير موجود.'], 404);
    }

    // 1.1 التحقق من الصلاحيات
    if ($user->role === 'field_owner' && $booking->field_id !== $user->owned_field_id) {
        Log::warning("Authorization failed for User ID: {$user->id} on Request ID: {$transferRequest->id}. Field ID mismatch.");
        return response()->json(['status' => false, 'message' => 'ليس لديك صلاحية للموافقة على طلبات نقل الحجوزات هذه.'], 403);
    }
    
    Log::info("Request ID: {$transferRequest->id} Authorization passed. Target Field: {$transferRequest->target_field_id}, Target Period: {$transferRequest->target_period_id}.");


    // 2. التحقق من توافر الملعب الجديد في الفترة المستهدفة
    try {
        $targetField = Field::findOrFail($transferRequest->target_field_id);
        $targetPeriod = FieldPeriod::where('id', $transferRequest->target_period_id)
            ->where('field_id', $targetField->id)
            ->firstOrFail();
    } catch (\Exception $e) {
        // يتم تسجيل هذا إذا لم يتم العثور على الملعب أو الفترة (خطأ 404 أو 500)
        Log::error("Target Field/Period lookup failed for Request ID: {$transferRequest->id}. Error: " . $e->getMessage());
        throw $e; // إعادة إلقاء الخطأ لإرساله للمستخدم
    }

    $currentBookingsAtTarget = FieldBooking::where('field_id', $targetField->id)
        ->where('period_id', $targetPeriod->id)
        ->where('date', $booking->date)
        ->sum('players_count');

    // التحقق من السعة المتاحة
    if ($currentBookingsAtTarget + $booking->players_count > $targetField->capacity) {
        Log::warning("Capacity check failed for Request ID: {$transferRequest->id}. Target Field ID: {$targetField->id} capacity exceeded. Existing: {$currentBookingsAtTarget}, New: {$booking->players_count}, Max: {$targetField->capacity}.");
        return response()->json([
            'status' => false,
            'message' => 'الملعب المستهدف غير متاح أو السعة مكتملة في الفترة المطلوبة.'
        ], 400);
    }
    
    // ----------------------------------------------------------------------
    // 3. تنفيذ التحديث (النقل)
    // ----------------------------------------------------------------------
    
    // A. حذف ملف QR Code القديم
    if ($booking->qr_code) {
        try {
            $oldQrPath = public_path(str_replace(url('/'), '', $booking->qr_code));
            if (File::exists($oldQrPath)) {
                File::delete($oldQrPath);
                Log::info("Deleted old QR code file for Booking ID: {$booking->id}. Path: {$oldQrPath}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete old QR code for Booking ID: {$booking->id}. Error: " . $e->getMessage());
            // نواصل العملية حتى لو فشل الحذف
        }
    }
    
    // B. تحديث بيانات الحجز (النقل)
    $booking->field_id = $transferRequest->target_field_id;
    $booking->period_id = $transferRequest->target_period_id;
    
    
    // C. توليد وحفظ QR Code جديد
    $qrData = [
        'booking_id' => $booking->id,
        // ... (باقي البيانات)
    ];

    $qrCodeFileName = 'booking_'.$booking->id.'_'.Str::random(6).'.png';
    $qrCodePath = public_path('qrcodes/'.$qrCodeFileName);
    
    try {
        if(!File::exists(public_path('qrcodes'))){
            File::makeDirectory(public_path('qrcodes'), 0755, true);
        }

        // توليد وحفظ ملف QR Code الجديد
        QrCode::format('png')->size(300)->generate(json_encode($qrData), $qrCodePath);
        $booking->qr_code = url('qrcodes/'.$qrCodeFileName);
        Log::info("New QR code generated successfully for Booking ID: {$booking->id}. Filename: {$qrCodeFileName}");
        
    } catch (\Exception $e) {
        Log::critical("CRITICAL: Failed to generate/save new QR code for Booking ID: {$booking->id}. Error: " . $e->getMessage());
        // إذا فشل هذا الجزء، لا ينبغي أن يكتمل الحجز بنجاح
        return response()->json([
            'status' => false,
            'message' => 'فشل في توليد رمز QR Code الجديد. يرجى مراجعة سجلات الخادم.'
        ], 500);
    }

    // D. حفظ التغييرات في الحجز
    $booking->save();

    // 4. تحديث حالة طلب النقل وإرجاع الاستجابة
    $transferRequest->status = 'Approved';
    $transferRequest->save();
    
    Log::info("Transfer Request ID: {$transferRequest->id} approved and Booking ID: {$booking->id} updated successfully to Field: {$booking->field_id}, Period: {$booking->period_id}.");

// 4. تحديث حالة طلب النقل وإرجاع الاستجابة
    $transferRequest->status = 'Approved';
    $transferRequest->save();
    
    Log::info("Transfer Request ID: {$transferRequest->id} approved and Booking ID: {$booking->id} updated successfully...");

    // ================== Email Notification: Approved ==================
    $requestingUser = User::find($transferRequest->user_id);
    if ($requestingUser && $requestingUser->email) {
        $subject = '✅ تم اعتماد طلب نقل حجزك رقم ' . $transferRequest->id;
        $body = "عزيزي {$requestingUser->name}،\n\n";
        $body .= "تمت الموافقة على طلب نقل الحجز الخاص بك بنجاح.\n";
        $body .= "تم نقل حجزك رقم {$booking->id} إلى الملعب: {$targetField->name} في الفترة: {$targetPeriod->start_time} - {$targetPeriod->end_time}.\n\n";
        $body .= "يمكنك مراجعة تفاصيل حجزك في التطبيق.\n\n";
        $body .= "شكراً لك،\nفريق PlayPro";

        Mail::to($requestingUser->email)->send(new UserNotificationMail($subject, $body));
        Log::info("Approval email sent to User ID: {$requestingUser->id}");
    }
    // ===================================================================
    return response()->json([
        'status' => true,
        'message' => 'تمت الموافقة على طلب النقل بنجاح وتم تحديث الحجز، وتم توليد رمز QR جديد.',
        'new_booking_details' => $booking->fresh()->load(['field', 'period']),
    ], 200);
}

/**
 * رفض طلب نقل حجز معين.
 */
public function reject(Request $request, TransferRequest $transferRequest)
{
    $user = Auth::user();

    // 1. التحقق من الصلاحيات والحالة
    if ($transferRequest->status !== 'Pending') {
        return response()->json(['status' => false, 'message' => 'لا يمكن رفض طلب غير معلق.'], 400);
    }

    // (هنا يجب إضافة منطق تحقق الصلاحيات المشابه لدالة approve للتأكد من أن المالك/المسؤول هو من يقوم بالرفض)

    // 2. تحديث حالة الطلب
    $transferRequest->status = 'Rejected';
    $transferRequest->save();

    // ================== Email Notification: Rejected ==================
    $requestingUser = User::find($transferRequest->user_id);
    if ($requestingUser && $requestingUser->email) {
        $subject = '❌ رفض طلب نقل حجزك رقم ' . $transferRequest->id;
        $body = "عزيزي {$requestingUser->name}،\n\n";
        $body .= "نعتذر، تم رفض طلب نقل الحجز الخاص بك (رقم الطلب: {$transferRequest->id}).\n";
        $body .= "قد يكون السبب عدم توافر الملعب أو الفترة المطلوبة.\n\n";
        $body .= "حجزك الأصلي لا يزال ساري المفعول.\n\n";
        $body .= "شكراً لك،\nفريق PlayPro";

        Mail::to($requestingUser->email)->send(new UserNotificationMail($subject, $body));
        Log::info("Rejection email sent to User ID: {$requestingUser->id}");
    }
    // ===================================================================

    return response()->json([
        'status' => true,
        'message' => 'تم رفض طلب النقل بنجاح وإرسال إشعار للمستخدم.',
        'request' => $transferRequest
    ], 200);
}

}