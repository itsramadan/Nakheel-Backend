<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "الرئيسية" عند المزارع. حاليًا بترجّع بيانات الزيارات بس (تنبيه لو فيه
 * زيارة قريبة) - نفس فكرة `upcoming_visits_count` / `nearest_visit` عند
 * المهندس. لو محتاجين نضيف بيانات تانية للهوم (تاسكات، مزارع...) قولّي.
 */
class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->user()->id;

        $nearestVisit = Visit::query()
            ->whereHas('farm', fn ($q) => $q->where('client_id', $clientId))
            ->where('status', VisitStatus::Scheduled->value)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->with(['farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks')])
            ->first();

        return response()->json([
            'upcoming_visits_count' => Visit::query()
                ->whereHas('farm', fn ($q) => $q->where('client_id', $clientId))
                ->where('status', VisitStatus::Scheduled->value)
                ->whereDate('scheduled_date', '>=', now()->toDateString())
                ->count(),
            'nearest_visit' => $nearestVisit ? new VisitResource($nearestVisit) : null,
        ]);
    }
}
