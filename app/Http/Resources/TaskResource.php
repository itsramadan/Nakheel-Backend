<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\FarmTask */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $displayStatus = $this->displayStatus();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'due_date' => $this->due_date?->toDateString(),

            // الحالة التفصيلية الحقيقية (pending/submitted/approved/rejected)
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            // الحالة المبسطة المستخدمة في فلاتر القوائم (approved/pending/late)
            'display_status' => $displayStatus,
            'display_status_label' => match ($displayStatus) {
                'approved' => 'منتهي',
                'late' => 'متأخر',
                default => 'قيد الانتظار',
            },

            // تفاصيل المزرعة كاملة (نفس فورم "إضافة مزرعة" اللي المهندس دخلها) -
            // مش نسخة مصغّرة، عشان شاشة تفاصيل التاسك عند المزارع تعرضها كاملة.
            'farm' => new FarmResource($this->whenLoaded('farm')),

            // اتعمله رفض قبل كده وفيه محاولة جديدة اتبعتت (شارة "↺ محاولة جديدة")
            'is_retry' => $this->whenCounted('submissions', fn () => $this->submissions_count > 1),

            'latest_submission' => $this->whenLoaded(
                'latestSubmission',
                fn () => $this->latestSubmission ? new TaskSubmissionResource($this->latestSubmission) : null
            ),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
