<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    protected $appends = ['images_urls'];

    public function getImagesUrlsAttribute()
    {
        if (!$this->images) {
            return [];
        }

        return collect($this->images)->map(function ($image) {
            return asset('storage/' . $image);
        });
    }
}
