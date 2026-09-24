# TASK-13: صفحة "عن المنصة" (`/about`)

> **المستوى**: سهل | **المدة المقدرة**: يومان | **الأولوية**: متوسطة (Trust/Brand)

---

## 1. نظرة عامة

صفحة تعريفية بالمنصة تعرض الرؤية والرسالة والأرقام والإحصائيات والفريق — تبني الثقة وتعزز الهوية المؤسسية.

**الملف الجديد**: `about.blade.php`

---

## 2. Route

```php
// routes/web.php
Route::get('/about', function () {
    return view('update_service.about');
})->name('amrtm.about');
```

---

## 3. الواجهة — هيكل الصفحة

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 1: Hero                                    │
│  ┌─────────────────────────────────────────────┐   │
│  │  breadcrumb: الرئيسية > عن المنصة             │   │
│  │  العنوان: عن آمر تم                          │   │
│  │  الوصف: منصة سعودية متكاملة لإدارة الطلبات   │   │
│  │  والخدمات الحكومية وقطاع الأعمال              │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 2: أرقام وإحصائيات (عدّاد متحرك)           │
│  ┌─────────────────────────────────────────────┐   │
│  │  10K+           50K+          200+          4.9│   │
│  │  مستخدم نشط     طلب مكتمل     خدمة متوفرة  تقييم│   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 3: الرؤية والرسالة (بطاقتان)               │
│  ┌──────────────────┐ ┌──────────────────┐         │
│  │  🎯 رؤيتنا       │ │  📋 رسالتنا      │         │
│  │                   │ │                   │         │
│  │  أن نكون المنصة   │ │  تبسيط إجراءات   │         │
│  │  الرائدة في       │ │  الخدمات الحكومية │         │
│  │  تمكين الأعمال   │ │  وتوفير تجربة     │         │
│  │  في المملكة       │ │  رقمية متكاملة   │         │
│  └──────────────────┘ └──────────────────┘         │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 4: القيم (4 بطاقات)                        │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐│
│  │ 🛡️       │ │ ⚡       │ │ 🤝       │ │ 🔒    ││
│  │الشفافية  │ │ السرعة   │ │ الشراكة  │ │الأمان  ││
│  └──────────┘ └──────────┘ └──────────┘ └────────┘│
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 5: كيف نعمل (3 خطوات)                      │
│  ┌─────────────────────────────────────────────┐   │
│  │  1️⃣ سجّل          2️⃣ اختر الخدمة     3️⃣ تابع │   │
│  │  حسابك مجاناً     واطلبها فوراً      طلبك    │   │
│  │                                               │   │
│  │  ────────────────────────────────────────── │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 6: شركاؤنا (أرقام/شعارات)                  │
│  ┌─────────────────────────────────────────────┐   │
│  │  🏛️ وزارة التجارة  🏛️ وزارة العمل  🏛️ وزارة العدل│
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 7: CTA                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │  ابدأ الآن مع آمر تم                        │   │
│  │  [حساب مجاني]  [تصفح الخدمات]               │   │
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

{{-- Hero --}}
<section class="cui-hero">
    <div class="mx-auto max-w-[1280px] px-4 py-20 text-center">
        <nav class="mb-6 text-sm text-white/60">
            <a href="{{ route('amrtm.index') }}" class="hover:text-white">الرئيسية</a>
            <span class="mx-2">/</span>
            <span class="text-white">عن المنصة</span>
        </nav>
        <h1 class="text-4xl font-bold text-white lg:text-5xl">عن <span class="text-[#7DFFB3]">آمر تم</span></h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-white/80 leading-relaxed">
            منصة سعودية متكاملة لإدارة الطلبات والخدمات الحكومية وقطاع الأعمال.
            نسعى لتبسيط الإجراءات الرقمية وتمكين الأعمال في المملكة العربية السعودية.
        </p>
    </div>
</section>

{{-- الإحصائيات --}}
<section class="py-16 bg-gray-50">
    <div class="mx-auto max-w-[1280px] px-4">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            @foreach([
                ['count' => '10000', 'suffix' => '+', 'label' => 'مستخدم نشط', 'icon' => 'ti-users'],
                ['count' => '50000', 'suffix' => '+', 'label' => 'طلب مكتمل', 'icon' => 'ti-file-check'],
                ['count' => '200', 'suffix' => '+', 'label' => 'خدمة متوفرة', 'icon' => 'ti-layout-grid'],
                ['count' => '4.9', 'suffix' => '', 'label' => 'تقييم المستخدمين', 'icon' => 'ti-star'],
            ] as $stat)
                <div class="rounded-2xl bg-white p-6 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-[var(--cui-primary-soft)]">
                        <i class="ti {{ $stat['icon'] }} text-2xl text-[var(--cui-primary)]"></i>
                    </div>
                    <p class="mt-4 text-3xl font-bold text-gray-900 tabular-nums" dir="ltr">
                        <span data-count="{{ $stat['count'] }}" data-suffix="{{ $stat['suffix'] }}">0</span>
                    </p>
                    <p class="mt-1 text-sm text-gray-500">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- الرؤية والرسالة --}}
<section class="py-20">
    <div class="mx-auto max-w-[1280px] px-4">
        <div class="grid gap-8 lg:grid-cols-2">
            <div class="rounded-2xl border border-[--b1] bg-white p-8">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-[var(--cui-primary-soft)]">
                    <i class="ti ti-eye text-2xl text-[var(--cui-primary)]"></i>
                </div>
                <h2 class="mt-5 text-2xl font-bold text-gray-900">رؤيتنا</h2>
                <p class="mt-4 text-gray-500 leading-relaxed">
                    أن نكون المنصة الرائدة في تمكين الأعمال في المملكة العربية السعودية، من خلال توفير تجربة رقمية متكاملة تربط بين المنشآت والخدمات الحكومية بكفاءة وشفافية.
                </p>
            </div>
            <div class="rounded-2xl border border-[--b1] bg-white p-8">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-[var(--cui-primary-soft)]">
                    <i class="ti ti-bullseye text-2xl text-[var(--cui-primary)]"></i>
                </div>
                <h2 class="mt-5 text-2xl font-bold text-gray-900">رسالتنا</h2>
                <p class="mt-4 text-gray-500 leading-relaxed">
                    تبسيط إجراءات الخدمات الحكومية وتوفير تجربة رقمية متكاملة تُمكّن المنشآت من إدارة طلباتها بكفاءة، مع ضمان الشفافية والأمان في كل معاملة.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- القيم --}}
<section class="py-16 bg-gray-50">
    <div class="mx-auto max-w-[1280px] px-4">
        <h2 class="text-center text-3xl font-bold text-gray-900">قيمنا</h2>
        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['icon' => 'ti-shield-check', 'title' => 'الشفافية', 'desc' => 'جميع التعاملات والأسعار واضحة ومكشوفة'],
                ['icon' => 'ti-bolt', 'title' => 'السرعة', 'desc' => 'إنجاز المعاملات في أسرع وقت ممكن'],
                ['icon' => 'ti-handshake', 'title' => 'الشراكة', 'desc' => 'بناء شراكات استراتيجية مع الجهات الحكومية'],
                ['icon' => 'ti-lock', 'title' => 'الأمان', 'desc' => 'حماية بيانات المستخدمين بأعلى المعايير'],
            ] as $value)
                <div class="group rounded-2xl border border-[--b1] bg-white p-6 text-center transition-all hover:shadow-lg hover:-translate-y-1">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-[var(--cui-primary-soft)] transition-colors group-hover:bg-[#006C35]">
                        <i class="ti {{ $value['icon'] }} text-2xl text-[var(--cui-primary)] group-hover:text-white"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-gray-900">{{ $value['title'] }}</h3>
                    <p class="mt-2 text-sm text-gray-500">{{ $value['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- كيف نعمل --}}
<section class="py-20">
    <div class="mx-auto max-w-[1280px] px-4">
        <h2 class="text-center text-3xl font-bold text-gray-900">كيف نعمل؟</h2>
        <p class="mt-3 text-center text-gray-500">ثلاث خطوات بسيطة للحصول على خدمتك</p>

        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
            @foreach([
                ['step' => '01', 'icon' => 'ti-user-plus', 'title' => 'سجّل حسابك', 'desc' => 'أنشئ حسابك مجاناً في دقائق معدودة وأكمل بيانات منشأتك.'],
                ['step' => '02', 'icon' => 'ti-search', 'title' => 'اختر الخدمة', 'desc' => 'تصفح كتالوجنا الشامل من الخدمات الحكومية واختر ما يناسبك.'],
                ['step' => '03', 'icon' => 'ti-chart-line', 'title' => 'تابع طلبك', 'desc' => 'تتبع طلبك لحظة بلحظة من الإرسال حتى الإنجاز.'],
            ] as $step)
                <div class="relative text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[var(--cui-primary-soft)] text-3xl font-bold text-[var(--cui-primary)]">
                        {{ $step['step'] }}
                    </div>
                    <div class="mt-4 mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#006C35] text-white">
                        <i class="ti {{ $step['icon'] }} text-xl"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-gray-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-gray-500">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-20 bg-[#006C35]">
    <div class="mx-auto max-w-[1280px] px-4 text-center">
        <h2 class="text-3xl font-bold text-white">ابدأ الآن مع آمر تم</h2>
        <p class="mt-3 text-white/80">انضم لآلاف المنشآت التي تستخدم منصتنا</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ route('amrtm.provider.account.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 font-semibold text-[#006C35] transition-all hover:shadow-lg hover:-translate-y-0.5">
                <i class="ti ti-plus"></i>
                حساب مجاني
            </a>
            <a href="{{ route('amrtm.catalog.category', 'ministries') }}"
               class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 px-8 py-3 font-semibold text-white transition-all hover:bg-white/10">
                <i class="ti ti-layout-grid"></i>
                تصفح الخدمات
            </a>
        </div>
    </div>
</section>

@include('partials.public.footer')
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
// Counter animation
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseFloat(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const isFloat = target % 1 !== 0;
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const timer = setInterval(() => {
                        current += step;
                        if (current >= target) { current = target; clearInterval(timer); }
                        el.textContent = (isFloat ? current.toFixed(1) : Math.floor(current).toLocaleString('ar-SA')) + suffix;
                    }, 16);
                    observer.disconnect();
                }
            });
        }, { threshold: 0.5 });
        observer.observe(el);
    });
});
</script>
@endsection
```

---

## 5. SEO

```blade
@section('head')
<title>عن المنصة — آمر تم</title>
<meta name="description" content="آمر تم منصة سعودية متكاملة لإدارة الطلبات والخدمات الحكومية وقطاع الأعمال. تعرف على رؤيتنا ورسالتنا." />
@endsection
```

---

## 6. الاختبارات

```php
// tests/Feature/AboutPageTest.php
public function test_about_page_renders()
{
    $response = $this->get('/about');
    $response->assertOk();
    $response->assertSee('عن آمر تم');
    $response->assertSee('رؤيتنا');
    $response->assertSee('رسالتنا');
}

public function test_about_page_has_seo()
{
    $response = $this->get('/about');
    $response->assertSee('عن المنصة');
}
```

---

## 7. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **إضافة** مسار `/about` |
| `resources/views/update_service/about.blade.php` | **جديد** |
| `resources/views/partials/public/footer.blade.php` | **تعديل** — إضافة رابط |
| `tests/Feature/AboutPageTest.php` | **جديد** |

---

## 8. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `/about` تُعرض | 200 OK |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| العدّاد يتحرك عند التمرير | IntersectionObserver |
| التجاوب 3 مقاسات | سليم |
| لا أخطاء Console | صفر |
| `<title>` + `<meta>` | موجودة |
