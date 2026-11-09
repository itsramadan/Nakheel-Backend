<?php

namespace App\Notifications;

use App\Models\FarmTask;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** للمهندس: تاسك عند عميله فات معاده ولسه مش متسلم - متابعة. */
class TaskOverdueEngineer extends Notification
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
            'type' => 'task_overdue_engineer',
            'title' => 'تاسك متأخر عند '.$this->task->farm->name,
            'body' => $this->task->title,
            'task_id' => $this->task->id,
            'farm_id' => $this->task->farm_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'تاسك متأخر عند '.$this->task->farm->name,
            'body' => $this->task->title,
            'data' => [
                'type' => 'task_overdue_engineer',
                'task_id' => $this->task->id,
                'farm_id' => $this->task->farm_id,
            ],
        ];
    }
}
