<?php

namespace App\Notifications;

use App\Models\FarmTask;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * للمهندس: المزارع بعت دليل تنفيذ (صور/فيديو/ملاحظة) على تاسك.
 * ("دليل جديد على: <تاسك>" في الديزاين)
 */
class TaskEvidenceSubmitted extends Notification
{
    use Queueable;

    public function __construct(private readonly FarmTask $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'evidence_submitted',
            'title' => 'دليل جديد على: '.$this->task->title,
            'body' => $this->task->farm->name,
            'task_id' => $this->task->id,
            'farm_id' => $this->task->farm_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'دليل جديد على: '.$this->task->title,
            'body' => $this->task->farm->name,
            'data' => [
                'type' => 'evidence_submitted',
                'task_id' => $this->task->id,
                'farm_id' => $this->task->farm_id,
            ],
        ];
    }
}
