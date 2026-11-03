<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Enums\TaskStatus;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Resources\VisitResource;
use App\Models\Client;
use App\Models\Farm;
use App\Models\FarmTask;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;

/**
 * "لوحة اليوم" - نفس شاشة الرئيسية للمهندس في الديزاين.
 */
class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $nearestVisit = Visit::query()
            ->where('status', VisitStatus::Scheduled->value)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->with('farm.client')
            ->first();

        // بترتب حسب الأولوية مش تاريخ الإنشاء: بانتظار المراجعة (submitted) الأول
        // لأنها فعل مباشر مطلوب من المهندس، بعدين المتأخر (يحتاج متابعة)، بعدين
        // العادي (pending)، وآخر حاجة المعتمد (approved) لأنه خلص وملوش داعي
        // يظهر في "افعل الآن". داخل كل مستوى، الأحدث إنشاءً الأول.
        $latestTasks = FarmTask::query()
            ->with('farm')
            ->withCount('submissions')
            ->orderByRaw("
                CASE
                    WHEN status = ? THEN 0
                    WHEN status IN (?, ?) AND due_date < ? THEN 1
                    WHEN status IN (?, ?) THEN 2
                    ELSE 3
                END
            ", [
                TaskStatus::Submitted->value,
                TaskStatus::Pending->value, TaskStatus::Rejected->value, now()->toDateString(),
                TaskStatus::Pending->value, TaskStatus::Rejected->value,
            ])
            ->latest('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'clients_count' => Client::count(),
            'farms_count' => Farm::count(),
            'late_tasks_count' => FarmTask::query()->late()->count(),
            'upcoming_visits_count' => Visit::query()
                ->where('status', VisitStatus::Scheduled->value)
                ->whereDate('scheduled_date', '>=', now()->toDateString())
                ->count(),
            'nearest_visit' => $nearestVisit ? new VisitResource($nearestVisit) : null,
            'latest_tasks' => TaskResource::collection($latestTasks),
        ]);
    }
}
