<?php

namespace App\Models;

use App\Enums\TaskMediaType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_submission_id', 'type', 'path'])]
class TaskSubmissionMedia extends Model
{
    protected function casts(): array
    {
        return [
            'type' => TaskMediaType::class,
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(TaskSubmission::class, 'task_submission_id');
    }

    public function url(): string
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->path);
    }
}
