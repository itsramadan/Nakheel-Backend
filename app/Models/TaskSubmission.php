<?php

namespace App\Models;

use App\Enums\TaskSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['task_id', 'note', 'status', 'review_note', 'reviewed_by', 'reviewed_at', 'submitted_at'])]
class TaskSubmission extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TaskSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(FarmTask::class, 'task_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(TaskSubmissionMedia::class);
    }
}
