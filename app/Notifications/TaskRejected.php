<?php

namespace App\Notifications;

use App\Models\TaskSubmission;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** للمزارع: المهندس رفض الدليل المرسل، محتاج يبعت "محاولة جديدة". */
class TaskRejected extends Notification
{
    use Queueable;

    public function __construct(private readonly TaskSubmission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $task = $this->submission->task;

        return [
            'type' => 'task_rejected',
            'title' => 'اترفض: '.$task->title,
            'body' => $this->submission->review_note,
            'task_id' => $task->id,
            'farm_id' => $task->farm_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $task = $this->submission->task;

        return [
            'title' => 'اترفض: '.$task->title,
            'body' => $this->submission->review_note ?: 'محتاج تبعت محاولة جديدة.',
            'data' => [
                'type' => 'task_rejected',
                'task_id' => $task->id,
                'farm_id' => $task->farm_id,
            ],
        ];
    }
}
