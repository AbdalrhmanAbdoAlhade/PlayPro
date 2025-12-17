<?php

namespace App\Http\Controllers;

use App\Models\FieldBooking;
use App\Models\Field;
use App\Models\FieldPeriod;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class FieldBookingController extends Controller
{
    /**
     * عرض الحجوزات (Admin / Owner)
     */
    public function index()
    {
        $user = Auth::user();

        // Admin يشوف كل الحجوزات
        if ($user->role === 'Admin') {
            $bookings = FieldBooking::with(['field', 'period', 'user'])
                ->latest()
                ->get();
        }

        // Owner يشوف حجوزات ملاعبه فقط
        elseif ($user->role === 'Owner') {
            $bookings = FieldBooking::whereHas('field', function ($q) use ($user) {
                    $q->where('owner_id', $user->id);
                })
                ->with(['field', 'period', 'user'])
                ->latest()
                ->get();
        }

        else {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $bookings
        ]);
    }

    /**
     * عرض حجوزات المستخدم الحالي فقط
     */
    public function myBookings()
    {
        $bookings = FieldBooking::where('user_id', Auth::id())
            ->with(['field', 'period'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $bookings
        ]);
    }

    /**
     * إنشاء حجز جديد
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'field_id'       => 'required|exists:fields,id',
            'period_id'      => 'required|exists:field_periods,id',
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'email'          => 'required|email|max:255',
            'date'           => 'required|date',
            'players_count'  => 'nullable|integer|min:1',
        ]);

        $field = Field::findOrFail($data['field_id']);

        $period = FieldPeriod::where('id', $data['period_id'])
            ->where('field_id', $field->id)
            ->firstOrFail();

        $playersCount = $data['players_count'] ?? 1;

        // التحقق من السعة
        $currentBookings = FieldBooking::where('field_id', $field->id)
            ->where('period_id', $period->id)
            ->where('date', $data['date'])
            ->sum('players_count');

        if ($currentBookings + $playersCount > $field->capacity) {
            return response()->json([
                'status' => false,
                'message' => 'عدد اللاعبين في هذه الفترة مكتمل'
            ], 400);
        }

        $price = $period->price_per_player * $playersCount;

        // إنشاء الحجز
        $booking = FieldBooking::create([
            'user_id'        => Auth::id(), // ✅ ربط الحجز بالمستخدم
            'field_id'       => $data['field_id'],
            'period_id'      => $data['period_id'],
            'name'           => $data['name'],
            'phone'          => $data['phone'],
            'email'          => $data['email'],
            'date'           => $data['date'],
            'players_count'  => $playersCount,
            'price'          => $price,
        ]);

        // بيانات QR
        $qrData = [
            'booking_id'     => $booking->id,
            'field_name'     => $field->name,
            'date'           => $booking->date,
            'period'         => $period->start_time . ' - ' . $period->end_time,
            'players_count'  => $playersCount,
            'price'          => $price
        ];

        $qrCodeFileName = 'booking_' . $booking->id . '_' . Str::random(6) . '.png';
        $qrPath = public_path('qrcodes');

        if (!file_exists($qrPath)) {
            mkdir($qrPath, 0755, true);
        }

        QrCode::format('png')
            ->size(300)
            ->generate(json_encode($qrData), $qrPath . '/' . $qrCodeFileName);

        // حفظ رابط QR
        $booking->update([
            'qr_code' => url('qrcodes/' . $qrCodeFileName)
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم الحجز بنجاح',
            'data' => $booking->fresh()->load(['field', 'period'])
        ], 201);
    }

    /**
     * التحقق من QR Code
     */
    public function verifyQr(Request $request)
    {
        $data = $request->validate([
            'qr_code_url' => 'required|url'
        ]);

        $booking = FieldBooking::where('qr_code', $data['qr_code_url'])->first();

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'QR غير صالح'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $booking->load(['field', 'period', 'user'])
        ]);
    }

    /**
     * عرض حجز واحد
     */
    public function show($id)
    {
        $booking = FieldBooking::with(['field', 'period', 'user'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $booking
        ]);
    }
    
    public function destroy($id)
{
    $user = Auth::user();
    $booking = FieldBooking::findOrFail($id);

    // Admin يحذف أي حجز
    if ($user->role === 'Admin') {
        $booking->delete();
    }

    // Owner يحذف حجوزات ملاعبه فقط
    elseif ($user->role === 'Owner') {
        if ($booking->field->owner_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بحذف هذا الحجز'
            ], 403);
        }

        $booking->delete();
    }

    // User لا يحق له الحذف
    else {
        return response()->json([
            'status' => false,
            'message' => 'غير مصرح لك'
        ], 403);
    }

    return response()->json([
        'status' => true,
        'message' => 'تم حذف الحجز بنجاح'
    ]);
}

}
