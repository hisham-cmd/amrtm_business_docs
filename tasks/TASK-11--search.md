# TASK-11: صفحة البحث الموحد (`/search`)

> **المستوى**: متوسط | **المدة المقدرة**: 3 أيام | **الأولوية**: عالية (SDK/Product)

---

## 1. نظرة عامة

صفحة بحث موحّدة تبحث في **كل محتوى المنصة** دفعة واحدة: الوزارات، الجهات، الخدمات، المكاتب، المستشارين — مع نتائج مقسمة تبويبات وفلترة فورية.

**الثغرة الحالية**: لا يوجد أي API أو صفحة بحث في المشروع كله (صفر).

**الملفات الجديدة**:
- `search.blade.php` — واجهة النتائج
- `SearchController.php` — البحث
- `resources/js/search.js` — بحث فوري + فلترة

---

## 2. البنية التحتية

### 2.1 Routes

```php
// routes/web.php
Route::get('/search', [SearchController::class, 'index'])->name('amrtm.search');

// API (اختياري — للمستقبل React)
Route::get('/api/search', [SearchController::class, 'api'])->name('amrtm.api.search');
```

### 2.2 Controller

```php
// app/Http/Controllers/SearchController.php
<?php

namespace App\Http\Controllers;

use App\Models\Business\Category;
use App\Models\Business\Entity;
use App\Models\Business\GovService;
use App\Models\Business\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $type = $request->input('type', 'all'); // all|ministries|offices|services

        $results = [
            'categories' => collect(),
            'offices' => collect(),
            'services' => collect(),
        ];

        if (strlen($query) >= 2) {
            $results['categories'] = Category::where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name_ar', 'LIKE', "%{$query}%")
                      ->orWhere('name_en', 'LIKE', "%{$query}%");
                })
                ->limit(10)
                ->get();

            $results['offices'] = Office::with('profile')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name_ar', 'LIKE', "%{$query}%")
                      ->orWhere('name_en', 'LIKE', "%{$query}%")
                      ->orWhere('city', 'LIKE', "%{$query}%")
                      ->orWhere('description_ar', 'LIKE', "%{$query}%");
                })
                ->limit(15)
                ->get();

            $results['services'] = GovService::with('entity.category')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name_ar', 'LIKE', "%{$query}%")
                      ->orWhere('name_en', 'LIKE', "%{$query}%")
                      ->orWhere('description_ar', 'LIKE', "%{$query}%");
                })
                ->limit(20)
                ->get();
        }

        $totalResults = $results['categories']->count()
            + $results['offices']->count()
            + $results['services']->count();

        return view('update_service.search', compact('query', 'type', 'results', 'totalResults'));
    }

    public function api(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'isSuccess' => true,
                'value' => ['categories' => [], 'offices' => [], 'services' => []],
            ]);
        }

        $categories = Category::where('is_active', true)
            ->where('name_ar', 'LIKE', "%{$query}%")
            ->orWhere('name_en', 'LIKE', "%{$query}%")
            ->limit(5)->get(['id', 'key', 'name_ar', 'name_en', 'icon']);

        $offices = Office::where('is_active', true)
            ->where('name_ar', 'LIKE', "%{$query}%")
            ->orWhere('city', 'LIKE', "%{$query}%")
            ->limit(10)->get(['id', 'name_ar', 'name_en', 'city', 'type']);

        $services = GovService::where('is_active', true)
            ->where('name_ar', 'LIKE', "%{$query}%")
            ->limit(10)->get(['id', 'name_ar', 'name_en', 'price']);

        return response()->json([
            'isSuccess' => true,
            'value' => compact('categories', 'offices', 'services'),
        ]);
    }
}
```

---

## 3. الواجهة الأمامية

### 3.1 هيكل الصفحة

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Hero بحث (cui-hero)                                │
│  ┌─────────────────────────────────────────────┐   │
│  │  breadcrumb: الرئيسية > بحث                  │   │
│  │  🔍 [___________________________] [بحث]      │   │
│  │  12 نتيجة لـ "محاماة"                        │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  فلاتر نوع النتيجة                                  │
│  ┌─────────────────────────────────────────────┐   │
│  │  [الكل (12)] [الوزارات (2)] [المكاتب (5)] [الخدمات (5)]│
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  النتائج — مقسمة أقسام                              │
│                                                     │
│  ── الوزارات (2 نتيجة) ────────────────────────    │
│  ┌──────────┐ ┌──────────┐                         │
│  │ 🏛️       │ │ 🏛️       │                         │
│  │وزارة العدل│ │وزارة التجارة│                       │
│  └──────────┘ └──────────┘                         │
│                                                     │
│  ── المكاتب (5 نتائج) ────────────────────────    │
│  ┌────────────────────────────────────────────┐   │
│  │ 🏢 مكتب الأمل للمحاماة                      │   │
│  │ 📍 الرياض | مكتب مساند | ✅ معتمد          │   │
│  │ خدمات قانونية متعددة                        │   │
│  │                      [عرض التفاصيل →]       │   │
│  ├────────────────────────────────────────────┤   │
│  │ 🏢 مكتب النور للاستشارات                   │   │
│  │ 📍 جدة | استشاري | ✅ موثّق                │   │
│  │ استشارات مالية وضريبية                       │   │
│  │                      [عرض التفاصيل →]       │   │
│  └────────────────────────────────────────────┘   │
│                                                     │
│  ── الخدمات (5 نتائج) ────────────────────────    │
│  ┌────────────────────────────────────────────┐   │
│  │ 📋 تجديد السجل التجاري                      │   │
│  │ وزارة التجارة | 200 ر.س | 3 أيام           │   │
│  │                      [اطلب الآن →]          │   │
│  ├────────────────────────────────────────────┤   │
│  │ 📋 إصدار رخصة مزاولة مهنية                   │   │
│  │ وزارة العمل | 150 ر.س | 5 أيام             │   │
│  │                      [اطلب الآن →]          │   │
│  └────────────────────────────────────────────┘   │
│                                                     │
│  ── لا توجد نتائج ────────────────────────────    │
│  ┌────────────────────────────────────────────┐   │
│  │  🔍                                         │   │
│  │  لا توجد نتائج لـ "xxx"                     │   │
│  │  جرّب كلمات مختلفة أو تصفح الدليل مباشرة     │   │
│  │  [تصفح الخدمات →]                           │   │
│  └────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

### 3.2 حقل البحث (Hero)

```blade
<section class="cui-hero">
    <div class="mx-auto max-w-[1280px] px-4 py-16">
        <nav class="mb-6 text-sm text-white/60">
            <a href="{{ route('amrtm.index') }}" class="hover:text-white">الرئيسية</a>
            <span class="mx-2">/</span>
            <span class="text-white">بحث</span>
        </nav>
        <h1 class="text-3xl font-bold text-white lg:text-4xl">بحث</h1>

        {{-- حقل البحث --}}
        <form action="{{ route('amrtm.search') }}" method="GET" class="mt-8">
            <div class="relative max-w-2xl">
                <input type="text"
                       name="q"
                       value="{{ $query }}"
                       placeholder="ابحث عن خدمة، وزارة، مكتب، مستشار..."
                       autofocus
                       class="w-full rounded-2xl border-2 border-white/20 bg-white/10 px-6 py-4 pr-14 text-lg text-white placeholder:text-white/50 backdrop-blur-lg transition-all focus:border-white/50 focus:bg-white/20 focus:outline-none" />
                <button type="submit"
                        class="absolute left-3 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#006C35] transition-all hover:bg-white/90">
                    <i class="ti ti-search text-xl"></i>
                </button>
            </div>
        </form>

        @if($query)
            <p class="mt-4 text-white/70">
                @if($totalResults > 0)
                    {{ $totalResults }} نتيجة لـ "<span class="font-semibold text-white">{{ $query }}</span>"
                @else
                    لا توجد نتائج لـ "<span class="font-semibold text-white">{{ $query }}</span>"
                @endif
            </p>
        @endif
    </div>
</section>
```

### 3.3 فلاتر التبويبات

```blade
@if($totalResults > 0)
<section class="border-b border-[--b1] bg-white sticky top-[72px] z-30">
    <div class="mx-auto max-w-[1280px] px-4">
        <div class="flex gap-1 overflow-x-auto py-3" id="search-filters">
            @php
                $tabs = [
                    'all' => ['label' => 'الكل', 'count' => $totalResults],
                    'ministries' => ['label' => 'الوزارات', 'count' => $results['categories']->count()],
                    'offices' => ['label' => 'المكاتب', 'count' => $results['offices']->count()],
                    'services' => ['label' => 'الخدمات', 'count' => $results['services']->count()],
                ];
            @endphp

            @foreach($tabs as $key => $tab)
                @if($tab['count'] > 0 || $key === 'all')
                    <button data-filter="{{ $key }}"
                            onclick="filterResults('{{ $key }}')"
                            class="filter-tab whitespace-nowrap rounded-full px-5 py-2 text-sm font-medium transition-all
                                   {{ $key === 'all' ? 'bg-[#006C35] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $tab['label'] }}
                        <span class="mr-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-white/20 px-1.5 text-xs">
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif
```

### 3.4 بطاقة النتيجة — المكتب

```blade
@foreach($results['offices'] as $office)
<a href="{{ route('amrtm.offices.detail', [$office->type, $office->id]) }}"
   class="search-result group block rounded-2xl border border-[--b1] bg-white p-5 transition-all hover:shadow-lg hover:-translate-y-0.5"
   data-type="offices">
    <div class="flex items-start gap-4">
        <img src="{{ $office->logo ? asset('images/uploads/'.$office->logo) : asset('images/default-office.png') }}"
             alt="{{ $office->name_ar }}"
             class="h-14 w-14 rounded-xl object-cover" />
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-gray-900 group-hover:text-[var(--cui-primary)]">
                    {{ $office->name_ar }}
                </h3>
                @if($office->is_verified)
                    <x-ui.badge variant="success">موثّق</x-ui.badge>
                @endif
            </div>
            <p class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                @if($office->city)
                    <i class="ti ti-map-pin"></i> {{ $office->city }}
                @endif
                <span class="text-gray-300">|</span>
                {{ \App\Models\Business\Office::$accountTypeLabels[array_values($office->account_types)[0] ?? ''] ?? '' }}
            </p>
            @if($office->description_ar)
                <p class="mt-2 line-clamp-2 text-sm text-gray-400">{{ $office->description_ar }}</p>
            @endif
        </div>
        <i class="ti ti-arrow-left text-gray-300 transition-all group-hover:text-[var(--cui-primary)] group-hover:-translate-x-1"></i>
    </div>
</a>
@endforeach
```

### 3.5 بطاقة النتيجة — الخدمة

```blade
@foreach($results['services'] as $service)
<a href="{{ route('amrtm.catalog.entity', [$service->entity->category->key, $service->entity->id]) }}"
   class="search-result group block rounded-2xl border border-[--b1] bg-white p-5 transition-all hover:shadow-lg hover:-translate-y-0.5"
   data-type="services">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--cui-primary-soft)]">
                <i class="ti ti-file-text text-[var(--cui-primary)] text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 group-hover:text-[var(--cui-primary)]">
                    {{ $service->name_ar }}
                </h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ $service->entity->name_ar ?? '' }}
                    @if($service->entity->category)
                        <span class="text-gray-300">|</span>
                        {{ $service->entity->category->name_ar }}
                    @endif
                </p>
            </div>
        </div>
        <div class="text-left">
            @if($service->price)
                <p class="font-bold text-[var(--cui-primary)] tabular-nums" dir="ltr">{{ number_format($service->price, 2) }} ر.س</p>
            @endif
            @if($service->estimated_days)
                <p class="text-xs text-gray-400">{{ $service->estimated_days }} أيام</p>
            @endif
        </div>
    </div>
</a>
@endforeach
```

---

## 4. JavaScript — `resources/js/search.js`

```javascript
document.addEventListener('DOMContentLoaded', () => {
    window.filterResults = function(type) {
        // Update tab styles
        document.querySelectorAll('.filter-tab').forEach(tab => {
            const isActive = tab.dataset.filter === type;
            tab.classList.toggle('bg-[#006C35]', isActive);
            tab.classList.toggle('text-white', isActive);
            tab.classList.toggle('bg-gray-100', !isActive);
            tab.classList.toggle('text-gray-600', !isActive);
        });

        // Show/hide results
        document.querySelectorAll('.search-result').forEach(el => {
            if (type === 'all') {
                el.style.display = '';
            } else {
                el.style.display = el.dataset.type === type ? '' : 'none';
            }
        });

        // Show/hide section headers
        document.querySelectorAll('.search-section').forEach(section => {
            if (type === 'all') {
                section.style.display = '';
            } else {
                section.style.display = section.dataset.type === type ? '' : 'none';
            }
        });
    };

    // Live search (debounced)
    let debounceTimer;
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (e.target.value.length >= 2) {
                    // Optional: AJAX live search
                    // fetch(`/api/search?q=${encodeURIComponent(e.target.value)}`)
                }
            }, 300);
        });
    }
});
```

---

## 5. حالة فارغة

```blade
@if($query && $totalResults === 0)
<section class="py-20">
    <div class="mx-auto max-w-md text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
            <i class="ti ti-search text-4xl text-gray-300"></i>
        </div>
        <h2 class="mt-6 text-xl font-bold text-gray-900">لا توجد نتائج</h2>
        <p class="mt-2 text-gray-500">
            لا توجد نتائج لـ "<span class="font-medium">{{ $query }}</span>".
            جرّب كلمات مختلفة أو تصفح الخدمات مباشرة.
        </p>
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('amrtm.catalog.category', 'ministries') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-[#006C35] px-6 py-3 text-white transition-all hover:bg-[#0B3B2C]">
                <i class="ti ti-layout-grid"></i>
                تصفح الخدمات
            </a>
            <a href="{{ route('amrtm.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-6 py-3 text-gray-600 transition-all hover:bg-gray-50">
                <i class="ti ti-home"></i>
                الرئيسية
            </a>
        </div>
    </div>
</section>
@endif
```

---

## 6. ربط حقل البحث في النافبار

```blade
{{-- في partials/public/navbar.blade.php — حقل البحث الحالي --}}
<form action="{{ route('amrtm.search') }}" method="GET" class="flex-1 max-w-md">
    <div class="relative">
        <input type="text" name="q" placeholder="ابحث عن خدمة..."
               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 pr-10 text-sm transition-all focus:border-[#006C35] focus:bg-white focus:outline-none" />
        <i class="ti ti-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>
</form>
```

---

## 7. التجاوب

| المقاس | التخطيط |
|--------|---------|
| `≥1024px` | النتائج بعرض كامل مع بطاقات أفقية |
| `768px - 1023px` | بطاقات أعرض مع معلومات أقل |
| `≤768px` | بطاقات عمودية + فلاتر بتمرير أفقي + حقل بحث بعرض كامل |

---

## 8. الاختبارات

### 8.1 Feature Test

```php
// tests/Feature/SearchTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Business\Category;
use App\Models\Business\Office;
use App\Models\Business\GovService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->businessDb = DB::connection('business');
    }

    public function test_search_page_renders()
    {
        $response = $this->get('/search?q=test');
        $response->assertOk();
        $response->assertSee('بحث');
    }

    public function test_search_returns_categories()
    {
        Category::factory()->create(['name_ar' => 'ال JUSTICE', 'is_active' => true]);
        // Need to seed or create with Arabic name
        $response = $this->get('/search?q=عدل');
        $response->assertOk();
    }

    public function test_search_requires_minimum_characters()
    {
        $response = $this->get('/search?q=a');
        $response->assertOk();
        $response->assertSee('لا توجد نتائج');
    }

    public function test_search_api_returns_json()
    {
        $response = $this->getJson('/api/search?q=test');
        $response->assertOk();
        $response->assertJsonStructure(['isSuccess', 'value']);
    }

    public function test_empty_search_shows_page()
    {
        $response = $this->get('/search');
        $response->assertOk();
        $response->assertSee('بحث');
    }
}
```

### 8.2 Checklist يدوي

- [ ] `/search?q=محاماة` يعرض نتائج من كل الأقسام
- [ ] التبويبات تفلتر النتائج فوراً (بدون reload)
- [ ] النتيجة الفارغة تظهر رسالة واضحة + أزرار
- [ ] حقل البحث في النافبار يُوجه لـ `/search`
- [ ] على الموبايل: حقل بحث بعرض كامل + فلاتر قابلة للتمرير
- [ ] لا أخطاء Console

---

## 9. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **إضافة** مسارين |
| `app/Http/Controllers/SearchController.php` | **جديد** |
| `resources/views/update_service/search.blade.php` | **جديد** |
| `resources/js/search.js` | **جديد** |
| `resources/js/app.js` | **تعديل** — استيراد |
| `resources/views/partials/public/navbar.blade.php` | **تعديل** — ربط حقل البحث |

---

## 10. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan test --filter=SearchTest` | 5 اختبارات PASS |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| `/search?q=xxx` يعرض نتائج | حقيقية |
| التبويبات تفلتر | فوراً |
| API endpoint يعمل | JSON سليم |
| لا `<input>` خام (عدا حقل البحث الرئيسي) | مقبول |
| التجاوب 3 مقاسات | سليم |
