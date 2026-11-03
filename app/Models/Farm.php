<?php

namespace App\Models;

use App\Enums\SubscriptionCycle;
use App\Enums\TaskStatus;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'client_id', 'created_by', 'name', 'palm_count', 'area', 'location',
    'variety_type', 'palm_age_years', 'planted_at', 'soil_type', 'water_source',
    'water_analysis', 'general_notes',
    'subscription_cycle', 'subscription_next_payment_date',
])]
class Farm extends Model
{
    /** @use HasFactory<\Database\Factories\FarmFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'planted_at' => 'date',
            'subscription_cycle' => SubscriptionCycle::class,
            'subscription_next_payment_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function varieties(): HasMany
    {
        return $this->hasMany(FarmVariety::class)->orderBy('sort_order');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(FarmTask::class);
    }

    /**
     * تاسكات "مفتوحة" (لسه مش approved) - العدد اللي بيظهر كـ "تاسكات مفتوحة"
     * على كارت المزرعة (الرئيسية عند المزارع، وتفاصيل العميل عند المهندس).
     */
    public function openTasks(): HasMany
    {
        return $this->tasks()->where('status', '!=', TaskStatus::Approved->value);
    }

    /**
     * أقرب زيارة قادمة (اللي بتتعرض كـ "الزيارة القادمة" في تفاصيل المزرعة).
     */
    public function nextVisit(): HasOne
    {
        return $this->hasOne(Visit::class)
            ->ofMany(
                ['scheduled_date' => 'min'],
                fn ($query) => $query->where('status', VisitStatus::Scheduled->value)
                    ->whereDate('scheduled_date', '>=', now()->toDateString())
            );
    }
}
