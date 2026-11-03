<?php

namespace App\Http\Resources;

use App\Enums\TaskCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Farm */
class FarmResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'palm_count' => $this->palm_count,
            'area' => $this->area === null ? null : (float) $this->area,
            'location' => $this->location,

            // "الصنف" - نص مباشر (مجدول/صعيدي/أو نص "غيره" المخصص)، بديل لنظام
            // الأصناف المتعددة القديم.
            'variety_type' => $this->variety_type,
            'palm_age_years' => $this->palm_age_years,
            'planted_at' => $this->planted_at?->toDateString(),
            'soil_type' => $this->soil_type,
            'water_source' => $this->water_source,
            'water_analysis' => $this->water_analysis,
            'general_notes' => $this->general_notes,

            'subscription' => [
                'cycle' => $this->subscription_cycle?->value,
                'cycle_label' => $this->subscription_cycle?->label(),
                'next_payment_date' => $this->subscription_next_payment_date?->toDateString(),
            ],

            'next_visit_date' => $this->whenLoaded(
                'nextVisit',
                fn () => $this->nextVisit?->scheduled_date?->toDateString()
            ),

            // عدد التاسكات "المفتوحة" (اللي لسه مش approved) - كارت "تاسكات مفتوحة".
            'open_tasks_count' => $this->whenCounted('openTasks'),

            // التاسكات مجمّعة حسب الخطة (تصنيف) - "الخطة – حسب التصنيف" في
            // تفاصيل المزرعة عند المهندس. كل خطة فيها عددها وقائمة تاسكاتها
            // (نفس شكل TaskResource المعتاد، فمش شكل جديد للتاسك نفسه).
            'plan_summary' => $this->whenLoaded('tasks', function () {
                // نسخة من المزرعة نفسها بس من غير علاقة "tasks" المحمّلة، عشان
                // لما TaskResource يعرض farm جواه (FarmResource كامل)، مبيرجعش
                // يحاول يبني plan_summary تاني على نفس المزرعة → recursion لا
                // نهائي (Maximum stack depth exceeded). باقي العلاقات
                // (client, nextVisit, openTasks count) فاضلة زي ما هي.
                $farmForTasks = (clone $this->resource)->unsetRelation('tasks');

                return collect(TaskCategory::cases())
                    ->map(function (TaskCategory $category) use ($farmForTasks) {
                        $tasks = $this->tasks->where('category', $category)->values();
                        $tasks->each(fn ($task) => $task->setRelation('farm', $farmForTasks));

                        return [
                            'category' => $category->value,
                            'category_label' => $category->label(),
                            'tasks_count' => $tasks->count(),
                            'tasks' => TaskResource::collection($tasks),
                        ];
                    })
                    ->filter(fn ($p) => $p['tasks_count'] > 0)
                    ->values();
            }),

            'client' => $this->whenLoaded('client', fn () => new ClientSummaryResource($this->client)),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
