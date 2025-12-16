<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'type',
        'quantity',
        'image',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }


   public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}


    public function orders()
{
    return $this->belongsToMany(Order::class, 'order_items')
                ->withPivot(['quantity', 'price', 'total'])
                ->withTimestamps();
}

}
