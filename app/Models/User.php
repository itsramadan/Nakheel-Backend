<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * حساب المهندس الزراعي (staff). الدخول برقم الموبايل + OTP فقط.
 */
#[Fillable(['name', 'phone', 'role', 'phone_verified_at', 'fcm_token'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'phone_verified_at' => 'datetime',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class, 'created_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(FarmTask::class, 'created_by');
    }
}
