<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Coach extends Model
{
    protected $fillable = [
        'user_id',
        'field_id',
        'name',
        'age',
        'description',
        'experience_years',
        'images',
        'cv_file',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    protected $appends = [
        'images_urls',
        'cv_url',
    ];

    // 🔗 صور بلينك كامل
    public function getImagesUrlsAttribute()
    {
        if (!$this->images) {
            return [];
        }

        return collect($this->images)->map(function ($image) {
            return Storage::disk('public')->url($image);
        });
    }

    public function getCvUrlAttribute()
    {
        if (!$this->cv_file) {
            return null;
        }

        return Storage::disk('public')->url($this->cv_file);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }
}
