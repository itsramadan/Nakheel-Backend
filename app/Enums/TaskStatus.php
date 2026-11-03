<?php

namespace App\Enums;

/**
 * Stored status of a task. Note: "متأخر" (late) is NOT stored here — it is
 * computed on the fly (pending/rejected + due_date in the past). See
 * App\Models\FarmTask::displayStatus().
 */
enum TaskStatus: string
{
    case Pending = 'pending';       // لسه المزارع ما بعتش دليل
    case Submitted = 'submitted';   // بانتظار مراجعة المهندس
    case Approved = 'approved';     // منتهي / تم الاعتماد
    case Rejected = 'rejected';     // رفضه المهندس، محتاج إعادة إرسال

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Submitted => 'بانتظار مراجعة المهندس',
            self::Approved => 'منتهي',
            self::Rejected => 'مرفوض',
        };
    }
}
