<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Engineer\CompleteVisitRequest;
use App\Http\Requests\Engineer\StoreVisitRequest;
use App\Http\Resources\VisitResource;
use App\Models\Farm;
use App\Models\Visit;
use App\Notifications\VisitStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    /**
     * شاشة "الزيارات" - فلتر بالحالة وفلتر بالمزرعة (وفلتر بالعميل زي ما كان).
     */
    public function index(Request $request): JsonResponse
    {
        $visits = Visit::query()
            ->with(['farm' => fn ($q) => $q->with(['nextVisit', 'client'])->withCount('openTasks')])
            ->when($request->filled('farm_id'), fn ($q) => $q->where('farm_id', $request->integer('farm_id')))
            ->when(
                $request->filled('status') && $request->query('status') !== 'all',
                fn ($q) => $q->where('status', $request->query('status'))
            )
            ->when(
                $request->filled('client_id'),
                fn ($q) => $q->whereHas('farm', fn ($f) => $f->where('client_id', $request->integer('client_id')))
            )
            ->orderBy('scheduled_date')
            ->get();

        return response()->json(['data' => VisitResource::collection($visits)]);
    }

    public function store(StoreVisitRequest $request, Farm $farm): JsonResponse
    {
        $visit = Visit::create([
            ...$request->validated(),
            'farm_id' => $farm->id,
            'created_by' => $request->user()->id,
            'status' => VisitStatus::Scheduled->value,
        ]);

        $visit->load(['farm' => fn ($q) => $q->with(['nextVisit', 'client'])->withCount('openTasks')]);

        $visit->farm->client?->notify(new VisitStatusChanged($visit, 'scheduled'));

        return response()->json([
            'data' => new VisitResource($visit),
        ], 201);
    }

    /**
     * "تفاصيل الزيارة" عند المهندس.
     */
    public function show(Visit $visit): JsonResponse
    {
        $visit->load(['farm' => fn ($q) => $q->with(['nextVisit', 'client'])->withCount('openTasks')]);

        return response()->json(['data' => new VisitResource($visit)]);
    }

    public function complete(CompleteVisitRequest $request, Visit $visit): JsonResponse
    {
        $this->guardEditable($visit);

        $visit->update([
            ...$request->validated(),
            'status' => VisitStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $visit->load(['farm' => fn ($q) => $q->with(['nextVisit', 'client'])->withCount('openTasks')]);

        $visit->farm->client?->notify(new VisitStatusChanged($visit, 'completed'));

        return response()->json([
            'data' => new VisitResource($visit),
        ]);
    }

    public function cancel(Visit $visit): JsonResponse
    {
        $this->guardEditable($visit);

        $visit->update(['status' => VisitStatus::Cancelled->value]);

        $visit->load(['farm' => fn ($q) => $q->with(['nextVisit', 'client'])->withCount('openTasks')]);

        $visit->farm->client?->notify(new VisitStatusChanged($visit, 'cancelled'));

        return response()->json([
            'data' => new VisitResource($visit),
        ]);
    }

    private function guardEditable(Visit $visit): void
    {
        if ($visit->status !== VisitStatus::Scheduled) {
            throw ValidationException::withMessages([
                'visit' => 'الزيارة دي خلصت بالفعل (تمت أو ملغاة).',
            ]);
        }
    }
}
