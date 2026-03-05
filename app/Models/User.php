<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\QueryBuilderTrait;
use App\Notifications\AdminResetPasswordNotification;
use App\Concerns\AuthorizationChecker;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\ResetPassword as DefaultResetPassword;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users'; // Make sure this matches your users table
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'username',
        'profile_image'
    ];

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
}
