# TASK-07: لوحة تحكم العميل (`/client/dashboard`)

> **المستوى**: صعب | **المدة المقدرة**: 5 أيام | **الأولوية**: عالية

---

## 1. نظرة عامة

لوحة تحكم متكاملة وحديثة للعميل (طالب الخدمة) تعرض إحصائيات حقيقية وطلبات حديثة وإشعارات وسجل معاملات — بديل احترافي للداشبورد الحالي البسيط (`user_dashboard.blade.php`).

**الهدف**: تجربة مستخدم ممتازة تُشبه Stripe Dashboard أو Shopify Admin.

---

## 2. البنية التحتية

### 2.1 Routes

```php
// routes/web.php — ضمن middleware auth:business
Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])
    ->name('amrtm.client.dashboard');
```

### 2.2 Controller

```php
// app/Http/Controllers/ClientDashboardController.php
namespace App\Http\Controllers;

use App\Models\Business\BusinessUser;
use App\Models\Business\ServiceRequest;
use App\Models\Business\ServicePayment;
use App\Models\Business\OfficeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'total_requests' => ServiceRequest::where('user_id', $user->id)->count(),
            'pending' => ServiceRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'processing' => ServiceRequest::where('user_id', $user->id)->whereIn('status', ['processing', 'in_progress'])->count(),
            'completed' => ServiceRequest::where('user_id', $user->id)->where('status', 'done')->count(),
            'balance' => $this->getUserBalance($user->id),
        ];

        $recentRequests = ServiceRequest::with('govService', 'entity', 'office')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $recentNotifications = \App\Models\Business\BusinessNotification::where('user_id', $user->id)
            ->latest()
            ->limit(3)
            ->get();

        $monthlyRequests = ServiceRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return view('update_service.client_dashboard', compact(
            'stats', 'recentRequests', 'recentNotifications', 'monthlyRequests'
        ));
    }

    private function getUserBalance(int $userId): float
    {
        return ServicePayment::where('user_id', $userId)
            ->selectRaw("SUM(CASE WHEN type='charge' THEN amount WHEN type='refund' THEN amount ELSE -amount END) as balance")
            ->value('balance') ?? 0.0;
    }
}
```

### 2.3 لا يحتاج Migration

يعتمد على جداول موجودة بالكامل.

---

## 3. الواجهة الأمامية

### 3.1 الصفحة (`client_dashboard.blade.php`)

**الم.layout**: `@extends('layouts.public')`

**الهيكل العام**:

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│  Hero Section                                       │
│  ┌─────────────────────────────────────────────┐   │
│  │  مرحباً، [اسم العميل] 👋                    │   │
│  │  إحصائيات: 12 طلب | 3 قيد التنفيذ | 500 ر.س │   │
│  └─────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────┤
│  Stat Cards (4 بطاقات في صف)                        │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐             │
│  │إجمالي│ │قيد   │ │مكتمل│ │الرصيد│             │
│  │ الطلبات│ │التنفيذ│ │     │ │      │             │
│  │  12   │ │  3   │ │  5  │ │500 ر.س│             │
│  └──────┘ └──────┘ └──────┘ └──────┘             │
├─────────────────────────────────────────────────────┤
│  الشريط الجانبي (desktop) / عمود واحد (mobile)       │
│  ┌──────────────────────┬─────────────────────┐   │
│  │  آخر 5 طلبات         │  آخر 3 إشعارات      │   │
│  │  ┌────────────────┐  │  ┌────────────────┐ │   │
│  │  │ AMR-001234     │  │  │ 🔔 تم قبول...  │ │   │
│  │  │ سجل تجاري     │  │  │ 🔔 دفع new...   │ │   │
│  │  │ ✅ مكتمل      │  │  │ 🔔 تحديث...    │ │   │
│  │  ├────────────────┤  │  └────────────────┘ │   │
│  │  │ AMR-001235     │  │                     │   │
│  │  │ ترخيص مزاولة  │  │  أكثر الخدمات طلباً  │   │
│  │  │ ⏳ قيد التنفيذ│  │  ┌────────────────┐ │   │
│  │  └────────────────┘  │  │ 1. سجل تجاري  │ │   │
│  │                      │  │ 2. ترخيص       │ │   │
│  │  [عرض كل الطلبات →]  │  │ 3. تعقيب       │ │   │
│  └──────────────────────┴─────────────────────┘   │
├─────────────────────────────────────────────────────┤
│  رسم بياني: طلبات آخر 6 شهور (Bar Chart)           │
│  ┌─────────────────────────────────────────────┐   │
│  │  📊                                         │   │
│  │  ████                                       │   │
│  │  ████ ████                                  │   │
│  │  ████ ████ ████                             │   │
│  │  Jan Feb Mar Apr May Jun                    │   │
│  └─────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────┤
│  اختصارات سريعة (3 أزرار)                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │طلب خدمة   │ │عرض الطلبات│ │شحن الرصيد│           │
│  │  ➕        │ │  📋       │ │  💳       │           │
│  └──────────┘ └──────────┘ └──────────┘           │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

### 3.2 بطاقات الإحصائيات

```blade
{{-- resources/views/components/dashboard/stat-card.blade.php --}}
<div class="rounded-2xl border border-[--b1] bg-white p-6 shadow-[0_4px_24px_rgba(0,0,0,0.06)]
            transition-all duration-300 hover:shadow-[0_8px_32px_rgba(0,0,0,0.1)] hover:-translate-y-1">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 tabular-nums" dir="ltr">{{ $value }}</p>
        </div>
        <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ $bgClass }}">
            <i class="ti {{ $icon }} text-2xl {{ $iconClass }}"></i>
        </div>
    </div>
    @if($trend)
        <div class="mt-3 flex items-center gap-1 text-sm">
            <i class="ti ti-trending-up text-green-500"></i>
            <span class="text-green-600">{{ $trend }}</span>
            <span class="text-gray-400">من الشهر الماضي</span>
        </div>
    @endif
</div>
```

**الاستخدام**:

```blade
<x-dashboard.stat-card
    label="إجمالي الطلبات"
    :value="$stats['total_requests']"
    icon="ti-file-text"
    bgClass="bg-blue-50"
    iconClass="text-blue-600"
    trend="+3"
/>
```

### 3.3 الرسم البياني

**المكتبة**: Chart.js عبر CDN (لا حاجة لتثبيت npm)

```html
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
            datasets: [{
                label: 'الطلبات',
                data: @json(array_values($monthlyRequests)),
                backgroundColor: 'rgba(0, 108, 53, 0.8)',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endsection
```

### 3.4 بطاقة الطلب الأخيرة

```blade
@foreach($recentRequests as $request)
<a href="{{ route('amrtm.my-requests.show', $request->id) }}"
   class="flex items-center gap-4 rounded-xl border border-[--b1] p-4 transition-all hover:bg-gray-50">
    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--cui-primary-soft)]">
        <i class="ti ti-file-text text-[var(--cui-primary)]"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-900">{{ $request->ref_number }}</p>
        <p class="truncate text-xs text-gray-500">{{ $request->govService->name_ar ?? 'خدمة' }}</p>
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
</a>
@endforeach
```

### 3.5 اختصارات سريعة

```blade
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <a href="{{ route('amrtm.catalog.category', 'ministries') }}"
       class="flex items-center gap-4 rounded-2xl border border-[--b1] bg-white p-6 transition-all hover:bg-[var(--cui-primary)] hover:text-white group">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600
                    group-hover:bg-white/20 group-hover:text-white">
            <i class="ti ti-plus text-xl"></i>
        </div>
        <div>
            <p class="font-semibold">طلب خدمة جديدة</p>
            <p class="text-sm text-gray-400 group-hover:text-white/70">تصفح الخدمات الحكومية</p>
        </div>
    </a>
    <!-- باقي الاختصارات... -->
</div>
```

---

## 4. التجاوب (Responsive)

| المقاس | التخطيط |
|--------|---------|
| `≥1024px` | شبكة: محتوى رئيسي 2/3 + شريط جانبي 1/3 |
| `768px - 1023px` | عمود واحد: الإحصائيات → الطلبات → الإشعارات |
| `≤768px` | عمود واحد مدمج: بطاقات أصغر + قائمة بديلة للرسم البياني |

**الحد الأدنى المطلوب**: لا overflow + أرقام `tabular-nums` + لا تقطيع في النصوص.

---

## 5. CSS

### 5.1 الفئات المخصصة (داخل `@push('styles')`)

```css
/* Stat card gradient backgrounds */
.stat-card--blue { background: linear-gradient(135deg, #EFF6FF, #DBEAFE); }
.stat-card--amber { background: linear-gradient(135deg, #FFFBEB, #FEF3C7); }
.stat-card--green { background: linear-gradient(135deg, #ECFDF5, #D1FAE5); }
.stat-card--purple { background: linear-gradient(135deg, #F5F3FF, #EDE9FE); }

/* Chart container */
.chart-container { position: relative; height: 280px; }
```

---

## 6. الاختبارات

### 6.1 Feature Test

```php
// tests/Feature/ClientDashboardTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Business\BusinessUser;
use App\Models\Business\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->businessDb = DB::connection('business');
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/client/dashboard');
        $response->assertRedirect();
    }

    public function test_client_can_open_dashboard()
    {
        $user = BusinessUser::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'business')
            ->get('/client/dashboard');

        $response->assertOk();
        $response->assertSee('مرحباً');
        $response->assertSee('id="stat-total"');
    }

    public function test_dashboard_shows_real_stats()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);
        ServiceRequest::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'done']);
        ServiceRequest::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user, 'business')->get('/client/dashboard');

        $response->assertOk();
        $response->assertSee('5');   // total
        $response->assertSee('2');   // pending
        $response->assertSee('3');   // completed
    }

    public function test_dashboard_includes_chart_data()
    {
        $user = BusinessUser::factory()->create(['role' => 'user', 'is_active' => true]);

        $response = $this->actingAs($user, 'business')->get('/client/dashboard');

        $response->assertOk();
        $response->assertSee('monthlyChart');
        $response->assertSee('chart.js');
    }
}
```

### 6.2 Checklist يدوي

- [ ] تسجيل دخول عميل → ينتقل تلقائياً للوحة `/client/dashboard`
- [ ] الإحصائيات تعرض أرقاماً حقيقية
- [ ] الرسم البياني يُعرض (Chart.js)
- [ ] آخر 5 طلبات تظهر مع حالة صحيحة
- [ ] آخر 3 إشعارات تظهر
- [ ] اختصار "طلب خدمة" يُوجه للكتالوج
- [ ] اختصار "شحن الرصيد" يفتح مودال الشحن
- [ ] على الموبايل: لا overflow + كل شيء قابل للقراءة
- [ ] لا أخطاء Console

---

## 7. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **إضافة** مسار `/client/dashboard` |
| `app/Http/Controllers/ClientDashboardController.php` | **جديد** |
| `resources/views/update_service/client_dashboard.blade.php` | **جديد** |
| `resources/views/components/dashboard/stat-card.blade.php` | **جديد** (مكوّن مشترك) |
| `tests/Feature/ClientDashboardTest.php` | **جديد** |

---

## 8. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan test --filter=ClientDashboardTest` | 4 اختبارات PASS |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| لا `<input>` / `<button>` خام | صفر |
| Chart.js يُعرض | الرسم ظاهر |
| التجاوب 375/768/1280 | سليم |
| لا أخطاء Console | صفر |
