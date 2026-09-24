# TASK-09: صفحة "حمّل التطبيق" (`/app`)

> **المستوى**: صعب | **المدة المقدرة**: 3 أيام | **الأولوية**: متوسطة

---

## 1. نظرة عامة

صفحة هبوط احترافية (Landing Page) لتطبيق الموبايل منصة آمر تم، مصممة لإقناع الزائر بتحميل التطبيق. تحتوي محاكاة للهاتف + مميزات + لقطات شاشة + آراء مستخدمين + FAQs.

**الملف الجديد**: `app-landing.blade.php`

---

## 2. Route

```php
// routes/web.php
Route::get('/app', function () {
    return view('update_service.app_landing');
})->name('amrtm.app');
```

---

## 3. الواجهة الأمامية — هيكل الصفحة

### 3.1 الم.layout

```blade
@extends('layouts.public')
@section('content')
    @include('partials.public.corporate-ui')
    {{-- المحتوى --}}
@endsection
```

### 3.2 الهيكل الكامل

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 1: Hero                                    │
│  ┌─────────────────────────────────────────────┐   │
│  │  [نص يسار]              [موك أيفون يمين]    │   │
│  │                                               │   │
│  │  حمّل تطبيق               ┌──────────┐       │   │
│  │  آمر تم                   │ 📱       │       │   │
│  │                           │ App      │       │   │
│  │  كل الخدمات الحكومية      │ Screen   │       │   │
│  │  في مكتبك                 │ Mockup   │       │   │
│  │                           │          │       │   │
│  │  [App Store] [Google Play]└──────────┘       │   │
│  │  أو حمّل عبر QR ↓                            │   │
│  │  [QR Code]                                   │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 2: المميزات (6 بطاقات في شبكة 3×2)         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │ 📋       │ │ 🔔       │ │ 💳       │           │
│  │طلب خدمة  │ │إشعارات   │ │مدفوعات   │           │
│  │فوري      │ │لحظية     │ │آمنة      │           │
│  ├──────────┤ ├──────────┤ ├──────────┤           │
│  │ 📊       │ │ 🛡️       │ │ 🌍       │           │
│  │تتبع      │ │حماية     │ │دعم       │           │
│  │الطلبات   │ │بياناتك   │ │متعدد اللغات│          │
│  └──────────┘ └──────────┘ └──────────┘           │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 3: لقطات الشاشة (Carousel)                 │
│  ┌─────────────────────────────────────────────┐   │
│  │  [صورة 1]  [صورة 2]  [صورة 3]  [صورة 4]    │   │
│  │                                               │   │
│  │  ◀  ● ○ ○ ○  ▶                              │   │
│  │                                               │   │
│  │  الرئيسية | الطلبات | المدفوعات | الإشعارات  │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 4: أرقام وإحصائيات                        │
│  ┌─────────────────────────────────────────────┐   │
│  │  10,000+         50,000+        4.8 ⭐      │   │
│  │  مستخدم نشط      طلب مكتمل      تقييم       │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 5: آراء المستخدمين (3 بطاقات)              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │ ⭐⭐⭐⭐⭐  │ │ ⭐⭐⭐⭐⭐  │ │ ⭐⭐⭐⭐⭐  │           │
│  │ "تطبيق    │ │ "سهل       │ │ " توفير   │           │
│  │ ممتاز     │ │ الاستخدام  │ │ وقت كبير" │           │
│  │ وشامل"   │ │ والسرعة"   │ │           │           │
│  │ 👤 محمد   │ │ 👤 سارة    │ │ 👤 خالد   │           │
│  └──────────┘ └──────────┘ └──────────┘           │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 6: الأسئلة الشائعة (Accordion)             │
│  ┌─────────────────────────────────────────────┐   │
│  │  ▶ هل التطبيق مجاني؟                         │   │
│  │  ▶ على أي الأنظمة يعمل؟                       │   │
│  │  ▶ هل أحتاج حساب لاستخدامه؟                    │   │
│  │  ▶ كيف أحفظ بياناتي بشكل آمن؟                  │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SECTION 7: CTA Final                               │
│  ┌─────────────────────────────────────────────┐   │
│  │  🚀 استخدم آمر تم الآن                        │   │
│  │  [App Store]  [Google Play]                   │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

---

## 4. التصميم التفصيلي

### 4.1 Hero Section

```blade
<section class="relative overflow-hidden bg-gradient-to-br from-[#0B3B2C] via-[#006C35] to-[#0B3B2C] py-20 lg:py-32">
    <!-- Dot pattern overlay -->
    <div class="absolute inset-0 opacity-10"
         style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;">
    </div>

    <div class="mx-auto max-w-[1280px] px-4 lg:grid lg:grid-cols-2 lg:items-center lg:gap-12">
        {{-- النص --}}
        <div class="relative z-10 text-center lg:text-right">
            <span class="mb-4 inline-block rounded-full bg-white/10 px-4 py-1.5 text-sm text-white/90 backdrop-blur">
                متاح على iOS و Android
            </span>
            <h1 class="mt-4 text-4xl font-bold text-white lg:text-6xl">
                حمّل تطبيق<br/>
                <span class="text-[#7DFFB3]">آمر تم</span>
            </h1>
            <p class="mt-6 text-lg text-white/80 leading-relaxed">
                كل الخدمات الحكومية والخدمات المساندة في مكان واحد.
                اطلب خدمة، تابع طلبك، ادفع بأمان — من هاتفك.
            </p>

            {{-- أزرار التحميل --}}
            <div class="mt-8 flex flex-wrap justify-center gap-4 lg:justify-start">
                <a href="#" class="flex items-center gap-3 rounded-xl bg-white px-6 py-3 text-gray-900 shadow-lg transition-all hover:shadow-xl hover:-translate-y-0.5">
                    <i class="ti ti-brand-apple text-2xl"></i>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">حمّل من</p>
                        <p class="font-semibold">App Store</p>
                    </div>
                </a>
                <a href="#" class="flex items-center gap-3 rounded-xl bg-white px-6 py-3 text-gray-900 shadow-lg transition-all hover:shadow-xl hover:-translate-y-0.5">
                    <i class="ti ti-brand-google-play text-2xl"></i>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">حمّل من</p>
                        <p class="font-semibold">Google Play</p>
                    </div>
                </a>
            </div>

            {{-- QR Code --}}
            <div class="mt-8 flex items-center justify-center gap-4 lg:justify-start">
                <div class="rounded-xl bg-white p-3" id="app-qr"></div>
                <p class="text-sm text-white/70">امسح الرمز لتحميل التطبيق</p>
            </div>
        </div>

        {{-- موك الهاتف --}}
        <div class="relative mt-12 lg:mt-0 flex justify-center">
            <div class="relative w-[280px] h-[560px] rounded-[3rem] border-[6px] border-gray-800 bg-black shadow-2xl overflow-hidden">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 h-7 w-32 rounded-b-2xl bg-gray-800"></div>
                <img src="/images/app-screenshot-hero.png" alt="لقطة شاشة التطبيق"
                     class="h-full w-full object-cover" />
            </div>
            <!-- Floating cards -->
            <div class="absolute -top-4 -left-4 rounded-xl bg-white p-3 shadow-lg animate-bounce"
                 style="animation-duration: 3s;">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                        <i class="ti ti-check text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium">تم القبول</p>
                        <p class="text-[10px] text-gray-400">طلب #AMR-1234</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
```

### 4.2 قسم المميزات

```blade
<section class="py-20 bg-gray-50">
    <div class="mx-auto max-w-[1280px] px-4">
        <h2 class="text-center text-3xl font-bold text-gray-900">لماذا آمر تم؟</h2>
        <p class="mt-3 text-center text-gray-500">تجربة متكاملة صُمّمت لراحتك</p>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['icon' => 'ti-file-text', 'title' => 'طلب خدمة فوري', 'desc' => 'اطلب أي خدمةgovernmentية بنقرات قليلة'],
                ['icon' => 'ti-bell', 'title' => 'إشعارات لحظية', 'desc' => 'تلقَ إشعارات فورية عند تحديث حالة طلبك'],
                ['icon' => 'ti-credit-card', 'title' => 'مدفوعات آمنة', 'desc' => 'ادفع بشكل آمن عبر بوابات الدفع المعتمدة'],
                ['icon' => 'ti-chart-line', 'title' => 'تتبع الطلبات', 'desc' => 'تابع طلبك لحظة بلحظة من الإرسال للإنجاز'],
                ['icon' => 'ti-shield-check', 'title' => 'حماية البيانات', 'desc' => 'بياناتك مشفّرة ومحمية بأعلى معايير الأمان'],
                ['icon' => 'ti-world', 'title' => 'دعم متعدد اللغات', 'desc' => 'العربية والإنجليزية بتبديل فوري'],
            ] as $feature)
                <div class="group rounded-2xl border border-[--b1] bg-white p-8 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-[var(--cui-primary-soft)] transition-colors group-hover:bg-[#006C35] group-hover:text-white">
                        <i class="ti {{ $feature['icon'] }} text-2xl text-[var(--cui-primary)] group-hover:text-white"></i>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-gray-900">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-gray-500 leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
```

### 4.3 أسماء الحقول المطلوبة

| العنصر | الكائن/المتغير | الملاحظات |
|--------|---------------|-----------|
| QR Code | `window.QRCode` | يُولَّد عبر `qr.js` (موجود مسبقاً) |
| صورة موك الهاتف | `/images/app-screenshot-hero.png` | تُنشأ بـ Figma أو CSS mockup |
| لقطات الشاشة | `/images/app-shot-1.png` إلى `4.png` | placeholder يُستبدل لاحقاً |
| أزرار App Store | `href="#"` | روابط حقيقية عند التوفر |
| إحصائيات | hardcoded أو من API | 10,000+ / 50,000+ / 4.8 |

---

## 5. JavaScript

### 5.1 Carousel للقطات

```javascript
// resources/js/app-landing-carousel.js
document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.getElementById('shots-carousel');
    if (!carousel) return;
    
    const track = carousel.querySelector('.carousel-track');
    const dots = carousel.querySelectorAll('.carousel-dot');
    const prev = carousel.querySelector('.carousel-prev');
    const next = carousel.querySelector('.carousel-next');
    let current = 0;
    const total = dots.length;
    
    function goTo(index) {
        current = Math.max(0, Math.min(index, total - 1));
        track.style.transform = `translateX(${current * -100}%)`;
        dots.forEach((d, i) => d.classList.toggle('bg-[#006C35]', i === current));
        dots.forEach((d, i) => d.classList.toggle('bg-gray-300', i !== current));
    }
    
    prev?.addEventListener('click', () => goTo(current - 1));
    next?.addEventListener('click', () => goTo(current + 1));
    dots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));
    
    // Auto-slide every 5 seconds
    setInterval(() => goTo((current + 1) % total), 5000);
});
```

### 5.2 عدّاد الأرقام (Counter Animation)

```javascript
// resources/js/counter-animate.js
function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const timer = setInterval(() => {
                        current += step;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        el.textContent = Math.floor(current).toLocaleString('ar-SA') + suffix;
                    }, 16);
                    observer.disconnect();
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(el);
    });
}

document.addEventListener('DOMContentLoaded', animateCounters);
```

---

## 6. SEO

```blade
@section('head')
<title>حمّل تطبيق آمر تم — الخدمات الحكومية في جيبك</title>
<meta name="description" content="حمّل تطبيق آمر تم واطلب الخدمات الحكومية والمكتبية من هاتفك. تتبع الطلبات، مدفوعات آمنة، إشعارات فورية." />
<meta property="og:title" content="حمّل تطبيق آمر تم" />
<meta property="og:description" content="كل الخدمات الحكومية في مكان واحد" />
<meta property="og:image" content="/images/app-og-image.png" />
<meta property="og:type" content="website" />
@endsection
```

---

## 7. التجاوب

| المقاس | التخطيط |
|--------|---------|
| `≥1024px` | Hero: نص يسار + هاتف يمين. المميزات: 3 أعمدة. |
| `768px - 1023px` | Hero: نص أعلى + هاتف أسفل. المميزات: 2 أعمدة. |
| `≤768px` | كل شيء عمودي. الهاتف أصغر (240×480). البطاقات: عمود واحد. |

---

## 8. الأسئلة الشائعة (Accordion HTML)

```blade
<div class="mx-auto max-w-3xl space-y-4">
    @foreach([
        ['q' => 'هل التطبيق مجاني؟', 'a' => 'نعم، التطبيق مجاني بالكامل. بعض الخدمات قد تتطلب رسوماً ت governed by each ministry.'],
        ['q' => 'على أي الأنظمة يعمل؟', 'a' => 'يعمل على iOS (iPhone/iPad) و Android بنسخة متوافقة مع جميع الأجهزة الحديثة.'],
        ['q' => 'هل أحتاج حساباً جديداً؟', 'a' => 'يمكنك استخدام حسابك الحالي على المنصة أو إنشاء حساب جديد مباشرة من التطبيق.'],
        ['q' => 'كيف أحمي بياناتي؟', 'a' => 'جميع البيانات مشفّرة ومحفوظة على خوادم آمنة داخل المملكة العربية السعودية.'],
    ] as $faq)
        <details class="group rounded-2xl border border-[--b1] bg-white">
            <summary class="flex cursor-pointer items-center justify-between p-6 font-semibold text-gray-900
                          [&::-webkit-details-marker]:hidden">
                {{ $faq['q'] }}
                <i class="ti ti-chevron-down transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="px-6 pb-6 text-gray-500 leading-relaxed">{{ $faq['a'] }}</div>
        </details>
    @endforeach
</div>
```

---

## 9. Checklist يدوي

- [ ] الصفحة تُعرض على `/app` بدون أخطاء
- [ ] Hero يظهر نص + هاتف + أزرار تحميل + QR
- [ ] المميزات الـ 6 تظهر في شبكة 3×2
- [ ] Carousel يتنقل تلقائياً + يدوياً
- [ ] عدّاد الأرقام يتحرك عند التمرير
- [ ] الأسئلة الشائعة تفتح وتُغلق
- [ ] التجاوب: 375px (موبايل) / 768px (تابلت) / 1280px (ديسكتوب)
- [ ] لا أخطاء Console
- [ ] `<title>` و `<meta>` صحيحة

---

## 10. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `routes/web.php` | **تعديل** — إضافة مسار `/app` |
| `resources/views/update_service/app_landing.blade.php` | **جديد** |
| `resources/js/app-landing-carousel.js` | **جديد** |
| `resources/js/counter-animate.js` | **جديد** |
| `resources/js/app.js` | **تعديل** — استيراد الوحدتين |
| `resources/views/partials/public/footer.blade.php` | **تعديل** — إضافة رابط "حمّل التطبيق" |

---

## 11. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| الصفحة تُعرض على `/app` | 200 OK |
| SEO: title + meta + OG | موجودة |
| التجاوب 3 مقاسات | سليم |
| Carousel يعمل | يتنقل تلقائياً |
| لا `<input>` / `<button>` خام | صفر |
| لا أخطاء Console | صفر |
