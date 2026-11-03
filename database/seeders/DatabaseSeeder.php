<?php

namespace Database\Seeders;

use App\Enums\SubscriptionCycle;
use App\Enums\TaskCategory;
use App\Enums\TaskMediaType;
use App\Enums\TaskStatus;
use App\Enums\TaskSubmissionStatus;
use App\Enums\VisitStatus;
use App\Models\Client;
use App\Models\Farm;
use App\Models\FarmTask;
use App\Models\TaskSubmission;
use App\Models\TaskSubmissionMedia;
use App\Models\User;
use App\Models\Visit;
use App\Notifications\TaskApproved;
use App\Notifications\TaskEvidenceSubmitted;
use App\Notifications\TaskRejected;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية بتحاكي البيانات اللي في ديزاين التطبيق (Nakheel.html)
 * عشان تكون جاهزة للتجربة من الفلاتر على طول.
 *
 * أرقام الدخول التجريبية (كود التفعيل ثابت 1111 لحد ما نربط مزود SMS):
 *   - المهندس:            01000000000
 *   - مزارع بمزرعة واحدة:   01011122233
 *   - مزارع بأكتر من مزرعة: 01044455566
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $engineer = User::create([
            'name' => 'م. أحمد',
            'phone' => '01000000000',
            'role' => 'engineer',
            'phone_verified_at' => now(),
        ]);

        $clients = [
            ['name' => 'أحمد الشمري', 'phone' => '01011122233', 'city' => 'الفيوم'],
            ['name' => 'سالم المزروعي', 'phone' => '01022233344', 'city' => 'أسوان'],
            ['name' => 'خليفة النعيمي', 'phone' => '01033344455', 'city' => 'المنيا'],
            ['name' => 'محمد الكعبي', 'phone' => '01044455566', 'city' => 'البحيرة'],
        ];

        $clientModels = [];
        foreach ($clients as $data) {
            $clientModels[$data['phone']] = Client::create([
                ...$data,
                'created_by' => $engineer->id,
                'phone_verified_at' => now(),
            ]);
        }

        $farms = [
            'الروضة' => [
                'client' => '01011122233',
                'palm_count' => 320,
                'area' => 45,
                'location' => 'الفيوم',
                'variety_type' => 'مجدول',
                'palm_age_years' => 12,
                'soil_type' => 'طينية',
                'water_source' => 'بئر ارتوازي',
                'water_analysis' => 'نسبة الأملاح ٨٠٠ جزء في المليون - ملوحة منخفضة.',
                'general_notes' => 'الأرض مستوية وسهلة الوصول لكل الأشجار.',
                'next_payment_days' => 14,
            ],
            'الواحة الخضراء' => [
                'client' => '01022233344',
                'palm_count' => 260,
                'area' => 38,
                'location' => 'أسوان',
                'variety_type' => 'صعيدي',
                'palm_age_years' => 8,
                'soil_type' => 'رملية',
                'water_source' => 'ترعة',
                'water_analysis' => null,
                'general_notes' => null,
                'next_payment_days' => 20,
            ],
            'النخيل الذهبي' => [
                'client' => '01033344455',
                'palm_count' => 190,
                'area' => 27,
                'location' => 'المنيا',
                // مثال على حالة "غيره" (نص مخصص مش من الاختيارين التانيين).
                'variety_type' => 'سماني',
                'palm_age_years' => 15,
                'soil_type' => 'طينية رملية',
                'water_source' => 'بئر',
                'water_analysis' => null,
                'general_notes' => 'فيه جزء من الأرض منخفض شوية عن باقي المزرعة.',
                'next_payment_days' => 25,
            ],
            'الواحة الشرقية' => [
                'client' => '01044455566',
                'palm_count' => 210,
                'area' => 33,
                'location' => 'أبو حماد، حي الزراعات',
                'variety_type' => 'مجدول',
                'palm_age_years' => 10,
                'soil_type' => 'رملية',
                'water_source' => 'بئر ارتوازي',
                'water_analysis' => 'نسبة الأملاح متوسطة - محتاجة متابعة الموسم الجاي.',
                'general_notes' => null,
                'next_payment_days' => 18,
            ],
            'بن راشد' => [
                'client' => '01044455566',
                'palm_count' => 150,
                'area' => 22,
                'location' => 'البحيرة',
                'variety_type' => 'زغلول',
                'palm_age_years' => 20,
                'soil_type' => 'طينية',
                'water_source' => 'ترعة',
                'water_analysis' => null,
                'general_notes' => 'مزرعة قديمة، الأشجار كبيرة.',
                'next_payment_days' => 30,
            ],
        ];

        $farmModels = [];
        foreach ($farms as $name => $data) {
            $farm = Farm::create([
                'client_id' => $clientModels[$data['client']]->id,
                'created_by' => $engineer->id,
                'name' => 'مزرعة '.$name,
                'palm_count' => $data['palm_count'],
                'area' => $data['area'],
                'location' => $data['location'],
                'variety_type' => $data['variety_type'],
                'palm_age_years' => $data['palm_age_years'],
                'planted_at' => now()->subYears($data['palm_age_years']),
                'soil_type' => $data['soil_type'],
                'water_source' => $data['water_source'],
                'water_analysis' => $data['water_analysis'],
                'general_notes' => $data['general_notes'],
                'subscription_cycle' => SubscriptionCycle::Monthly->value,
                'subscription_next_payment_date' => now()->addDays($data['next_payment_days']),
            ]);

            $farmModels[$name] = $farm;
        }

        // ---------------------------------------------------------------
        // الزيارات
        // ---------------------------------------------------------------
        Visit::create([
            'farm_id' => $farmModels['الروضة']->id,
            'created_by' => $engineer->id,
            'title' => 'تقليم وفحص عام',
            'scheduled_date' => now()->subDays(14),
            'status' => VisitStatus::Completed->value,
            'report_note' => 'تم تقليم السعف الجاف، ورصد إصابة محدودة بحفار عذوق النخيل في ٦ أشجار من صنف البرحي.',
            'completed_at' => now()->subDays(14),
        ]);

        Visit::create([
            'farm_id' => $farmModels['الروضة']->id,
            'created_by' => $engineer->id,
            'title' => 'زيارة متابعة دورية',
            'scheduled_date' => now()->addDays(20),
            'notes' => 'متابعة نتيجة الفحص اللي فات والتأكد من عدم انتشار الإصابة.',
            'status' => VisitStatus::Scheduled->value,
        ]);

        Visit::create([
            'farm_id' => $farmModels['الواحة الخضراء']->id,
            'created_by' => $engineer->id,
            'title' => 'فحص دوري بعد التسميد',
            'scheduled_date' => now()->addDays(9),
            'status' => VisitStatus::Scheduled->value,
        ]);

        Visit::create([
            'farm_id' => $farmModels['النخيل الذهبي']->id,
            'created_by' => $engineer->id,
            'title' => 'فحص شامل للتربة والأشجار',
            'scheduled_date' => now()->addDays(15),
            'status' => VisitStatus::Scheduled->value,
        ]);

        Visit::create([
            'farm_id' => $farmModels['الواحة الشرقية']->id,
            'created_by' => $engineer->id,
            'title' => 'زيارة متابعة إصابة سوسة النخيل',
            'scheduled_date' => now()->addDays(3), // "قريبة" - أقرب زيارة قادمة
            'notes' => 'متابعة التقرير المرفوع من المزارع عن الإصابة.',
            'status' => VisitStatus::Scheduled->value,
        ]);

        Visit::create([
            'farm_id' => $farmModels['بن راشد']->id,
            'created_by' => $engineer->id,
            'title' => 'زيارة دورية',
            'scheduled_date' => now()->addDays(11),
            'status' => VisitStatus::Scheduled->value,
        ]);

        // ---------------------------------------------------------------
        // التاسكات
        // ---------------------------------------------------------------

        // منتهي (متعمد ✓)
        $approvedTask = $this->makeTask(
            $farmModels['الروضة'], $engineer, TaskCategory::Spraying,
            'فحص عام للنخيل بعد الموسم', now()->subDays(20), TaskStatus::Approved,
            approvedNote: 'تم الفحص الشامل ولم يتم رصد أي إصابات جديدة.',
        );

        // بانتظار مراجعة المهندس (submitted) - ومعاها صور (قبل/بعد) عشان تجربة
        // عرض الميديا مع رد المزارع في تفاصيل التاسك.
        $pruningTask = $this->makeTask(
            $farmModels['الروضة'], $engineer, TaskCategory::Spraying,
            'تقليم السعف الجاف وإزالة البقايا', now()->subDays(3), TaskStatus::Submitted,
            pendingNote: 'تم تقليم كل السعف الجاف وتنظيف المنطقة حول الجذع، وتصوير النخلة قبل وبعد.',
        );

        $pruningSubmission = $pruningTask->submissions()->first();
        if ($pruningSubmission) {
            TaskSubmissionMedia::create([
                'task_submission_id' => $pruningSubmission->id,
                'type' => TaskMediaType::Image->value,
                'path' => 'task-submissions/demo-pruning-before.jpg',
            ]);
            TaskSubmissionMedia::create([
                'task_submission_id' => $pruningSubmission->id,
                'type' => TaskMediaType::Image->value,
                'path' => 'task-submissions/demo-pruning-after.jpg',
            ]);
        }

        // متأخر (pending، مفيش دليل اتبعت خالص)
        $this->makeTask(
            $farmModels['الروضة'], $engineer, TaskCategory::Spraying,
            'رصد إصابة محدودة بحفار عذوق النخيل', now()->subDays(2), TaskStatus::Pending,
        );

        // قيد الانتظار (pending، لسه مفيش دليل، بس معاده لسه ما جاش)
        $this->makeTask(
            $farmModels['الواحة الخضراء'], $engineer, TaskCategory::Fertilizing,
            'تسميد الأشجار حسب الجرعة الموصى بها', now()->addDays(3), TaskStatus::Pending,
        );

        // اتُرفض مرة وبعدين المزارع بعت "محاولة جديدة" (submitted تاني)
        $retryTask = $this->makeTask(
            $farmModels['الواحة الخضراء'], $engineer, TaskCategory::Spraying,
            'معالجة موضعية لسوسة النخيل الحمراء', now()->subDays(10), TaskStatus::Rejected,
        );
        TaskSubmission::create([
            'task_id' => $retryTask->id,
            'note' => null,
            'status' => TaskSubmissionStatus::Rejected->value,
            'review_note' => 'الصورة مش واضحة لمكان الفتحات — من فضلك ارفع صورة أقرب.',
            'reviewed_by' => $engineer->id,
            'reviewed_at' => now()->subDays(9),
            'submitted_at' => now()->subDays(9)->subHours(2),
        ]);
        TaskSubmission::create([
            'task_id' => $retryTask->id,
            'note' => 'رشيت المبيد المحدد على الفتحات الظاهرة وغطيتها بالطين زي ما اتقال.',
            'status' => TaskSubmissionStatus::PendingReview->value,
            'submitted_at' => now()->subDays(1),
        ]);
        $retryTask->update(['status' => TaskStatus::Submitted->value]);

        $this->makeTask(
            $farmModels['النخيل الذهبي'], $engineer, TaskCategory::Irrigation,
            'ري إضافي بعد المعالجة', now()->addDays(6), TaskStatus::Pending,
        );

        $this->makeTask(
            $farmModels['الواحة الشرقية'], $engineer, TaskCategory::Fertilizing,
            'تسميد الأشجار الشتوي', now()->addDays(10), TaskStatus::Pending,
        );

        $this->makeTask(
            $farmModels['الواحة الشرقية'], $engineer, TaskCategory::Spraying,
            'رفع تقرير إصابة بسوسة النخيل الحمراء', now()->addDays(5), TaskStatus::Pending,
        );

        $this->makeTask(
            $farmModels['بن راشد'], $engineer, TaskCategory::Irrigation,
            'ري الأشجار الجديدة', now()->addDays(8), TaskStatus::Pending,
        );

        // ---------------------------------------------------------------
        // تاسكات إضافية لتغطية كل حالة (عشان تجربة قبول/رفض بسهولة من Postman)
        // ---------------------------------------------------------------

        // بانتظار مراجعة المهندس #٢ - جاهز لتجربة "قبول"
        $this->makeTask(
            $farmModels['النخيل الذهبي'], $engineer, TaskCategory::Spraying,
            'فحص التربة ومعدلات الملوحة', now()->subDay(), TaskStatus::Submitted,
            pendingNote: 'أخدت عينة من التربة حوالين ٥ أشجار وبعت الصور المطلوبة.',
        );

        // بانتظار مراجعة المهندس #٣ - جاهز لتجربة "رفض"
        $this->makeTask(
            $farmModels['الواحة الشرقية'], $engineer, TaskCategory::Fertilizing,
            'تسميد ورقي إضافي بعد الفحص', now()->subDay(), TaskStatus::Submitted,
            pendingNote: 'رشيت السماد الورقي المطلوب على كل الأشجار المصابة.',
        );

        // متأخر #٢
        $this->makeTask(
            $farmModels['بن راشد'], $engineer, TaskCategory::Spraying,
            'فحص الأشجار المجاورة للإصابة', now()->subDays(5), TaskStatus::Pending,
        );

        // منتهي #٢
        $this->makeTask(
            $farmModels['الواحة الخضراء'], $engineer, TaskCategory::Spraying,
            'تقليم أولي قبل موسم الرطب', now()->subDays(25), TaskStatus::Approved,
            approvedNote: 'تم التقليم بالكامل والموقع نضيف.',
        );

        // مرفوض ولسه المزارع مبعتش محاولة جديدة (عكس حالة "↺ محاولة جديدة" اللي فوق)
        $rejectedTask = $this->makeTask(
            $farmModels['النخيل الذهبي'], $engineer, TaskCategory::Spraying,
            'معالجة حشرة القشريات على الجذوع', now()->subDays(4), TaskStatus::Rejected,
            reviewNote: 'الفيديو مقطوع في النص - ابعت فيديو كامل يوضح كل الجذع من فضلك.',
        );

        // ---------------------------------------------------------------
        // إشعارات تجريبية - بنستخدم نفس كلاسات الإشعار الحقيقية اللي بتتبعت
        // وقت التنفيذ الفعلي (تقديم دليل / اعتماد / رفض)، عشان GET
        // /notifications يرجع نتيجة حقيقية للتجربة على طول من غير ما تعدي
        // بالـ Postman على كل خطوة (تقديم دليل ثم اعتماد/رفض) الأول.
        // ---------------------------------------------------------------

        // للمهندس (01000000000): "دليل جديد على: تقليم السعف الجاف وإزالة البقايا"
        $engineer->notify(new TaskEvidenceSubmitted($pruningTask));

        // لمزارع الروضة (01011122233): "تم اعتماد: فحص عام للنخيل بعد الموسم"
        $approvedTask->farm->client?->notify(new TaskApproved($approvedTask));

        // لمزارع النخيل الذهبي (01033344455): "اترفض: معالجة حشرة القشريات على الجذوع"
        $rejectedSubmission = $rejectedTask->submissions()->first();
        if ($rejectedSubmission) {
            $rejectedTask->farm->client?->notify(new TaskRejected($rejectedSubmission));
        }
    }

    /**
     * تاسك + (لو محتاج) submission واحدة بترجع نفس حالة الـ task.
     */
    private function makeTask(
        Farm $farm,
        User $engineer,
        TaskCategory $category,
        string $title,
        \DateTimeInterface $dueDate,
        TaskStatus $status,
        ?string $pendingNote = null,
        ?string $approvedNote = null,
        ?string $reviewNote = null,
    ): FarmTask {
        $task = FarmTask::create([
            'farm_id' => $farm->id,
            'created_by' => $engineer->id,
            'category' => $category->value,
            'title' => $title,
            'due_date' => $dueDate,
            'status' => $status->value,
        ]);

        if ($status === TaskStatus::Submitted && $pendingNote) {
            TaskSubmission::create([
                'task_id' => $task->id,
                'note' => $pendingNote,
                'status' => TaskSubmissionStatus::PendingReview->value,
                'submitted_at' => now()->subHours(6),
            ]);
        }

        if ($status === TaskStatus::Approved) {
            TaskSubmission::create([
                'task_id' => $task->id,
                'note' => $approvedNote,
                'status' => TaskSubmissionStatus::Approved->value,
                'reviewed_by' => $engineer->id,
                'reviewed_at' => now()->subDays(19),
                'submitted_at' => now()->subDays(19)->subHours(3),
            ]);
        }

        // مرفوض ولسه محدش بعت محاولة جديدة (خلاف حالة الـ retry اللي بنعملها يدوي فوق)
        if ($status === TaskStatus::Rejected && $reviewNote) {
            TaskSubmission::create([
                'task_id' => $task->id,
                'note' => null,
                'status' => TaskSubmissionStatus::Rejected->value,
                'review_note' => $reviewNote,
                'reviewed_by' => $engineer->id,
                'reviewed_at' => now()->subDays(2),
                'submitted_at' => now()->subDays(2)->subHours(4),
            ]);
        }

        return $task;
    }
}
