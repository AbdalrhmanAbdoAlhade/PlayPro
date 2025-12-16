<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_id',
        'period_id',
        'name',
        'phone',
        'email',
        'date',
        "players_count",
        'price',
        'qr_code',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function period()
    {
        return $this->belongsTo(FieldPeriod::class);
    }
}
