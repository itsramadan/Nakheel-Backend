<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * نسخة مصغّرة من المزرعة (للقوائم: شيبس العميل، سويتشر مزارع المزارع...).
 * @mixin \App\Models\Farm
 */
class FarmSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'palm_count' => $this->palm_count,
            'next_visit_date' => $this->whenLoaded('nextVisit', fn () => $this->nextVisit?->scheduled_date?->toDateString()),
            // عدد التاسكات "المفتوحة" (اللي لسه مش approved) - كارت "تاسكات مفتوحة".
            'open_tasks_count' => $this->whenCounted('openTasks'),
        ];
    }
}
