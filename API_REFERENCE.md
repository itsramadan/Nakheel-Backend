# نخيل (Nakheel) API Reference — Step 2

Base URL (local): `http://nakheel-backend.test/api` (أو أي domain عندك في `APP_URL`)

كل الـ endpoints بترجع JSON. بعد تسجيل الدخول، كل request محتاج header:

```
Authorization: Bearer <token>
Accept: application/json
```

أخطاء الـ validation بترجع بشكل Laravel القياسي:

```json
// 422 Unprocessable Entity
{
  "message": "The phone field is required. (and 1 more error)",
  "errors": {
    "phone": ["The phone field is required."]
  }
}
```

أخطاء الصلاحيات (403) وعدم الوجود (404) برجعوا `{"message": "..."}`.

---

## 1. تسجيل الدخول (OTP)

نفس الرقم بيستخدم للمهندس والمزارع - السيرفر هو اللي بيحدد الدور تلقائياً.
دلوقتي كود التفعيل ثابت **1111** لكل الأرقام (لحد ما يتحدد مزود SMS).

### POST `/auth/otp/request`
```json
// Request
{ "phone": "01011122233" }

// 200 OK
{ "message": "تم إرسال كود التفعيل." }

// 422 لو الرقم مش مسجل
{ "message": "...", "errors": { "phone": ["هذا الرقم غير مسجل في النظام، تواصل مع المهندس المسؤول."] } }
```

### POST `/auth/otp/verify`
```json
// Request
{ "phone": "01011122233", "code": "1111", "fcm_token": "optional - توكن إشعارات الموبايل لو موجود" }

// 200 OK (مثال مزارع)
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "role": "farmer",
  "profile": { "id": 2, "name": "أحمد الشمري", "phone": "01011122233", "city": "الفيوم" }
}

// 200 OK (مثال مهندس - phone=01000000000)
{
  "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "role": "engineer",
  "profile": { "id": 1, "name": "م. أحمد", "phone": "01000000000", "role": "engineer" }
}
```
احفظ الـ `token` في التطبيق وابعته في كل request جاي.

### GET `/auth/me` *(auth)*
بيرجع نفس شكل `{role, profile}` - مفيد وقت فتح التطبيق للتأكد إن التوكن لسه صالح.

### POST `/auth/logout` *(auth)*
بيلغي التوكن الحالي فقط. `{"message": "تم تسجيل الخروج."}`

### POST `/auth/fcm-token` *(auth)*
تحديث توكن إشعارات الموبايل (FCM) بعد الدخول - الموبايل لازم يبعته هنا كل ما يتغيّر (حتى من غير تسجيل دخول جديد، لأن توكنات FCM بتتجدد لوحدها أحيانًا):
```json
// Request
{ "fcm_token": "the-new-fcm-token" }

// 200 OK
{ "message": "تم تحديث توكن الإشعارات." }
```
> `fcm_token` ممكن كمان يتبعت اختياريًا جوه `/auth/otp/verify` نفسها وقت الدخول (أسهل من عمل ريكوست تاني فورًا بعد الدخول) - الاتنين بيحفظوا في نفس العمود.

---

## 2. إشعارات (مشتركة) *(auth)*

- `GET /notifications` → `{data: [...], unread_count, meta: {current_page, last_page}}`
- `POST /notifications/read-all`
- `POST /notifications/{id}/read`

شكل كل notification:
```json
{
  "id": "uuid",
  "type": "evidence_submitted",   // أو task_approved | task_rejected
  "title": "دليل جديد على: تقليم السعف الجاف وإزالة البقايا",
  "body": "مزرعة الروضة",
  "data": { "task_id": 4, "farm_id": 1 },
  "read_at": null,
  "created_at": "2026-08-18T10:00:00+00:00"
}
```

---

## 3. المهندس *(auth + role: engineer)*

### GET `/engineer/dashboard`
"لوحة اليوم":
```json
{
  "clients_count": 4,
  "farms_count": 5,
  "late_tasks_count": 1,
  "upcoming_visits_count": 5,
  "nearest_visit": { "...": "VisitResource أو null" },
  "latest_tasks": [ "...TaskResource" ]
}
```

### العملاء
- `GET /engineer/clients` → `{data: [ClientResource...]}`
- `POST /engineer/clients` → body: `{name, phone, city}` → `{data: ClientResource}` (201)
- `GET /engineer/clients/{client}` → `{data: ClientResource}` (بالمزارع كاملة)

`ClientResource`:
```json
{
  "id": 2, "name": "أحمد الشمري", "phone": "01011122233", "city": "الفيوم",
  "farms": [ { "id": 1, "name": "مزرعة الروضة", "location": "الفيوم", "palm_count": 320, "next_visit_date": "2026-09-01", "open_tasks_count": 2 } ],
  "created_at": "..."
}
```
> `open_tasks_count` = عدد تاسكات المزرعة اللي لسه مش `approved` (يعني pending + submitted + rejected مجتمعين) - ده رقم "تاسكات مفتوحة" على كارت المزرعة.

### المزارع
- `POST /engineer/clients/{client}/farms` → إنشاء مزرعة لعميل:
```json
// Request
{
  "name": "مزرعة الروضة",
  "location": "أبو حماد، حي الزراعات",
  "area": 45,
  "variety_type": "مجدول",
  "palm_count": 320,
  "palm_age_years": 12,
  "planted_at": "2014-09-15",
  "soil_type": "طينية",
  "water_source": "بئر ارتوازي",
  "water_analysis": "نسبة الأملاح ٨٠٠ جزء في المليون - ملوحة منخفضة.",
  "general_notes": "الأرض مستوية وسهلة الوصول لكل الأشجار."
}
```
> الحقول دي (بالترتيب ده بالظبط): `name`, `location`, `area`, `variety_type`, `palm_count`, `palm_age_years`, `planted_at` (تاريخ الزراعة - `YYYY-MM-DD`), `soil_type`, `water_source`, `water_analysis`, `general_notes`. (`اسم العميل` مش حقل في الـ body - هو `{client}` في الـ URL نفسه.) `variety_type` نص حر دايمًا (مجدول/صعيدي/أو أي اسم صنف تاني من "غيره") - مفيش id. كلهم `nullable` غير `name` و `palm_count` (مطلوبين).
>
> `subscription_cycle`، `subscription_next_payment_date` حقول قديمة من قبل ده لسه موجودة في الـ endpoint (مش من الصورة دي ومفيش تغيير فيها)، ممكن تتبعت اختياريًا في نفس الريكوست لو حابب. `city` **اتشال بالكامل** من المزرعة (مش موجود ولا في الريكوست ولا في الريسبونس) - استخدم `location` بدل منه.
- `GET /engineer/farms/{farm}` → تفاصيل كاملة (`FarmResource`، شامل `plan_summary`)
- `PUT /engineer/farms/{farm}` → تعديل (كل الحقول اختيارية، بما فيها الحقول دي)

`FarmResource`:
```json
{
  "id": 1, "name": "مزرعة الروضة", "palm_count": 320, "area": 45.0,
  "location": "أبو حماد، حي الزراعات",
  "variety_type": "مجدول",
  "palm_age_years": 12,
  "planted_at": "2014-09-15",
  "soil_type": "طينية",
  "water_source": "بئر ارتوازي",
  "water_analysis": "نسبة الأملاح ٨٠٠ جزء في المليون - ملوحة منخفضة.",
  "general_notes": "الأرض مستوية وسهلة الوصول لكل الأشجار.",
  "subscription": { "cycle": "monthly", "cycle_label": "شهري", "next_payment_date": "2026-09-01" },
  "next_visit_date": "2026-08-20",
  "open_tasks_count": 2,
  "plan_summary": [
    {
      "category": "spraying", "category_label": "برنامج الرش", "tasks_count": 1,
      "tasks": [ "...TaskResource (نفس شكل التاسك المعتاد، شايفه تحت)" ]
    }
  ],
  "client": { "id": 2, "name": "أحمد الشمري", "phone": "01011122233" },
  "created_at": "..."
}
```
> `open_tasks_count` و `plan_summary` بيظهروا بس لو الـ endpoint بيحمّل التاسكات (كل الـ endpoints الحالية بتعملها). `plan_summary[].tasks` دلوقتي فيها التاسك كامل (عنوان + حالة) مش بس عدد، عشان شاشة "الخطة – حسب التصنيف" عند المهندس تعرضهم.

### التاسكات
> `category` (البرنامج) - 4 قيم بس: `irrigation` (برنامج الري) | `fertilizing` (برنامج التسميد) | `spraying` (برنامج الرش) | `agricultural_operations` (برنامج العمليات الزراعية). أي قيمة تانية بترفض بـ validation error.
- `GET /engineer/tasks?farm_id=&category=&status=` (`status`: `all` | `approved` | `pending` | `late`)
- `POST /engineer/farms/{farm}/tasks` → `{category, title, description?, due_date}`
- `GET /engineer/tasks/{task}` → "مراجعة التاسك" → `{data: TaskResource, submissions: [TaskSubmissionResource...]}` (تاريخ كل المحاولات)
- `POST /engineer/tasks/{task}/approve`
- `POST /engineer/tasks/{task}/reject` → `{review_note}`

`TaskResource`:
```json
{
  "id": 4, "title": "رصد إصابة محدودة بحفار عذوق النخيل", "description": null,
  "category": "spraying", "category_label": "برنامج الرش", "due_date": "2026-08-16",
  "status": "pending", "status_label": "قيد الانتظار",
  "display_status": "late", "display_status_label": "متأخر",
  "farm": {
    "id": 1, "name": "مزرعة الروضة", "palm_count": 320, "area": 45.0,
    "location": "الفيوم",
    "variety_type": "مجدول", "palm_age_years": 12, "planted_at": "2014-09-15",
    "soil_type": "طينية", "water_source": "بئر ارتوازي",
    "water_analysis": "نسبة الأملاح ٨٠٠ جزء في المليون - ملوحة منخفضة.",
    "general_notes": "الأرض مستوية وسهلة الوصول لكل الأشجار.",
    "subscription": { "cycle": "monthly", "cycle_label": "شهري", "next_payment_date": "2026-09-01" },
    "next_visit_date": null,
    "open_tasks_count": 2,
    "created_at": "..."
  },
  "is_retry": false,
  "latest_submission": null,
  "created_at": "..."
}
```
> `farm` دلوقتي بيرجع **تفاصيل المزرعة كاملة** (نفس شكل `FarmResource` - كل الحقول اللي المهندس دخلها في فورم إضافة المزرعة)، مش نسخة مصغّرة زي قبل كذا. ده تغيير شكل مشترك بين كل الـ endpoints اللي بترجع `TaskResource` (تاسكات المهندس والمزارع الاتنين، لأنهم بيستخدموا نفس الـ resource). `plan_summary` و `client` مش ظاهرين هنا لأن التاسك مبيحمّلش علاقات `tasks`/`client` بتاعت المزرعة (`whenLoaded` بيشيلهم من الرد تلقائيًا).
> `status` = الحالة الحقيقية (pending/submitted/approved/rejected). `display_status` = الفلتر المبسط في الديزاين (approved/pending/late) — استخدم ده في تلوين الشارات.

`TaskSubmissionResource`:
```json
{
  "id": 7, "note": "رشيت المبيد...", "status": "pending_review", "status_label": "بانتظار المراجعة",
  "review_note": null, "submitted_at": "...", "reviewed_at": null,
  "media": [ { "id": 3, "type": "image", "url": "http://.../storage/task-submissions/xxx.jpg" } ]
}
```

### الزيارات
- `GET /engineer/visits?farm_id=&status=&client_id=` (`status`: `all` | `scheduled` | `completed` | `cancelled`)
- `POST /engineer/farms/{farm}/visits` → `{title, scheduled_date, notes?}`
- `GET /engineer/visits/{visit}` → "تفاصيل الزيارة"
- `POST /engineer/visits/{visit}/complete` → `{report_note?}` (اختياري بالكامل)
- `POST /engineer/visits/{visit}/cancel` (بدون body)

> `complete`/`cancel` بترفض بـ 422 لو الزيارة خلصت بالفعل (مش `scheduled`).

`VisitResource`:
```json
{
  "id": 5,
  "title": "زيارة متابعة إصابة سوسة النخيل",
  "scheduled_date": "2026-09-24",
  "notes": "متابعة التقرير المرفوع من المزارع عن الإصابة.",
  "status": "scheduled",
  "status_label": "مجدولة",
  "is_upcoming_soon": true,
  "report_note": null,
  "completed_at": null,
  "farm": { "...": "FarmResource كامل (شامل client)" },
  "created_at": "..."
}
```
> `title` و `notes` بيتدخلوا وقت إنشاء الزيارة (`notes` اختياري - ده اللي بيظهر كـ"الوصف" في شاشة تفاصيل الزيارة). `report_note` منفصل تمامًا وبيتدخل وقت "تمت الزيارة" بس (اختياري برضو). `farm` بيرجع تفاصيل المزرعة كاملة (نفس مبدأ `TaskResource.farm`) شامل `client` جواها - مفيش حاجة تانية منفصلة لازم تحملها.
> `is_upcoming_soon` = true لو الزيارة `scheduled` وباقي عليها أسبوع أو أقل.

---

## 4. المزارع *(auth + role: farmer)*

### GET `/farmer/dashboard`
"الرئيسية" عند المزارع - حاليًا بيانات الزيارات بس (تنبيه لو فيه زيارة قريبة على أي مزرعة من مزارعه):
```json
{
  "upcoming_visits_count": 1,
  "nearest_visit": { "...": "VisitResource أو null" }
}
```
> لو محتاج بيانات تانية في نفس الرد (تاسكات، مزارع...) قولّي وأضيفها.

- `GET /farmer/farms` → مزارع المزارع نفسه (`{data: [FarmResource...]}`) - للسويتشر لو أكتر من مزرعة
- `GET /farmer/farms/{farm}` → `{data: FarmResource, last_visits: [VisitResource...]}` (آخر ٥ زيارات مكتملة بتقاريرها)
- `GET /farmer/visits/{visit}` → "تفاصيل الزيارة" عند المزارع - نفس `VisitResource` بالظبط (تفاصيل الزيارة + المزرعة كاملة)، من غير أي أكشن (مفيش complete/cancel عند المزارع).
- `GET /farmer/tasks?farm_id=&category=&status=` → **كل تاسكات المزارع في كل مزارعه مجتمعة** (لشاشة "التاسكات" العامة، بدل ما يحدد مزرعة). كل الفلاتر اختيارية - لو محتاج مزرعة بعينها ابعت `farm_id`.
- `GET /farmer/farms/{farm}/tasks?status=` → تاسكات مزرعة واحدة بعينها فقط (لو شاشة تفاصيل المزرعة عايزة تاسكاتها هي بس)
- `GET /farmer/tasks/{task}` → تفاصيل تاسك (فيه `latest_submission`)
- `POST /farmer/tasks/{task}/submissions` → **multipart/form-data**:
  - `note` (اختياري)
  - `media[]` (صور/فيديو، لغاية ١٠ ملفات، ٥٠ ميجا للملف)

  → `{data: TaskSubmissionResource}` (201)

  لو التاسك بالفعل `submitted` أو `approved` بيرجع 422.

---

## ملاحظات تنفيذية للفلاتر (Flutter)
1. كل الـ endpoints المحمية محتاجة `auth:sanctum` — لو التوكن غلط/منتهي بترجع 401.
2. لازم تشغّل `php artisan storage:link` عندك عشان روابط `media[].url` تكون شغالة (public disk).
3. الحقول الرقمية العشرية (`area`) برجع كـ number (float) في JSON مش string.
4. التواريخ: `date` بصيغة `YYYY-MM-DD`، والـ `datetime` بصيغة ISO 8601 مع timezone.
5. لسه ماعملتش endpoints للـ update/delete بتوع العملاء أو حذف تاسك/زيارة - قولي لو محتاجهم دلوقتي.
6. إشعار "زيارة قريبة" التلقائي (لما تقرب زيارة) لسه مش متعمل - ده محتاج Laravel Scheduler (cron)، ينفع نضيفه بعدين كخطوة منفصلة.
