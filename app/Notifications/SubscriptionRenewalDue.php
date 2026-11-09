<?php

namespace App\Notifications;

use App\Models\Farm;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * للمزارع: اشتراك مزرعته قرب ميعاد تجديده/دفعه - بيتبعت من
 * SendReminderNotifications (قبل الميعاد بـ 3 أيام).
 */
class SubscriptionRenewalDue extends Notification
{
    use Queueable;

    public function __construct(private readonly Farm $farm)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'subscription_renewal_due',
            'title' => 'تجديد الاشتراك قريب',
            'body' => $this->farm->name.' - '.$this->farm->subscription_next_payment_date?->format('Y-m-d'),
            'farm_id' => $this->farm->id,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'تجديد الاشتراك قريب',
            'body' => $this->farm->name.' - '.$this->farm->subscription_next_payment_date?->format('Y-m-d'),
            'data' => [
                'type' => 'subscription_renewal_due',
                'farm_id' => $this->farm->id,
            ],
        ];
    }
}
