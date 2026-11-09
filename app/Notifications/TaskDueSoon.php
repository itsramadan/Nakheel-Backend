<?php

namespace App\Notifications;

use App\Models\FarmTask;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * للمزارع: تذكير إن تاسك قرب معاده (بكرة) ولسه معلّق - بيتبعت من
 * SendReminderNotifications (يوميًا عن طريق Laravel Scheduler).
 */
class TaskDueSoon extends Notification
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
            'type' => 'task_due_soon',
            'title' => 'تاسك قرب معاده: '.$this->task->title,
            'body' => $this->task->farm->name.' - موعد التسليم بكرة',
            'task_id' => $this->task->id,
            'farm_id' => $this->task->farm_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'تاسك قرب معاده: '.$this->task->title,
            'body' => $this->task->farm->name.' - موعد التسليم بكرة',
            'data' => [
                'type' => 'task_due_soon',
                'task_id' => $this->task->id,
                'farm_id' => $this->task->farm_id,
            ],
        ];
    }
}
