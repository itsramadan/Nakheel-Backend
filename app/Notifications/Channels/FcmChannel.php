<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

/**
 * قناة إرسال push notification فعلي عن طريق Firebase Cloud Messaging.
 * بتشتغل جنب قناة 'database' العادية (مش بديلة عنها) - كل إشعار بيتسجل
 * في جدول notifications زي ما هو، وكمان بيتبعت push لو:
 * - الموديل (User/Client) عنده fcm_token محفوظ.
 * - الإشعار نفسه عامل method اسمها toFcm().
 * - Firebase متظبط (service account key موجود - شوف AppServiceProvider).
 *
 * لو أي شرط من دول مش متحقق، بنرجع من غير ما نعمل حاجة (مفيش error)
 * عشان الإشعار يفضل يتسجل في الداتابيز حتى لو الـ push فشل أو Firebase
 * لسه مش متظبط.
 */
class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $token = $notifiable->fcm_token ?? null;

        if (! $token) {
            return;
        }

        if (! app()->bound(Messaging::class)) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        $data = array_map('strval', $payload['data'] ?? []);

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(FirebaseNotification::create($payload['title'], $payload['body']))
            ->withData($data);

        try {
            app(Messaging::class)->send($message);
        } catch (\Throwable $e) {
            Log::warning('FCM push failed: '.$e->getMessage(), [
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => $notification::class,
            ]);
        }
    }
}
