<?php

namespace App\Notifications;

use App\Models\Visit;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * للمزارع: تذكير إن زيارة المهندس بكرة - بيتبعت من SendReminderNotifications
 * (يوميًا عن طريق Laravel Scheduler).
 */
class VisitUpcoming extends Notification
{
    use Queueable;

    public function __construct(private readonly Visit $visit)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'visit_upcoming',
            'title' => 'زيارة المهندس بكرة',
            'body' => $this->visit->farm->name,
            'visit_id' => $this->visit->id,
            'farm_id' => $this->visit->farm_id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'زيارة المهندس بكرة',
            'body' => $this->visit->farm->name,
            'data' => [
                'type' => 'visit_upcoming',
                'visit_id' => $this->visit->id,
                'farm_id' => $this->visit->farm_id,
            ],
        ];
    }
}
