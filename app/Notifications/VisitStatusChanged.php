<?php

namespace App\Notifications;

use App\Models\Visit;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * للمزارع: تغيّرت حالة زيارة (اتحددت/تمت/اتلغت). نفس الكلاس للتلات حالات
 * بفرق $status بس - عشان مانكررش كلاسات لكل حالة.
 */
class VisitStatusChanged extends Notification
{
    use Queueable;

    /** @param 'scheduled'|'completed'|'cancelled' $status */
    public function __construct(private readonly Visit $visit, private readonly string $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'visit_'.$this->status,
            'title' => $this->title(),
            'body' => $this->visit->farm->name,
            'visit_id' => $this->visit->id,
            'farm_id' => $this->visit->farm_id,
            'status' => $this->status,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->visit->farm->name,
            'data' => [
                'type' => 'visit_'.$this->status,
                'visit_id' => $this->visit->id,
                'farm_id' => $this->visit->farm_id,
                'status' => $this->status,
            ],
        ];
    }

    private function title(): string
    {
        return match ($this->status) {
            'scheduled' => 'تم تحديد زيارة جديدة',
            'completed' => 'تمت الزيارة',
            'cancelled' => 'تم إلغاء الزيارة',
            default => 'تحديث على زيارتك',
        };
    }
}
