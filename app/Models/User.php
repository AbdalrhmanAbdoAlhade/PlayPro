<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role', // Admin, Coach, Owner, User
        'avatar',
         'status',
        'registration_role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // ملاعب هذا الـ Owner
    public function fields()
    {
        return $this->hasMany(Field::class, 'owner_id');
    }

    // الحجزات الخاصة بالمستخدم
    public function bookings()
    {
        return $this->hasMany(FieldBooking::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin()
    {
        return $this->role === 'Admin';
    }

    public function isOwner()
    {
        return $this->role === 'Owner';
    }

    public function isCoach()
    {
        return $this->role === 'Coach';
    }

    public function isUser()
    {
        return $this->role === 'User';
    }


    
}
