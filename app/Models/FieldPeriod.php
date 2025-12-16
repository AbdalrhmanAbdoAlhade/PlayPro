<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldPeriod extends Model
{
    protected $fillable = [
        'field_id',
        'start_time',
        'end_time',
        'price_per_player',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function bookings()
{
    return $this->hasMany(FieldBooking::class, 'period_id');
}

}


