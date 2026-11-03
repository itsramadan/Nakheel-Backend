<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Enums\TaskStatus;
use App\Enums\TaskSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Engineer\RejectSubmissionRequest;
use App\Http\Requests\Engineer\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskSubmissionResource;
use App\Models\Farm;
use App\Models\FarmTask;
use App\Notifications\TaskApproved;
use App\Notifications\TaskRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * قائمة التاسكات - نفس شاشة "التاسكات" (فلتر مزرعة + فلتر حالة).
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = FarmTask::query()
            ->with(['farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks')])
            ->withCount('submissions')
            ->when($request->filled('farm_id'), fn ($q) => $q->where('farm_id', $request->integer('farm_id')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when(
                $request->filled('status') && $request->query('status') !== 'all',
                fn ($q) => $q->displayStatus($request->query('status'))
            )
            ->latest('due_date')
            ->get();

        return response()->json(['data' => TaskResource::collection($tasks)]);
    }

    public function store(StoreTaskRequest $request, Farm $farm): JsonResponse
    {
        $task = FarmTask::create([
            ...$request->validated(),
            'farm_id' => $farm->id,
            'created_by' => $request->user()->id,
            'status' => TaskStatus::Pending->value,
        ]);

        return response()->json(['data' => new TaskResource($task->load(['farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks')]))], 201);
    }

    /**
     * "مراجعة التاسك" - تفاصيل التاسك + الدليل المرسل + تاريخ المحاولات.
     */
    public function show(FarmTask $task): JsonResponse
    {
        $task->load([
            'farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks'),
            'submissions.media',
        ]);

        return response()->json([
            'data' => new TaskResource($task),
            'submissions' => TaskSubmissionResource::collection($task->submissions),
        ]);
    }

    public function approve(Request $request, FarmTask $task): JsonResponse
    {
        $submission = $task->submissions()->where('status', TaskSubmissionStatus::PendingReview->value)->first();

        if (! $submission) {
            throw ValidationException::withMessages([
                'task' => 'لا يوجد دليل بانتظار المراجعة لهذا التاسك.',
            ]);
        }

        $submission->update([
            'status' => TaskSubmissionStatus::Approved->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $task->update(['status' => TaskStatus::Approved->value]);

        $task->farm->client?->notify(new TaskApproved($task));

        return response()->json(['data' => new TaskResource($task->load(['farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks')]))]);
    }

    public function reject(RejectSubmissionRequest $request, FarmTask $task): JsonResponse
    {
        $submission = $task->submissions()->where('status', TaskSubmissionStatus::PendingReview->value)->first();

        if (! $submission) {
            throw ValidationException::withMessages([
                'task' => 'لا يوجد دليل بانتظار المراجعة لهذا التاسك.',
            ]);
        }

        $submission->update([
            'status' => TaskSubmissionStatus::Rejected->value,
            'review_note' => $request->validated('review_note'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $task->update(['status' => TaskStatus::Rejected->value]);

        $task->farm->client?->notify(new TaskRejected($submission));

        return response()->json(['data' => new TaskResource($task->load(['farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks')]))]);
    }
}
