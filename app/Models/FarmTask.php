<?php

namespace App\Models;

use App\Enums\TaskCategory;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * جدول `tasks`. سميناه FarmTask في الكود عشان منتلخبطش مع مفاهيم Laravel
 * التانية (زي جدولة الأوامر Console\Scheduling).
 */
#[Fillable(['farm_id', 'created_by', 'category', 'title', 'description', 'due_date', 'status'])]
class FarmTask extends Model
{
    protected $table = 'tasks';

    protected function casts(): array
    {
        return [
            'category' => TaskCategory::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'task_id')->latest('submitted_at');
    }

    public function latestSubmission(): HasOne
    {
        return $this->hasOne(TaskSubmission::class, 'task_id')->ofMany('submitted_at', 'max');
    }

    /**
     * الحالة المبسطة اللي بتتفلتر بيها القوائم في الديزاين (نفس الـ 3
     * فلاتر: منتهي / قيد الانتظار / متأخر). الحالة التفصيلية الحقيقية
     * (pending/submitted/approved/rejected) موجودة في `status` وبتتعرض
     * في شاشة تفاصيل التاسك (زي ملاحظة الرفض).
     *
     * - approved                              => 'approved' (منتهي)
     * - (pending أو rejected) وعدى معاد الاستحقاق => 'late' (متأخر)
     * - أي حاجة تانية (submitted، أو لسه في معادها)  => 'pending' (قيد الانتظار)
     */
    public function displayStatus(): string
    {
        if ($this->status === TaskStatus::Approved) {
            return 'approved';
        }

        $isOverdue = $this->due_date->isPast() && ! $this->due_date->isToday();

        if ($isOverdue && in_array($this->status, [TaskStatus::Pending, TaskStatus::Rejected], true)) {
            return 'late';
        }

        return 'pending';
    }

    /**
     * تاسكات "متأخر" فقط - نفس شرط displayStatus() بس كـ query بدل PHP،
     * مستخدمة في لوحة المهندس وفلتر التاسكات.
     */
    public function scopeLate(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [TaskStatus::Pending->value, TaskStatus::Rejected->value])
            ->whereDate('due_date', '<', now()->toDateString());
    }

    /**
     * فلتر بالحالة المبسطة (approved/pending/late) نفس اللي في التطبيق.
     */
    public function scopeDisplayStatus(Builder $query, string $bucket): Builder
    {
        return match ($bucket) {
            'approved' => $query->where('status', TaskStatus::Approved->value),
            'late' => $query->late(),
            'pending' => $query->where('status', '!=', TaskStatus::Approved->value)
                ->where(function (Builder $q) {
                    $q->where('status', TaskStatus::Submitted->value)
                        ->orWhereDate('due_date', '>=', now()->toDateString());
                }),
            default => $query,
        };
    }
}
