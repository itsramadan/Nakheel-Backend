<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Enums\TaskMediaType;
use App\Enums\TaskStatus;
use App\Enums\TaskSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreTaskSubmissionRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskSubmissionResource;
use App\Models\Farm;
use App\Models\FarmTask;
use App\Models\TaskSubmissionMedia;
use App\Notifications\TaskEvidenceSubmitted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * "تاسكاتي" لمزرعة واحدة بعينها، مع فلتر الحالة (الكل/منتهي/قيد الانتظار/متأخر).
     */
    public function index(Request $request, Farm $farm): JsonResponse
    {
        $this->authorizeFarm($request, $farm);

        $tasks = $farm->tasks()
            ->with([
                'farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks'),
                'latestSubmission.media',
            ])
            ->withCount('submissions')
            ->when(
                $request->filled('status') && $request->query('status') !== 'all',
                fn ($q) => $q->displayStatus($request->query('status'))
            )
            ->latest('due_date')
            ->get();

        return response()->json(['data' => TaskResource::collection($tasks)]);
    }

    /**
     * "كل تاسكاتي" في كل مزارعي مجتمعة (مش مربوطة بمزرعة واحدة) - لشاشة
     * "التاسكات" العامة عند المزارع. فلاتر اختيارية: farm_id / category /
     * status، نفس فلتر index() فوق بالظبط عشان تفضل متسقة.
     */
    public function indexAll(Request $request): JsonResponse
    {
        $tasks = FarmTask::query()
            ->whereHas('farm', fn ($q) => $q->where('client_id', $request->user()->id))
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

    /**
     * "تفاصيل التاسك" من ناحية المزارع.
     */
    public function show(Request $request, FarmTask $task): JsonResponse
    {
        $this->authorizeFarm($request, $task->farm);

        $task->load([
            'farm' => fn ($q) => $q->with('nextVisit')->withCount('openTasks'),
            'latestSubmission.media',
        ]);

        return response()->json(['data' => new TaskResource($task)]);
    }

    /**
     * إرسال دليل تنفيذ (صور/فيديو + ملاحظة اختيارية) - أو "محاولة جديدة"
     * بعد الرفض.
     */
    public function storeSubmission(StoreTaskSubmissionRequest $request, FarmTask $task): JsonResponse
    {
        $this->authorizeFarm($request, $task->farm);

        if (in_array($task->status, [TaskStatus::Submitted, TaskStatus::Approved], true)) {
            throw ValidationException::withMessages([
                'task' => 'التاسك ده بالفعل بانتظار المراجعة أو تم اعتماده.',
            ]);
        }

        $submission = DB::transaction(function () use ($request, $task) {
            $submission = $task->submissions()->create([
                'note' => $request->validated('note'),
                'status' => TaskSubmissionStatus::PendingReview->value,
                'submitted_at' => now(),
            ]);

            foreach ($request->file('media', []) as $file) {
                $path = $file->store('task-submissions', 'public');

                TaskSubmissionMedia::create([
                    'task_submission_id' => $submission->id,
                    'type' => str_starts_with($file->getMimeType(), 'video') ? TaskMediaType::Video->value : TaskMediaType::Image->value,
                    'path' => $path,
                ]);
            }

            $task->update(['status' => TaskStatus::Submitted->value]);

            return $submission;
        });

        $task->createdBy?->notify(new TaskEvidenceSubmitted($task));

        return response()->json(['data' => new TaskSubmissionResource($submission->load('media'))], 201);
    }

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        abort_if($farm->client_id !== $request->user()->id, 403, 'هذا التاسك غير تابع لمزرعتك.');
    }
}
