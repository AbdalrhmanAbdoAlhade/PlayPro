<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FieldImage;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'size',
        'capacity',
        'latitude',
        'longitude',
        'city',
        'address',
        'description',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function periods()
    {
        return $this->hasMany(FieldPeriod::class);
    }
  public function images()
    {
        return $this->hasMany(FieldImage::class);
    }

    public function icon()
    {
        return $this->hasOne(FieldImage::class)
                    ->where('type', 'icon');
    }

    public function gallery()
    {
        return $this->hasMany(FieldImage::class)
                    ->where('type', 'gallery');
    }
      public function bookings()
    {
        return $this->hasMany(FieldBooking::class);
    }

}
