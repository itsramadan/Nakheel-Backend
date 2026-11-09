<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Enums\VisitStatus;
use App\Models\Farm;
use App\Models\FarmTask;
use App\Models\Visit;
use App\Notifications\SubscriptionExpiringEngineer;
use App\Notifications\SubscriptionRenewalDue;
use App\Notifications\TaskDueSoon;
use App\Notifications\TaskOverdue;
use App\Notifications\TaskOverdueEngineer;
use App\Notifications\VisitUpcoming;
use App\Notifications\VisitUpcomingEngineer;
use Illuminate\Console\Command;

/**
 * تذكيرات يومية (تاسكات مستحقة/متأخرة، زيارات قريبة، اشتراكات قرب تنتهي).
 * متسجّل في routes/console.php عشان يتشغل يوميًا عن طريق Laravel Scheduler
 * - محتاج cron entry واحد بس على السيرفر (شوف API_REFERENCE.md).
 *
 * كل حالة بتتحقق بتاريخ محدد بالظبط (مش "أقل من" مفتوح) عشان كل إشعار
 * يتبعت مرة واحدة بس، مش كل يوم لحد ما المشكلة تتحل.
 */
class SendReminderNotifications extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'يبعت تذكيرات يومية: تاسكات مستحقة بكرة/اتأخرت، زيارات قريبة، اشتراكات قرب تنتهي';

    public function handle(): int
    {
        $this->sendTaskDueSoon();
        $this->sendTaskOverdue();
        $this->sendVisitUpcoming();
        $this->sendSubscriptionRenewal();

        $this->info('تم إرسال التذكيرات.');

        return self::SUCCESS;
    }

    /** تاسكات معاد تسليمها بكرة ولسه pending. */
    private function sendTaskDueSoon(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        FarmTask::query()
            ->where('status', TaskStatus::Pending->value)
            ->whereDate('due_date', $tomorrow)
            ->with('farm.client')
            ->each(function (FarmTask $task) {
                $task->farm->client?->notify(new TaskDueSoon($task));
            });
    }

    /** تاسكات فات معادها "امبارح بالظبط" (يوم واحد بعد الاستحقاق) - تتبعت مرة واحدة. */
    private function sendTaskOverdue(): void
    {
        $yesterday = now()->subDay()->toDateString();

        FarmTask::query()
            ->whereIn('status', [TaskStatus::Pending->value, TaskStatus::Rejected->value])
            ->whereDate('due_date', $yesterday)
            ->with('farm.client', 'createdBy')
            ->each(function (FarmTask $task) {
                $task->farm->client?->notify(new TaskOverdue($task));
                $task->createdBy?->notify(new TaskOverdueEngineer($task));
            });
    }

    /** زيارات مجدولة بكرة - للمزارع وللمهندس اللي حددها. */
    private function sendVisitUpcoming(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        Visit::query()
            ->where('status', VisitStatus::Scheduled->value)
            ->whereDate('scheduled_date', $tomorrow)
            ->with('farm.client', 'createdBy')
            ->each(function (Visit $visit) {
                $visit->farm->client?->notify(new VisitUpcoming($visit));
                $visit->createdBy?->notify(new VisitUpcomingEngineer($visit));
            });
    }

    /** مزارع اشتراكها هيتجدد بعد ٣ أيام - للمزارع وللمهندس المسؤول. */
    private function sendSubscriptionRenewal(): void
    {
        $targetDate = now()->addDays(3)->toDateString();

        Farm::query()
            ->whereDate('subscription_next_payment_date', $targetDate)
            ->with('client', 'createdBy')
            ->each(function (Farm $farm) {
                $farm->client?->notify(new SubscriptionRenewalDue($farm));
                $farm->createdBy?->notify(new SubscriptionExpiringEngineer($farm));
            });
    }
}
