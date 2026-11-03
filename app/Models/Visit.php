<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['farm_id', 'created_by', 'title', 'scheduled_date', 'notes', 'status', 'report_note', 'completed_at'])]
class Visit extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'status' => VisitStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * بنعتبر الزيارة "قريبة" لو خلال الأسبوع الجاي - ده اللي بيحدد شارة
     * "قريبة" اللي شايفينها المهندس في شاشة الزيارات. سهل نغيّر الحد ده.
     */
    public function isUpcomingSoon(): bool
    {
        if ($this->status !== VisitStatus::Scheduled) {
            return false;
        }

        return $this->scheduled_date->isFuture()
            && $this->scheduled_date->diffInDays(now()) <= 7;
    }
}
