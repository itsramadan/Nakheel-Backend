<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FarmResource;
use App\Http\Resources\VisitResource;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * "مزرعتي" / سويتشر "مزارعي" لو عنده أكتر من مزرعة.
     */
    public function index(Request $request): JsonResponse
    {
        $farms = Farm::query()
            ->where('client_id', $request->user()->id)
            ->with(['nextVisit'])
            ->withCount('openTasks')
            ->get();

        return response()->json(['data' => FarmResource::collection($farms)]);
    }

    public function show(Request $request, Farm $farm): JsonResponse
    {
        $this->authorizeFarm($request, $farm);

        $farm->load(['nextVisit', 'tasks'])->loadCount('openTasks');

        $lastVisits = $farm->visits()
            ->where('status', VisitStatus::Completed->value)
            ->latest('completed_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => new FarmResource($farm),
            'last_visits' => VisitResource::collection($lastVisits),
        ]);
    }

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        abort_if($farm->client_id !== $request->user()->id, 403, 'هذه المزرعة غير تابعة لحسابك.');
    }
}
