<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * حساب العميل/المزارع. بيتضاف من المهندس، ورقم جواله هو حساب دخوله في
 * التطبيق (نفس شاشة تسجيل الدخول بتاعة المهندس).
 */
#[Fillable(['name', 'phone', 'city', 'created_by', 'phone_verified_at', 'fcm_token'])]
class Client extends Model implements AuthenticatableContract
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use Authenticatable, HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }
}
