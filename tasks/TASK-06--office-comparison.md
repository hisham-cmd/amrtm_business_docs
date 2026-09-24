# TASK-06: ميزة مقارنة المكاتب (`/compare`)

> **المستوى**: متوسط | **المدة المقدرة**: 3 أيام | **الأولوية**: عالية

---

## 1. نظرة عامة

ميزة تُمكّن العميل من مقارنة عدة مكاتب خدمات أو مستشارين جنباً إلى جنب في جدول مقارنة شامل، لمساعدته على اتخاذ قرار أفضل عند اختيار مقدم الخدمة.

**الملفات المتأثرة**:
- **جديد**: `compare.blade.php` — صفحة المقارنة
- **جديد**: `CompareController.php`
- **جديد**: `resources/js/compare.js`
- **تعديل**: `office_directory.blade.php` — زر "قارن"
- **تعديل**: `consultants_directory.blade.php` — زر "قارن"
- **تعديل**: `office_detail.blade.php` — زر "قارن"
- **تعديل**: `consultant_detail.blade.php` — زر "قارن"
- **تعديل**: `layouts/public.blade.php` — شريط المقارنة

---

## 2. البنية التحتية

### 2.1 Route

```php
// routes/web.php
Route::get('/compare', [CompareController::class, 'index'])->name('amrtm.compare');
```

### 2.2 Controller

```php
// app/Http/Controllers/CompareController.php
<?php

namespace App\Http\Controllers;

use App\Models\Business\Office;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function index(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) < 2) {
            return redirect()->route('amrtm.index')
                ->with('error', 'يجب اختيار مكتبين على الأقل للمقارنة');
        }

        $ids = array_slice(array_unique(array_map('intval', $ids)), 0, 4);

        $offices = Office::withCount([
            'requests as completed_requests_count' => fn($q) => $q->where('status', 'done'),
            'services as services_count',
        ])
        ->with(['profile', 'specialtiesRelation'])
        ->whereIn('id', $ids)
        ->get();

        return view('update_service.compare', compact('offices'));
    }
}
```

### 2.3 لا يحتاج Migration

المقارنة تعتمد على بيانات موجودة.

---

## 3. الواجهة — صفحة المقارنة

### 3.1 الهيكل

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│  Hero Section (cui-hero)                            │
│  breadcrumb: الرئيسية > مقارنة المكاتب               │
│  العنوان: مقارنة المكاتب                             │
├─────────────────────────────────────────────────────┤
│                                                     │
│  جدول المقارنة (overflow-x-auto)                    │
│  ┌────────────┬───────────┬───────────┬───────────┐│
│  │ المعيار     │ مكتب 1    │ مكتب 2    │ مكتب 3    ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │            │  ┌─────┐  │  ┌─────┐  │  ┌─────┐  ││
│  │  الشعار     │  │ 🏢  │  │  │ 🏢  │  │  │ 🏢  │  ││
│  │            │  └─────┘  │  └─────┘  │  └─────┘  ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  الاسم     │ مكتب الأمل│ مكتب النور│ مكتب السلام││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  المدينة    │ الرياض    │ جدة      │ الدمام    ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  النوع     │ مساند     │ استشاري  │ مساند     ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  التخصصات   │ [رقاقة]   │ [رقاقة]  │ [رقاقة]  ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  الخدمات   │ 12        │ 8        │ 15       ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  الطلبات    │ 45        │ 30       │ 60       ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  الحالة     │ ✅ معتمد  │ ✅ موثّق  │ ✅ معتمد  ││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  الهاتف     │ 05XX XXXX│ 05XX XXXX│ 05XX XXXX││
│  ├────────────┼───────────┼───────────┼───────────┤│
│  │  التواصل    │ [تواصل]  │ [تواصل]  │ [تواصل]  ││
│  └────────────┴───────────┴───────────┴───────────┘│
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │  + أضف مكتباً آخر للمقارنة                    │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

### 3.2 الكود

```blade
@extends('layouts.public')

@section('content')
@include('partials.public.corporate-ui')

<section class="cui-hero">
    <div class="mx-auto max-w-[1280px] px-4 py-16">
        <nav class="mb-6 text-sm text-white/60">
            <a href="{{ route('amrtm.index') }}" class="hover:text-white">الرئيسية</a>
            <span class="mx-2">/</span>
            <span class="text-white">مقارنة المكاتب</span>
        </nav>
        <h1 class="text-4xl font-bold text-white">مقارنة المكاتب</h1>
        <p class="mt-3 text-white/70">{{ $offices->count() }} مكاتب محددة للمقارنة</p>
    </div>
</section>

<section class="py-12">
    <div class="mx-auto max-w-[1280px] px-4">
        <div class="overflow-x-auto rounded-2xl border border-[--b1] bg-white shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
            <table class="w-full min-w-[700px]">
                <thead>
                    <tr class="border-b border-[--b1] bg-[#006C35]">
                        <th class="p-5 text-right text-sm font-semibold text-white w-[180px]">المعيار</th>
                        @foreach($offices as $office)
                            <th class="p-5 text-center text-sm font-semibold text-white">
                                <div class="flex flex-col items-center gap-3">
                                    <img src="{{ $office->logo ? asset('images/uploads/'.$office->logo) : asset('images/default-office.png') }}"
                                         alt="{{ $office->name_ar }}"
                                         class="h-16 w-16 rounded-xl object-cover border-2 border-white/30" />
                                    <span>{{ $office->name_ar }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- المدينة --}}
                    <tr class="border-b border-[--b1] bg-gray-50/50">
                        <td class="p-4 text-sm font-medium text-gray-500">المدينة</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center text-sm text-gray-900">{{ $office->city ?? '—' }}</td>
                        @endforeach
                    </tr>

                    {{-- النوع --}}
                    <tr class="border-b border-[--b1]">
                        <td class="p-4 text-sm font-medium text-gray-500">نوع المنشأة</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center text-sm text-gray-900">
                                {{ \App\Models\Business\Office::$accountTypeLabels[array_values($office->account_types)[0] ?? ''] ?? '—' }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- التخصصات --}}
                    <tr class="border-b border-[--b1] bg-gray-50/50">
                        <td class="p-4 text-sm font-medium text-gray-500">التخصصات</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center">
                                <div class="flex flex-wrap justify-center gap-1">
                                    @foreach($office->specialtiesRelation->take(3) as $spec)
                                        <span class="inline-block rounded-full bg-[var(--cui-primary-soft)] px-2.5 py-0.5 text-xs text-[var(--cui-primary)]">
                                            {{ app()->getLocale() === 'ar' ? $spec->name_ar : $spec->name_en }}
                                        </span>
                                    @endforeach
                                    @if($office->specialtiesRelation->count() > 3)
                                        <span class="text-xs text-gray-400">+{{ $office->specialtiesRelation->count() - 3 }}</span>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>

                    {{-- عدد الخدمات --}}
                    <tr class="border-b border-[--b1]">
                        <td class="p-4 text-sm font-medium text-gray-500">عدد الخدمات</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center text-lg font-bold text-[var(--cui-primary)] tabular-nums" dir="ltr">
                                {{ $office->services_count }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- الطلبات المكتملة --}}
                    <tr class="border-b border-[--b1] bg-gray-50/50">
                        <td class="p-4 text-sm font-medium text-gray-500">الطلبات المكتملة</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center text-lg font-bold text-gray-900 tabular-nums" dir="ltr">
                                {{ $office->completed_requests_count }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- الحالة --}}
                    <tr class="border-b border-[--b1]">
                        <td class="p-4 text-sm font-medium text-gray-500">الحالة</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center">
                                @if($office->is_verified)
                                    <x-ui.badge variant="success">موثّق</x-ui.badge>
                                @elseif($office->is_active)
                                    <x-ui.badge variant="info">نشط</x-ui.badge>
                                @else
                                    <x-ui.badge variant="secondary">غير نشط</x-ui.badge>
                                @endif
                            </td>
                        @endforeach
                    </tr>

                    {{-- الهاتف --}}
                    <tr class="border-b border-[--b1] bg-gray-50/50">
                        <td class="p-4 text-sm font-medium text-gray-500">الهاتف</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center text-sm" dir="ltr">{{ $office->phone }}</td>
                        @endforeach
                    </tr>

                    {{-- البريد --}}
                    <tr class="border-b border-[--b1]">
                        <td class="p-4 text-sm font-medium text-gray-500">البريد الإلكتروني</td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center text-sm" dir="ltr">{{ $office->email }}</td>
                        @endforeach
                    </tr>

                    {{-- زر التواصل --}}
                    <tr>
                        <td class="p-4"></td>
                        @foreach($offices as $office)
                            <td class="p-4 text-center">
                                <a href="tel:{{ $office->phone }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-[#006C35] px-5 py-2.5 text-sm font-medium text-white transition-all hover:bg-[#0B3B2C] hover:shadow-lg">
                                    <i class="ti ti-phone"></i>
                                    تواصل
                                </a>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- زر إضافة مكتب --}}
        <div class="mt-8 text-center">
            <a href="{{ route('amrtm.offices.directory', 'services') }}"
               class="inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-gray-300 px-6 py-3 text-gray-500 transition-all hover:border-[#006C35] hover:text-[var(--cui-primary)]">
                <i class="ti ti-plus"></i>
                أضف مكتباً آخر للمقارنة
            </a>
        </div>
    </div>
</section>
@endsection
```

---

## 4. شريط المقارنة الثابت (Sticky Bar)

### 4.1 في `layouts/public.blade.php`

```blade
{{-- قبل </body> --}}
<div id="compare-bar"
     class="fixed bottom-0 left-0 right-0 z-50 border-t border-[--b1] bg-white/95 backdrop-blur-lg shadow-[0_-4px_24px_rgba(0,0,0,0.1)] transition-transform duration-300"
     style="transform: translateY(100%);">
    <div class="mx-auto flex max-w-[1280px] items-center justify-between px-4 py-3">
        <div class="flex items-center gap-3">
            <i class="ti ti-git-compare text-[var(--cui-primary)] text-xl"></i>
            <span class="text-sm font-medium text-gray-900">
                <span id="compare-count">0</span> مكاتب محددة
            </span>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="AMRTM_COMPARE.clear()"
                    class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 transition-colors">
                احذف الكل
            </button>
            <a id="compare-link" href="/compare"
               class="inline-flex items-center gap-2 rounded-xl bg-[#006C35] px-5 py-2.5 text-sm font-medium text-white transition-all hover:bg-[#0B3B2C]">
                قارن الآن
                <i class="ti ti-arrow-left"></i>
            </a>
        </div>
    </div>
</div>
```

---

## 5. JavaScript — `resources/js/compare.js`

```javascript
const COMPARE_KEY = 'amrtm_compare_offices';
const MAX_COMPARE = 4;

window.AMRTM_COMPARE = {
    getIds() {
        try {
            return JSON.parse(localStorage.getItem(COMPARE_KEY)) || [];
        } catch {
            return [];
        }
    },

    add(id) {
        const ids = this.getIds();
        if (ids.includes(id)) return false;
        if (ids.length >= MAX_COMPARE) {
            alert('يمكنك مقارنة 4 مكاتب كحد أقصى');
            return false;
        }
        ids.push(id);
        localStorage.setItem(COMPARE_KEY, JSON.stringify(ids));
        this.updateUI();
        return true;
    },

    remove(id) {
        const ids = this.getIds().filter(i => i !== id);
        localStorage.setItem(COMPARE_KEY, JSON.stringify(ids));
        this.updateUI();
    },

    toggle(id) {
        if (this.getIds().includes(id)) {
            this.remove(id);
        } else {
            this.add(id);
        }
    },

    clear() {
        localStorage.removeItem(COMPARE_KEY);
        this.updateUI();
    },

    updateUI() {
        const ids = this.getIds();
        const bar = document.getElementById('compare-bar');
        const count = document.getElementById('compare-count');
        const link = document.getElementById('compare-link');

        if (bar) {
            bar.style.transform = ids.length >= 2 ? 'translateY(0)' : 'translateY(100%)';
        }
        if (count) count.textContent = ids.length;
        if (link) link.href = `/compare?ids=${ids.join(',')}`;

        document.querySelectorAll('.compare-add-btn').forEach(btn => {
            const officeId = parseInt(btn.dataset.officeId);
            const isSelected = ids.includes(officeId);
            btn.classList.toggle('bg-[#006C35]', isSelected);
            btn.classList.toggle('text-white', isSelected);
            btn.classList.toggle('border-[#006C35]', isSelected);
            btn.classList.toggle('bg-white', !isSelected);
            btn.classList.toggle('text-gray-600', !isSelected);
            btn.classList.toggle('border-gray-200', !isSelected);

            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('ti-git-compare', !isSelected);
                icon.classList.toggle('ti-check', isSelected);
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => AMRTM_COMPARE.updateUI());
window.toggleCompare = function(btn) {
    AMRTM_COMPARE.toggle(parseInt(btn.dataset.officeId));
};
```

---

## 6. زر "قارن" في صفحات الدليل

### 6.1 في كل بطاقة مكتب

```blade
{{-- يُضاف في نهاية كل بطاقة مكتب في office_directory / consultants_directory --}}
<button onclick="toggleCompare(this)"
        data-office-id="{{ $office->id }}"
        class="compare-add-btn inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 transition-all hover:border-[#006C35] hover:text-[var(--cui-primary)]">
    <i class="ti ti-git-compare"></i>
    قارن
</button>
```

---

## 7. التجاوب

| المقاس | السلوك |
|--------|--------|
| `≥1024px` | جدول أفقي كامل — لا تغيير |
| `768px - 1023px` | جدول بـ `overflow-x-auto` + تمرير أفقي |
| `≤768px` | البطاقات العمودية (كل مكتب = بطاقة منفصلة) |

### 7.1 الموبايل — بديل الجدول

```blade
{{-- على الموبايل: بطاقات بدل جدول --}}
<div class="lg:hidden space-y-4">
    @foreach($offices as $office)
        <div class="rounded-2xl border border-[--b1] bg-white p-5">
            <div class="flex items-center gap-4">
                <img src="{{ $office->logo ? asset('images/uploads/'.$office->logo) : asset('images/default-office.png') }}"
                     class="h-14 w-14 rounded-xl object-cover" />
                <div>
                    <h3 class="font-bold text-gray-900">{{ $office->name_ar }}</h3>
                    <p class="text-sm text-gray-500">{{ $office->city ?? '' }}</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-gray-400">الخدمات:</span> <strong>{{ $office->services_count }}</strong></div>
                <div><span class="text-gray-400">الطلبات:</span> <strong>{{ $office->completed_requests_count }}</strong></div>
                <div><span class="text-gray-400">الهاتف:</span> <strong dir="ltr">{{ $office->phone }}</strong></div>
                <div><span class="text-gray-400">الحالة:</span>
                    @if($office->is_verified) <x-ui.badge variant="success">موثّق</x-ui.badge> @endif
                </div>
            </div>
            <a href="tel:{{ $office->phone }}"
               class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-[#006C35] py-2.5 text-sm font-medium text-white">
                <i class="ti ti-phone"></i> تواصل
            </a>
        </div>
    @endforeach
</div>
```

---

## 8. الاختبارات

### 8.1 Feature Test

```php
// tests/Feature/CompareTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Business\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->businessDb = DB::connection('business');
    }

    public function test_compare_page_renders_with_valid_ids()
    {
        $office1 = Office::factory()->create(['is_active' => true]);
        $office2 = Office::factory()->create(['is_active' => true]);

        $response = $this->get("/compare?ids={$office1->id},{$office2->id}");
        $response->assertOk();
        $response->assertSee($office1->name_ar);
        $response->assertSee($office2->name_ar);
    }

    public function test_compare_redirects_with_less_than_two_ids()
    {
        $office = Office::factory()->create();
        $response = $this->get("/compare?ids={$office->id}");
        $response->assertRedirect();
    }

    public function test_compare_limits_to_four_offices()
    {
        $offices = Office::factory()->count(6)->create(['is_active' => true]);
        $ids = $offices->pluck('id')->implode(',');
        $response = $this->get("/compare?ids={$ids}");
        $response->assertOk();
    }

    public function test_compare_page_uses_corporate_ui()
    {
        $office1 = Office::factory()->create(['is_active' => true]);
        $office2 = Office::factory()->create(['is_active' => true]);

        $response = $this->get("/compare?ids={$office1->id},{$office2->id}");
        $response->assertSee('cui-hero');
        $response->assertSee('corporate-ui');
    }
}
```

### 8.2 Checklist يدوي

- [ ] من `/offices/law`، نقر "قارن" على مكتبين → يظهر الشريط
- [ ] نقر "قارن الآن" → `/compare?ids=1,2` تُعرض
- [ ] جدول المقارنة يعرض كل الصفوف المطلوبة
- [ ] على الموبايل (375px) → تتحول إلى بطاقات
- [ ] "احذف الكل" يُخفي الشريط
- [ ] 5 مكاتب → رسالة تنبيه
- [ ] `localStorage` يبقى بعد Refresh
- [ ] لا أخطاء Console

---

## 9. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **إضافة** مسار `/compare` |
| `app/Http/Controllers/CompareController.php` | **جديد** |
| `resources/views/update_service/compare.blade.php` | **جديد** |
| `resources/js/compare.js` | **جديد** |
| `resources/js/app.js` | **تعديل** — استيراد `compare.js` |
| `resources/views/update_service/office_directory.blade.php` | **تعديل** — زر قارن |
| `resources/views/update_service/consultants_directory.blade.php` | **تعديل** — زر قارن |
| `resources/views/update_service/office_detail.blade.php` | **تعديل** — زر قارن |
| `resources/views/update_service/consultant_detail.blade.php` | **تعديل** — زر قارن |
| `resources/views/layouts/public.blade.php` | **تعديل** — شريط المقارنة |

---

## 10. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan route:list --path=compare` | المسار موجود |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| `php artisan test --filter=CompareTest` | 4 اختبارات PASS |
| لا أخطاء Console | صفر |
| التجاوب 375/768/1280 | سليم |
| `localStorage` يعمل | القائمة تبقى |
| الشريط يظهر/يختفي | عند 2+ مكاتب |
