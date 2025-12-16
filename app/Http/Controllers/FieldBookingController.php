<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FieldBooking;
use App\Models\Field;
use App\Models\FieldPeriod;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class FieldBookingController extends Controller
{
public function store(Request $request)
    {
        $data = $request->validate([
            'field_id'=>'required|exists:fields,id',
            'period_id'=>'required|exists:field_periods,id',
            'name'=>'required|string|max:255',
            'phone'=>'required|string|max:20',
            'email'=>'required|email|max:255',
            'date'=>'required|date',
            'players_count'=>'nullable|integer|min:1',
        ]);

        $field = Field::findOrFail($data['field_id']);
        $period = FieldPeriod::where('id',$data['period_id'])
            ->where('field_id',$field->id)->firstOrFail();

        $playersCount = $data['players_count'] ?? 1;

        // التحقق من السعة المتاحة
        $currentBookings = FieldBooking::where('field_id',$field->id)
            ->where('period_id',$period->id)
            ->where('date',$data['date'])
            ->sum('players_count');

        if($currentBookings + $playersCount > $field->capacity){
            return response()->json([
                'status'=>false,
                'message'=>'عدد اللاعبين في هذه الفترة مكتمل'
            ],400);
        }

        $price = $period->price_per_player * $playersCount;

        // 1. إنشاء الحجز في قاعدة البيانات
        $booking = FieldBooking::create([
            ...$data,
            'players_count'=>$playersCount,
            'price'=>$price
        ]);

        // 2. إعداد بيانات QR Code وتوليد الملف
        $qrData = [
            'booking_id' => $booking->id,
            'field_name' => $field->name,
            'date' => $booking->date,
            'period' => $period->start_time.' - '.$period->end_time,
            'players_count' => $playersCount,
            'price' => $price
        ];

        $qrCodeFileName = 'booking_'.$booking->id.'_'.Str::random(6).'.png';
        $qrCodePath = public_path('qrcodes/'.$qrCodeFileName);

        if(!file_exists(public_path('qrcodes'))){
            mkdir(public_path('qrcodes'), 0755, true);
        }

        // توليد وحفظ ملف QR Code
        QrCode::format('png')->size(300)->generate(json_encode($qrData), $qrCodePath);

        // 3. حفظ رابط الـ QR في الحجز باستخدام asset()
        $booking->update([
            'qr_code' => url('qrcodes/'.$qrCodeFileName) // تم تغيير url() إلى asset()
        ]);
        
        // 4. جلب نسخة جديدة ومحدثة من الحجز (fresh) لضمان ظهور حقل qr_code في الرد
        $updatedBooking = $booking->fresh()->load(['field','period']);

        return response()->json([
            'status'=>true,
            'message'=>'تم الحجز بنجاح',
            'data'=>$updatedBooking // استخدام الكائن المحدث
        ],201);
    }

    /**
     * التحقق من QR واسترجاع معلومات الحجز
     */
    public function verifyQr(Request $request)
    {
        $data = $request->validate([
            'qr_code_url' => 'required|url'
        ]);

        $booking = FieldBooking::where('qr_code', $data['qr_code_url'])->first();

        if(!$booking){
            return response()->json([
                'status'=>false,
                'message'=>'QR غير صالح أو لا يوجد حجز مطابق'
            ],404);
        }

        return response()->json([
            'status'=>true,
            'data' => $booking->load(['field','period'])
        ]);
    }

    public function index()
    {
        $bookings = FieldBooking::with(['field', 'period'])->latest()->get();
        return response()->json([
            'status' => true,
            'data' => $bookings
        ]);
    }

    public function show($id)
    {
        $booking = FieldBooking::with(['field', 'period'])->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $booking
        ]);
    }
}
