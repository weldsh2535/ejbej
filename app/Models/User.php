<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users'; // Make sure this matches your users table
    use HasApiTokens, Notifiable; // Add HasApiTokens here
    protected $appends = ['avatar_url', 'full_name'];

    protected $fillable = [
        'first_name',
        'last_name',
        'full_name',
        'email',
        'username',
        'password',
        'profile_image',
        'email_verified_at',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return url('uploads/avatars/' . $this->avatar);
        }

        // Return default avatar if you have one
        // return url('uploads/avatars/default-avatar.png');
        return null;
    }


    // Accessor for full_name
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the products for the user
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }


    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorites')
            ->withTimestamps()
            ->orderBy('favorites.created_at', 'desc');
    }
}
