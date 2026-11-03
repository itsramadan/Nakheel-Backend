<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Visit */
class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'is_upcoming_soon' => $this->isUpcomingSoon(),

            // ملاحظة اختيارية بتُدخل وقت "تمت الزيارة" بس (مش نفس notes الإنشاء).
            'report_note' => $this->report_note,
            'completed_at' => $this->completed_at?->toIso8601String(),

            // تفاصيل المزرعة كاملة (نفس فورم "إضافة مزرعة") - عشان شاشة تفاصيل
            // الزيارة عند المهندس والمزارع تعرضها كاملة، بالإضافة لبيانات
            // العميل (client) اللي بقت جواها. تمشيًا مع نفس اللي عملناه في
            // TaskResource.
            'farm' => new FarmResource($this->whenLoaded('farm')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
