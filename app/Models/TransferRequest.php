<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Field;
use App\Models\FieldBooking; 
use App\Models\FieldPeriod; 

class TransferRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_booking_id',
        'target_field_id',
        'target_period_id',
        'status',
        'notes',
    ];
    
    // تعريف العلاقات
    
    // يشير إلى المستخدم الذي قام بتقديم الطلب
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // يشير إلى الحجز الحالي الذي يطلب نقله
    public function currentBooking()
    {
        return $this->belongsTo(FieldBooking::class, 'current_booking_id'); 
    }
    
    // يشير إلى الملعب المستهدف الجديد
    public function targetField()
    {
        return $this->belongsTo(Field::class, 'target_field_id');
    }
    
    // يشير إلى الفترة/التوقيت المستهدف الجديد
    public function targetPeriod()
    {
        return $this->belongsTo(FieldPeriod::class, 'target_period_id');
    }
}