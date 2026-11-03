<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    /**
     * "تفاصيل الزيارة" عند المزارع - عرض بس، من غير أي أكشن.
     */
    public function show(Request $request, Visit $visit): JsonResponse
    {
        $visit->loadMissing('farm');
        abort_if($visit->farm->client_id !== $request->user()->id, 403, 'هذه الزيارة غير تابعة لمزرعتك.');

        $visit->load(['farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks')]);

        return response()->json(['data' => new VisitResource($visit)]);
    }
}
