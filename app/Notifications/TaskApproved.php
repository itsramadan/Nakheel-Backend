<?php

namespace App\Notifications;

use App\Models\FarmTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** للمزارع: المهندس اعتمد التاسك. */
class TaskApproved extends Notification
{
    use Queueable;

    public function __construct(private readonly FarmTask $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'task_approved',
            'title' => 'تم اعتماد: '.$this->task->title,
            'body' => $this->task->farm->name,
            'task_id' => $this->task->id,
            'farm_id' => $this->task->farm_id,
        ];
    }
}
