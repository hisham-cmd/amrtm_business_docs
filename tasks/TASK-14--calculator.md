# TASK-14: حاسبة تكلفة الخدمة (`/calculator`)

> **المستوى**: متوسط | **المدة المقدرة**: 3 أيام | **الأولوية**: متوسطة (Conversion)

---

## 1. نظرة عامة

أداة تفاعلية تُقدّر تكلفة الخدمة المطلوبة مسبقاً قبل تقديم الطلب — تزيد ثقة العميل وتحوّل الزوار لعملاء.

**الملف الجديد**: `calculator.blade.php`

---

## 2. Route

```php
// routes/web.php
Route::get('/calculator', function () {
    $categories = \App\Models\Business\Category::with('entities.services')
        ->where('is_active', true)
        ->get();
    return view('update_service.calculator', compact('categories'));
})->name('amrtm.calculator');
```

---

## 3. الواجهة — هيكل الصفحة

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Hero (cui-hero)                                    │
│  breadcrumb: الرئيسية > حاسبة التكلفة               │
│  العنوان: حاسبة تكلفة الخدمة                       │
│  الوصف: اعرف تكلفة خدمتك مسبقاً قبل تقديم الطلب    │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  الحاسبة — خطوات                                   │
│                                                     │
│  الخطوة 1: اختر_TYPE الخدمة                        │
│  ┌─────────────────────────────────────────────┐   │
│  │  [🏛️ وزارات] [🏢 مكاتب] [📋 خدمات حكومية]  │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  الخطوة 2: اختر الجهة / المكتب                     │
│  ┌─────────────────────────────────────────────┐   │
│  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐      │   │
│  │  │ وزارة │ │ وزارة │ │ وزارة │ │ وزارة │      │   │
│  │  │التجارة│ │العمل  │ │العدل  │ │السياحة│      │   │
│  │  └──────┘ └──────┘ └──────┘ └──────┘      │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  الخطوة 3: اختر الخدمة                             │
│  ┌─────────────────────────────────────────────┐   │
│  │  ☐ تجديد السجل التجاري        200 ر.س      │   │
│  │  ☐ إصدار سجل تجاري جديد      300 ر.س      │   │
│  │  ☐ تعديل بيانات السجل         100 ر.س      │   │
│  │  ☐ إصدار شهادة سجل تجاري      150 ر.س      │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  خيارات إضافية                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │  ☐ خدمة تعقيب إضافية         +50 ر.س      │   │
│  │  ☐ نقل سريع                  +100 ر.س      │   │
│  │  ☐ استشارة قانونية           +200 ر.س      │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  ملخص التكلفة                                       │
│  ┌─────────────────────────────────────────────┐   │
│  │  سعر الخدمة الأساسي           200.00 ر.س   │   │
│  │  تعقيب إضافي                  + 50.00 ر.س   │   │
│  │  نقل سريع                     +100.00 ر.س   │   │
│  │  ──────────────────────────────────────── │   │
│  │  المجموع الكلي               350.00 ر.س   │   │
│  │  رسوم المنصة (10%)           + 35.00 ر.س   │   │
│  │  ════════════════════════════════════════ │   │
│  │  الإجمالي النهائي            385.00 ر.س   │   │
│  │                                               │   │
│  │  ⏱️ الوقت المقدر: 3-5 أيام عمل               │   │
│  │                                               │   │
│  │  [اطلب الآن →]                               │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

---

## 4. الكود

### 4.1 الصفحة

```blade
@extends('layouts.public')

@section('content')
@include('partials.public.corporate-ui')

<section class="cui-hero">
    <div class="mx-auto max-w-[1280px] px-4 py-16">
        <nav class="mb-6 text-sm text-white/60">
            <a href="{{ route('amrtm.index') }}" class="hover:text-white">الرئيسية</a>
            <span class="mx-2">/</span>
            <span class="text-white">حاسبة التكلفة</span>
        </nav>
        <h1 class="text-4xl font-bold text-white">حاسبة تكلفة الخدمة</h1>
        <p class="mt-3 text-white/70">اعرف تكلفة خدمتك مسبقاً قبل تقديم الطلب</p>
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-4xl px-4">

        {{-- الخطوة 1: النوع --}}
        <div id="step-1" class="mb-12">
            <h2 class="mb-6 flex items-center gap-3 text-xl font-bold text-gray-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#006C35] text-sm text-white">1</span>
                اختر نوع الخدمة
            </h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <button onclick="selectType('ministries')"
                        class="calc-type-btn rounded-2xl border-2 border-gray-200 p-6 text-center transition-all hover:border-[#006C35] hover:bg-[var(--cui-primary-soft)]">
                    <i class="ti ti-building-government text-3xl text-[var(--cui-primary)]"></i>
                    <p class="mt-3 font-semibold text-gray-900">خدمات حكومية</p>
                    <p class="mt-1 text-sm text-gray-400">الوزارات والجهات الحكومية</p>
                </button>
                <button onclick="selectType('offices')"
                        class="calc-type-btn rounded-2xl border-2 border-gray-200 p-6 text-center transition-all hover:border-[#006C35] hover:bg-[var(--cui-primary-soft)]">
                    <i class="ti ti-building text-3xl text-[var(--cui-primary)]"></i>
                    <p class="mt-3 font-semibold text-gray-900">المكاتب</p>
                    <p class="mt-1 text-sm text-gray-400">المكاتب المساعدة والاستشارية</p>
                </button>
                <button onclick="selectType('custom')"
                        class="calc-type-btn rounded-2xl border-2 border-gray-200 p-6 text-center transition-all hover:border-[#006C35] hover:bg-[var(--cui-primary-soft)]">
                    <i class="ti ti-calculator text-3xl text-[var(--cui-primary)]"></i>
                    <p class="mt-3 font-semibold text-gray-900">حاسبة مخصصة</p>
                    <p class="mt-1 text-sm text-gray-400">أدخل الأسعار يدوياً</p>
                </button>
            </div>
        </div>

        {{-- الخطوة 2: الجهة --}}
        <div id="step-2" class="mb-12 hidden">
            <h2 class="mb-6 flex items-center gap-3 text-xl font-bold text-gray-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#006C35] text-sm text-white">2</span>
                اختر الجهة
            </h2>
            <div id="entities-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {{-- يُملأ ديناميكياً --}}
            </div>
        </div>

        {{-- الخطوة 3: الخدمة --}}
        <div id="step-3" class="mb-12 hidden">
            <h2 class="mb-6 flex items-center gap-3 text-xl font-bold text-gray-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#006C35] text-sm text-white">3</span>
                اختر الخدمة
            </h2>
            <div id="services-list" class="space-y-3">
                {{-- يُملأ ديناميكياً --}}
            </div>
        </div>

        {{-- ملخص التكلفة --}}
        <div id="cost-summary" class="hidden rounded-2xl border-2 border-[#006C35] bg-white p-8 shadow-lg">
            <h2 class="mb-6 text-xl font-bold text-gray-900">ملخص التكلفة</h2>
            <div id="cost-lines" class="space-y-3">
                {{-- يُملأ ديناميكياً --}}
            </div>
            <div class="mt-6 border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between text-lg font-bold">
                    <span>الإجمالي النهائي</span>
                    <span id="total-cost" class="text-[var(--cui-primary)] tabular-nums" dir="ltr">0.00 ر.س</span>
                </div>
                <p class="mt-2 text-sm text-gray-400" id="estimated-time">⏱️ الوقت المقدر: --</p>
            </div>
            <a href="#" id="order-link"
               class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-[#006C35] py-3 text-white font-medium transition-all hover:bg-[#0B3B2C]">
                <i class="ti ti-arrow-left"></i>
                اطلب الآن
            </a>
        </div>
    </div>
</section>

@include('partials.public.footer')
@endsection

@section('scripts')
<script>
// Data from server
const SERVICES_DATA = @json($categories);

let selectedType = null;
let selectedEntity = null;
let selectedServices = [];
let extras = [
    { name: 'خدمة تعقيب إضافي', price: 50, active: false },
    { name: 'نقل سريع', price: 100, active: false },
    { name: 'استشارة قانونية', price: 200, active: false },
];
const PLATFORM_FEE_RATE = 0.10; // 10%

function selectType(type) {
    selectedType = type;
    document.querySelectorAll('.calc-type-btn').forEach(btn => {
        btn.classList.remove('border-[#006C35]', 'bg-[var(--cui-primary-soft)]');
        btn.classList.add('border-gray-200');
    });
    event.currentTarget.classList.add('border-[#006C35]', 'bg-[var(--cui-primary-soft)]');
    event.currentTarget.classList.remove('border-gray-200');

    if (type === 'custom') {
        document.getElementById('step-2').classList.add('hidden');
        document.getElementById('step-3').classList.add('hidden');
        showCustomCalc();
        return;
    }

    // Show entities for this type
    const grid = document.getElementById('entities-grid');
    grid.innerHTML = '';
    SERVICES_DATA.forEach(cat => {
        cat.entities?.forEach(ent => {
            if (ent.services?.length) {
                grid.innerHTML += `
                    <button onclick="selectEntity(${ent.id}, '${ent.name_ar}')"
                            class="entity-btn rounded-xl border border-gray-200 p-4 text-center transition-all hover:border-[#006C35] hover:bg-[var(--cui-primary-soft)]">
                        <p class="font-medium text-gray-900">${ent.name_ar}</p>
                        <p class="mt-1 text-xs text-gray-400">${ent.services.length} خدمة</p>
                    </button>`;
            }
        });
    });
    document.getElementById('step-2').classList.remove('hidden');
    document.getElementById('step-3').classList.add('hidden');
    document.getElementById('cost-summary').classList.add('hidden');
}

function selectEntity(id, name) {
    selectedEntity = { id, name };
    document.querySelectorAll('.entity-btn').forEach(btn => {
        btn.classList.remove('border-[#006C35]', 'bg-[var(--cui-primary-soft)]');
    });
    event.currentTarget.classList.add('border-[#006C35]', 'bg-[var(--cui-primary-soft)]');

    // Find services for this entity
    let services = [];
    SERVICES_DATA.forEach(cat => {
        cat.entities?.forEach(ent => {
            if (ent.id === id) services = ent.services || [];
        });
    });

    const list = document.getElementById('services-list');
    list.innerHTML = '';
    services.forEach(svc => {
        list.innerHTML += `
            <label class="flex items-center justify-between rounded-xl border border-gray-200 p-4 cursor-pointer transition-all hover:border-[#006C35] has-[:checked]:border-[#006C35] has-[:checked]:bg-[var(--cui-primary-soft)]">
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="service" value="${svc.id}"
                           data-price="${svc.price || 0}" data-name="${svc.name_ar}"
                           onchange="calculateTotal()"
                           class="h-4 w-4 rounded text-[#006C35] focus:ring-[#006C35]" />
                    <span class="font-medium text-gray-900">${svc.name_ar}</span>
                </div>
                <span class="font-bold text-[var(--cui-primary)] tabular-nums" dir="ltr">
                    ${svc.price ? svc.price.toFixed(2) + ' ر.س' : 'اتصل بنا'}
                </span>
            </label>`;
    });

    document.getElementById('step-3').classList.remove('hidden');
    document.getElementById('cost-summary').classList.add('hidden');
}

function calculateTotal() {
    let base = 0;
    const lines = [];
    document.querySelectorAll('input[name="service"]:checked').forEach(cb => {
        const price = parseFloat(cb.dataset.price) || 0;
        base += price;
        lines.push({ label: cb.dataset.name, amount: price });
    });

    extras.forEach(ext => {
        if (ext.active) {
            base += ext.price;
            lines.push({ label: ext.name, amount: ext.price });
        }
    });

    const fee = base * PLATFORM_FEE_RATE;
    const total = base + fee;

    const container = document.getElementById('cost-lines');
    container.innerHTML = lines.map(l =>
        `<div class="flex justify-between text-sm">
            <span class="text-gray-600">${l.label}</span>
            <span class="font-medium tabular-nums" dir="ltr">${l.amount.toFixed(2)} ر.س</span>
        </div>`
    ).join('');

    if (lines.length === 0) {
        document.getElementById('cost-summary').classList.add('hidden');
        return;
    }

    container.innerHTML += `
        <div class="flex justify-between text-sm border-t border-gray-100 pt-3 mt-3">
            <span class="text-gray-500">رسوم المنصة (10%)</span>
            <span class="tabular-nums" dir="ltr">${fee.toFixed(2)} ر.س</span>
        </div>`;

    document.getElementById('total-cost').textContent = total.toFixed(2) + ' ر.س';
    document.getElementById('estimated-time').textContent = '⏱️ الوقت المقدر: 3-5 أيام عمل';
    document.getElementById('cost-summary').classList.remove('hidden');
}

function showCustomCalc() {
    const list = document.getElementById('services-list');
    list.innerHTML = `
        <div class="rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">اسم الخدمة</label>
                <input type="text" id="custom-name" placeholder="مثال: تجديد سجل تجاري"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-[#006C35] focus:ring-2 focus:ring-[#006C35]/20 focus:outline-none" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">التكلفة (ر.س)</label>
                <input type="number" id="custom-price" min="0" step="0.01" placeholder="0.00"
                       dir="ltr"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-[#006C35] focus:ring-2 focus:ring-[#006C35]/20 focus:outline-none" />
            </div>
            <button onclick="addCustomService()" class="rounded-lg bg-[#006C35] px-4 py-2 text-sm text-white">إضافة</button>
        </div>`;
    document.getElementById('step-3').classList.remove('hidden');
}

function addCustomService() {
    const name = document.getElementById('custom-name').value;
    const price = parseFloat(document.getElementById('custom-price').value) || 0;
    if (!name) return;

    const list = document.getElementById('services-list');
    const div = document.createElement('div');
    div.className = 'flex items-center justify-between rounded-xl border border-gray-200 bg-[var(--cui-primary-soft)] p-4';
    div.innerHTML = `
        <span class="font-medium text-gray-900">${name}</span>
        <span class="font-bold text-[var(--cui-primary)] tabular-nums" dir="ltr">${price.toFixed(2)} ر.س</span>`;
    list.appendChild(div);

    // Add to extras
    extras.push({ name, price, active: false });
    calculateTotal();
}
</script>
@endsection
```

---

## 5. التجاوب

| المقاس | السلوك |
|--------|--------|
| `≥1024px` | خطوات بعرض كامل + ملخص ثابت على اليسار |
| `768px - 1023px` | خطوات بعرض كامل + ملخص أسفل |
| `≤768px` | خطوات عمودية + بطاقات أصغر + ملخص sticky في الأسفل |

---

## 6. الاختبارات

```php
// tests/Feature/CalculatorTest.php
public function test_calculator_page_renders()
{
    $response = $this->get('/calculator');
    $response->assertOk();
    $response->assertSee('حاسبة تكلفة الخدمة');
    $response->assertSee('الخدمة');
}
```

---

## 7. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **تعديل** — مسار `/calculator` |
| `resources/views/update_service/calculator.blade.php` | **جديد** |
| `resources/views/partials/public/footer.blade.php` | **تعديل** — رابط |
| `tests/Feature/CalculatorTest.php` | **جديد** |

---

## 8. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `/calculator` تُعرض | 200 OK |
| الخطوات 1→2→3 تعمل | تدفق سلس |
| الحساب صحيح | 100 + 50 + 100 = 250 + 25 = 275 |
| Custom calculator يعمل | إضافة يدوية |
| التجاوب 3 مقاسات | سليم |
| لا أخطاء Console | صفر |
