<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'role',
        'phone_number',
        'is_active'
    ];

    protected $hidden = [
        'google_id',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Rol kontrol metodları
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isStore()
    {
        return $this->role === 'store';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    // İlişkiler
    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }
}
