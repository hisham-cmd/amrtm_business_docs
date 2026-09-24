# TASK-12: صفحة طلباتي (`/my-requests`)

> **المستوى**: صعب | **المدة المقدرة**: 5 أيام | **الأولوية**: عالية (Core Product)

---

## 1. نظرة عامة

صفحة مستقلة تتبع فيها العميل كل طلباته مع **شريط تقدم حي** + **سجل زمني** + **رسائل مع المكتب**. بديل احترافي للداشبورد الحالي المدمج.

**الملفات الجديدة**:
- `my_requests.blade.php` — القائمة
- `my_request_show.blade.php` — التفاصيل
- `MyRequestsController.php`

---

## 2. Routes

```php
// routes/web.php — auth:business
Route::get('/my-requests', [MyRequestsController::class, 'index'])
    ->name('amrtm.my-requests');
Route::get('/my-requests/{id}', [MyRequestsController::class, 'show'])
    ->name('amrtm.my-requests.show');
Route::post('/my-requests/{id}/messages', [MyRequestsController::class, 'sendMessage'])
    ->name('amrtm.my-requests.message');
```

---

## 3. Controller

```php
// app/Http/Controllers/MyRequestsController.php
<?php

namespace App\Http\Controllers;

use App\Models\Business\ServiceRequest;
use App\Models\Business\OfficeMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyRequestsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'all');

        $query = ServiceRequest::with('govService', 'entity', 'office')
            ->where('user_id', $user->id)
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => ServiceRequest::where('user_id', $user->id)->count(),
            'pending' => ServiceRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'processing' => ServiceRequest::where('user_id', $user->id)->whereIn('status', ['processing', 'in_progress'])->count(),
            'completed' => ServiceRequest::where('user_id', $user->id)->where('status', 'done')->count(),
        ];

        return view('update_service.my_requests', compact('requests', 'stats', 'status'));
    }

    public function show($id)
    {
        $user = Auth::user();

        $request = ServiceRequest::with([
            'govService', 'entity.category', 'office', 'logs' => fn($q) => $q->latest(),
            'messages' => fn($q) => $q->latest(),
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        return view('update_service.my_request_show', compact('request'));
    }

    public function sendMessage(Request $request, $id)
    {
        $user = Auth::user();

        $serviceRequest = ServiceRequest::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ], [
            'message.required' => 'الرسالة مطلوبة',
            'message.max' => 'الرسالة لا تتجاوز 2000 حرف',
        ]);

        OfficeMessage::create([
            'request_id' => $serviceRequest->id,
            'office_id' => $serviceRequest->office_id,
            'sender_type' => 'client',
            'sender_id' => $user->id,
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        return back()->with('success', 'تم إرسال الرسالة بنجاح');
    }
}
```

---

## 4. الصفحة الرئيسية — القائمة

### 4.1 الهيكل

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│  Hero (cui-hero)                                    │
│  breadcrumb: الرئيسية > طلباتي                      │
│  العنوان: طلباتي                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  شريط الإحصائيات (4 بطاقات compact)                 │
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐          │
│  │الإجمالي│ │قيد    │ │جاري   │ │مكتمل  │          │
│  │  12   │ │الانتظار│ │المعالجة│ │      │          │
│  │       │ │  3    │ │  4    │ │  5   │          │
│  └───────┘ └───────┘ └───────┘ └───────┘          │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  فلاتر الحالة                                        │
│  ┌─────────────────────────────────────────────┐   │
│  │  [الكل (12)] [قيد الانتظار (3)] [جاري (4)] [مكتمل (5)] [مرفوض (0)]│
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  قائمة الطلبات                                      │
│  ┌────────────────────────────────────────────┐   │
│  │ AMR-001234                                │   │
│  │ 📋 تجديد السجل التجاري                     │   │
│  │ وزارة التجارة > جهةM01                     │   │
│  │ 🏢 مكتب الأمل | ⏳ قيد التنفيذ             │   │
│  │ 📅 2026-09-01                              │   │
│  │ ━━━━━━━━━━━━━━━━━━━░░░░░░░░ 60%            │   │
│  │                              [عرض التفاصيل →]│   │
│  ├────────────────────────────────────────────┤   │
│  │ AMR-001235                                │   │
│  │ 📋 إصدار رخصة مزاولة                      │   │
│  │ وزارة العمل > جهةL02                      │   │
│  │ 🏢 مكتب النور | ✅ مكتمل                  │   │
│  │ 📅 2026-08-15                              │   │
│  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ 100%        │   │
│  │                              [عرض التفاصيل →]│   │
│  └────────────────────────────────────────────┘   │
│                                                     │
│  ┌────────────────────────────────────────────┐   │
│  │  ← السابق    1 2 3    التالي →             │   │
│  └────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

### 4.2 شريط التقدم

```blade
{{-- حساب نسبة التقدم --}}
@php
    $progress = match($request->status) {
        'pending' => 20,
        'processing' => 50,
        'in_progress' => 75,
        'done' => 100,
        'rejected' => 100,
        default => 0,
    };
    $progressColor = match($request->status) {
        'done' => 'bg-green-500',
        'rejected' => 'bg-red-500',
        'in_progress' => 'bg-blue-500',
        'processing' => 'bg-amber-500',
        default => 'bg-gray-300',
    };
@endphp

<div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100">
    <div class="{{ $progressColor }} h-full rounded-full transition-all duration-500"
         style="width: {{ $progress }}%"></div>
</div>
<p class="mt-1 text-xs text-gray-400" dir="ltr">{{ $progress }}%</p>
```

### 4.3 بطاقة الطلب

```blade
<a href="{{ route('amrtm.my-requests.show', $request->id) }}"
   class="group block rounded-2xl border border-[--b1] bg-white p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
    <div class="flex items-start justify-between">
        <div>
            <p class="font-mono text-sm font-bold text-[var(--cui-primary)]" dir="ltr">{{ $request->ref_number }}</p>
            <h3 class="mt-1 font-semibold text-gray-900 group-hover:text-[var(--cui-primary)]">
                {{ $request->govService->name_ar ?? 'خدمة' }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ $request->entity->name_ar ?? '' }}
                @if($request->entity->category)
                    <span class="text-gray-300">|</span>
                    {{ $request->entity->category->name_ar }}
                @endif
            </p>
        </div>
        <x-ui.badge :variant="match($request->status) {
            'pending' => 'warning',
            'processing', 'in_progress' => 'info',
            'done' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        }">
            {{ match($request->status) {
                'pending' => 'قيد الانتظار',
                'processing' => 'جاري المعالجة',
                'in_progress' => 'قيد التنفيذ',
                'done' => 'مكتمل',
                'rejected' => 'مرفوض',
                default => $request->status
            } }}
        </x-ui.badge>
    </div>

    <div class="mt-3 flex items-center gap-4 text-sm text-gray-400">
        @if($request->office)
            <span class="flex items-center gap-1">
                <i class="ti ti-building"></i>
                {{ $request->office->name_ar }}
            </span>
        @endif
        <span class="flex items-center gap-1">
            <i class="ti ti-calendar"></i>
            {{ $request->created_at->format('Y-m-d') }}
        </span>
    </div>

    {{-- شريط التقدم --}}
    @php
        $progress = match($request->status) {
            'pending' => 20, 'processing' => 50, 'in_progress' => 75,
            'done' => 100, 'rejected' => 100, default => 0
        };
    @endphp
    <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
        <div class="h-full rounded-full transition-all duration-500
            {{ $request->status === 'done' ? 'bg-green-500' : ($request->status === 'rejected' ? 'bg-red-500' : 'bg-[#006C35]') }}"
             style="width: {{ $progress }}%"></div>
    </div>
</a>
```

---

## 5. صفحة التفاصيل

### 5.1 الهيكل

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│  breadcrumb: الرئيسية > طلباتي > AMR-001234          │
├─────────────────────────────────────────────────────┤
│                                                     │
│  رأس الطلب                                          │
│  ┌─────────────────────────────────────────────┐   │
│  │  AMR-001234                    قيد التنفيذ  │   │
│  │  تجديد السجل التجاري                       │   │
│  │  ━━━━━━━━━━━━━━━━━━━░░░░░░░░ 75%            │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  معلومات الطلب (بطاقة)                              │
│  ┌─────────────────────────────────────────────┐   │
│  │  الخدمة: تجديد السجل التجاري                 │   │
│  │  الجهة: وزارة التجارة                         │   │
│  │  المكتب: مكتب الأمل للمحاماة                 │   │
│  │  السعر: 200.00 ر.س                           │   │
│  │  تاريخ الإرسال: 2026-09-01                   │   │
│  │  ملاحظاتي: يرجى الت加速 قبل انتهاء السجل     │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  السجل الزمني (Timeline)                            │
│  ┌─────────────────────────────────────────────┐   │
│  │  ● 2026-09-01 10:00 — تم استلام الطلب       │   │
│  │  │                                          │   │
│  │  ● 2026-09-02 14:30 — قيد المعالجة          │   │
│  │  │                                          │   │
│  │  ● 2026-09-03 09:15 — جاري التجهيز         │   │
│  │  │                                          │   │
│  │  ◐ الآن — قيد التنفيذ                       │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  الرسائل مع المكتب                                  │
│  ┌─────────────────────────────────────────────┐   │
│  │  💬 المكتب (2026-09-02):                    │   │
│  │  "تم استلام طلبك وسنبدأ المعالجة قريباً"     │   │
│  │                                               │   │
│  │  💬 أنت (2026-09-03):                        │   │
│  │  "هل يمكن تسريع الإجراءات؟"                  │   │
│  │                                               │   │
│  │  [اكتب رسالة...]                    [إرسال]  │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

### 5.2 السجل الزمني (Timeline)

```blade
<div class="relative space-y-6">
    @foreach($request->logs as $log)
        <div class="flex gap-4">
            {{-- الدائرة --}}
            <div class="relative flex flex-col items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-full
                    {{ $loop->first ? 'bg-[#006C35] text-white' : 'bg-gray-100 text-gray-400' }}">
                    <i class="ti {{ match($log->status) {
                        'pending' => 'ti-clock',
                        'processing', 'in_progress' => 'ti-loader',
                        'done' => 'ti-check',
                        'rejected' => 'ti-x',
                        default => 'ti-circle'
                    } }}"></i>
                </div>
                @unless($loop->last)
                    <div class="mt-1 h-full w-0.5 flex-1 bg-gray-200"></div>
                @endunless
            </div>
            {{-- المحتوى --}}
            <div class="pb-6">
                <p class="text-sm font-medium text-gray-900">{{ $log->note ?? $log->status }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ $log->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    @endforeach
</div>
```

### 5.3 نموذج إرسال الرسالة

```blade
<form action="{{ route('amrtm.my-requests.message', $request->id) }}" method="POST" class="mt-4">
    @csrf
    <div class="flex gap-3">
        <x-ui.textarea
            name="message"
            placeholder="اكتب رسالتك هنا..."
            rows="2"
            class="flex-1"
        />
        <x-ui.button type="submit" variant="primary" class="self-end">
            <i class="ti ti-send"></i>
            إرسال
        </x-ui.button>
    </div>
    @error('message')
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</form>
```

---

## 6. الخطوات الزمنية (Steps Visualization)

**في صفحة التفاصيل** — شريط خطوات بدل الشريط البسيط:

```blade
@php
    $steps = [
        ['key' => 'pending', 'label' => 'تم الإرسال', 'icon' => 'ti-send'],
        ['key' => 'processing', 'label' => 'جاري المعالجة', 'icon' => 'ti-clock'],
        ['key' => 'in_progress', 'label' => 'قيد التنفيذ', 'icon' => 'ti-loader'],
        ['key' => 'done', 'label' => 'مكتمل', 'icon' => 'ti-check'],
    ];
    $statusOrder = ['pending' => 0, 'processing' => 1, 'in_progress' => 2, 'done' => 3, 'rejected' => 3];
    $currentStep = $statusOrder[$request->status] ?? 0;
@endphp

<div class="flex items-center justify-between">
    @foreach($steps as $i => $step)
        <div class="flex flex-1 items-center">
            <div class="flex flex-col items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all
                    {{ $i <= $currentStep
                        ? 'border-[#006C35] bg-[#006C35] text-white'
                        : 'border-gray-200 bg-white text-gray-300' }}">
                    <i class="ti {{ $step['icon'] }}"></i>
                </div>
                <span class="mt-2 text-xs text-center {{ $i <= $currentStep ? 'text-[#006C35] font-medium' : 'text-gray-400' }}">
                    {{ $step['label'] }}
                </span>
            </div>
            @unless($loop->last)
                <div class="mx-2 h-0.5 flex-1 {{ $i < $currentStep ? 'bg-[#006C35]' : 'bg-gray-200' }}"></div>
            @endunless
        </div>
    @endforeach
</div>
```

---

## 7. التجاوب

| المقاس | السلوك |
|--------|--------|
| `≥1024px` | صفحتا القائمة والتفاصيل بعرض كامل |
| `768px - 1023px` | بطاقات أعرض + timeline أفقي |
| `≤768px` | بطاقات عمودية + timeline عمودي + نموذج الرسالة fullscreen-like |

---

## 8. الاختبارات

```php
// tests/Feature/MyRequestsTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Business\BusinessUser;
use App\Models\Business\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->businessDb = DB::connection('business');
    }

    public function test_guest_cannot_access_my_requests()
    {
        $response = $this->get('/my-requests');
        $response->assertRedirect();
    }

    public function test_client_can_view_requests_list()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        ServiceRequest::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'business')->get('/my-requests');
        $response->assertOk();
        $response->assertSee('طلباتي');
    }

    public function test_client_can_view_request_details()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'business')->get("/my-requests/{$request->id}");
        $response->assertOk();
        $response->assertSee($request->ref_number);
    }

    public function test_client_cannot_view_others_requests()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        $otherUser = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        $request = ServiceRequest::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'business')->get("/my-requests/{$request->id}");
        $response->assertNotFound();
    }

    public function test_status_filter_works()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        ServiceRequest::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'done']);
        ServiceRequest::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user, 'business')->get('/my-requests?status=done');
        $response->assertOk();
    }

    public function test_send_message_to_office()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        $request = ServiceRequest::factory()->create(['user_id' => $user->id, 'office_id' => 1]);

        $response = $this->actingAs($user, 'business')
            ->post("/my-requests/{$request->id}/messages", ['message' => 'رسالة اختبار']);
        $response->assertRedirect();
    }
}
```

---

## 9. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **إضافة** 3 مسارات |
| `app/Http/Controllers/MyRequestsController.php` | **جديد** |
| `resources/views/update_service/my_requests.blade.php` | **جديد** |
| `resources/views/update_service/my_request_show.blade.php` | **جديد** |
| `tests/Feature/MyRequestsTest.php` | **جديد** |

---

## 10. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan test --filter=MyRequestsTest` | 6 اختبارات PASS |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| شريط التقدم يظهر | حسب الحالة |
| الخطوات الزمنية تعمل | 4 خطوات |
| إرسال رسالة يعمل | `OfficeMessage` يُنشأ |
| لا عميل يرى طلبات غيره | 404 |
| التجاوب 3 مقاسات | سليم |
