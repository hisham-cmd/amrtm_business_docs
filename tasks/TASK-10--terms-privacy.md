# TASK-10: صفحات "الشروط والأحكام" + "سياسة الخصوصية" (`/terms` + `/privacy`)

> **المستوى**: صعب | **المدة المقدرة**: يومان | **الأولوية**: عالية (قانونية)

---

## 1. نظرة عامة

صفحتان قانونيتان إلزاميتان لأي منصة سعودية: الشروط والأحكام لاستخدام المنصة، وسياسة الخصوصية وحماية البيانات الشخصية. يجب ربطهما بنموذج التسجيل (قبول شروط) وبالفوتر.

**الصفحات الجديدة**:
- `/terms` — الشروط والأحكام
- `/privacy` — سياسة الخصوصية

**التعديلات**:
- `login.blade.php` — checkbox "أوافق على الشروط" في التسجيل
- `partials/public/footer.blade.php` — روابط جديدة

---

## 2. Routes

```php
// routes/web.php
Route::get('/terms', function () {
    return view('update_service.terms');
})->name('amrtm.terms');

Route::get('/privacy', function () {
    return view('update_service.privacy');
})->name('amrtm.privacy');
```

---

## 3. الهجرة — قبول الشروط

### 3.1 Migration

```php
// database/migrations/2026_09_09_130000_add_terms_accepted_at_to_bs_users.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('business')->table('bs_users', function (Blueprint $table) {
            if (!$table->hasColumn('terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('business')->table('bs_users', function (Blueprint $table) {
            if ($table->hasColumn('terms_accepted_at')) {
                $table->dropColumn('terms_accepted_at');
            }
        });
    }
};
```

### 3.2 تعديل Model

```php
// app/Models/Business/BusinessUser.php — إضافة إلى $fillable و $casts
protected $fillable = [
    // ...existing fields...
    'terms_accepted_at',
];

protected $casts = [
    // ...existing casts...
    'terms_accepted_at' => 'datetime',
];
```

### 3.3 تعديل RegisterRequest

```php
// app/Http/Requests/RegisterRequest.php — إضافة قاعدة
public function rules(): array
{
    return [
        // ...existing rules...
        'terms_accepted' => 'required|accepted',
    ];
}

public function messages(): array
{
    return [
        // ...existing messages...
        'terms_accepted.required' => 'يجب الموافقة على الشروط والأحكام',
        'terms_accepted.accepted' => 'يجب الموافقة على الشروط والأحكام',
    ];
}
```

### 3.4 تعديل Controller

```php
// app/Http/Controllers/AmrtmAuthController.php — في register()
$validated['terms_accepted_at'] = now();
// ثم في create():
BusinessUser::create([...$validated, 'terms_accepted_at' => now()]);
```

---

## 4. الواجهة — صفحة الشروط (`terms.blade.php`)

### 4.1 الهيكل

```blade
@extends('layouts.public')

@section('content')
@include('partials.public.corporate-ui')

<section class="cui-hero">
    <div class="mx-auto max-w-[1280px] px-4 py-16">
        <nav class="mb-6 text-sm text-white/60">
            <a href="{{ route('amrtm.index') }}" class="hover:text-white">الرئيسية</a>
            <span class="mx-2">/</span>
            <span class="text-white">الشروط والأحكام</span>
        </nav>
        <h1 class="text-4xl font-bold text-white">الشروط والأحكام</h1>
        <p class="mt-3 text-white/70">آخر تحديث: {{ date('Y-m-d') }}</p>
    </div>
</section>

<section class="py-16">
    <div class="mx-auto max-w-[1280px] px-4 lg:grid lg:grid-cols-[280px_1fr] lg:gap-12">
        {{-- فهرس المحتويات (Sticky) --}}
        <aside class="mb-8 lg:mb-0">
            <nav class="sticky top-24 space-y-1 rounded-2xl border border-[--b1] bg-white p-4">
                <p class="mb-3 font-semibold text-gray-900">فهرس المحتويات</p>
                @foreach($sections as $i => $section)
                    <a href="#section-{{ $i }}"
                       class="block rounded-lg px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-[var(--cui-primary-soft)] hover:text-[var(--cui-primary)]">
                        {{ $section['title'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- المحتوى --}}
        <article class="prose prose-lg prose-arabic max-w-none">
            @foreach($sections as $i => $section)
                <div id="section-{{ $i }}" class="mb-12 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-gray-900 border-b border-[--b1] pb-3">
                        {{ ($i + 1) }}. {{ $section['title'] }}
                    </h2>
                    <div class="mt-4 text-gray-600 leading-relaxed space-y-4">
                        {!! $section['content'] !!}
                    </div>
                </div>
            @endforeach
        </article>
    </div>
</section>

{{-- زر العودة --}}
<div class="text-center pb-16">
    <a href="{{ route('amrtm.index') }}" class="inline-flex items-center gap-2 text-[var(--cui-primary)] hover:underline">
        <i class="ti ti-arrow-right"></i> العودة للرئيسية
    </a>
</div>
@endsection
```

### 4.2 المحتوى (oved to controller or config)

**المحتوى يُخزَّن في `config/legal.php`** (لا hardcoded في Blade):

```php
// config/legal.php
return [
    'terms' => [
        ['title' => 'مقدمة', 'content' => <<<'HTML'
            <p>مرحباً بك في منصة آمر تم ("المنصة"). تُعد هذه الشروط والأحكام اتفاقاً قانونياً ملزماً بينك وبين شركة آمر تم ("نحن"). باستخدامك للمنصة، أنت توافق على هذه الشروط بالكامل.</p>
            <p>إذا لم توافق على أي شرط من هذه الشروط، يرجى عدم استخدام المنصة.</p>
        HTML],
        ['title' => 'تعريفات', 'content' => <<<'HTML'
            <ul>
                <li><strong>"المنصة"</strong>: تطبيق وموقع آمر تم الإلكترونية.</li>
                <li><strong>"المستخدم"</strong>: أي شخص يسجل في المنصة أو يستخدمها.</li>
                <li><strong>"مقدم الخدمة"</strong>: المنشأة المسجّلة التي تقدم خدمات عبر المنصة.</li>
                <li><strong>"الخدمة"</strong>: أي خدمةgovernmentية أو مكتبية متوفرة عبر المنصة.</li>
            </ul>
        HTML],
        ['title' => 'حساب المستخدم', 'content' => <<<'HTML'
            <p>2.1. يجب أن يكون المستخدم بعمر قانوني (18 سنة فأكثر) لتسجيل حساب في المنصة.</p>
            <p>2.2. يتحمل المستخدم مسؤولية الحفاظ على سرية بيانات تسجيل الدخول.</p>
            <p>2.3. يحتفظ النظام بحق تعليق أو حذف الحسابات المخالفة.</p>
        HTML],
        ['title' => 'استخدام المنصة', 'content' => <<<'HTML'
            <p>3.1. تُوفر المنصة وسطاً يربط بين المستخدمين ومقدمي الخدمات الحكومية والمكتبية.</p>
            <p>3.2. لا تتحمل المنصة المسؤولية عن جودة الخدمات المقدمة من مقدمي الخدمات المستقلين.</p>
            <p>3.3. يُمنع استخدام المنصة لأي غرض غير قانوني أو مخالف.</p>
        HTML],
        ['title' => 'المدفوعات', 'content' => <<<'HTML'
            <p>4.1. جميع المدفوعات تتم عبر بوابات دفع معتمدة وآمنة.</p>
            <p>4.2. أسعار الخدمات يحددها مقدمو الخدمات، وتحتفظ المنصة بعمولة على كل معاملة.</p>
            <p>4.3. يمكن للمستخدم طلب استرجاع المبلغ وفقاً لسياسة الاسترجاع الخاصة بالمنصة.</p>
        HTML],
        ['title' => 'الملكية الفكرية', 'content' => <<<'HTML'
            <p>5.1. جميع محتويات المنصة (نصوص، تصميم، شعارات، صور) هي ملك لشركة آمر تم.</p>
            <p>5.2. يُمنع نسخ أو توزيع أي محتوى من المنصة بدون إذن مسبق.</p>
        HTML],
        ['title' => 'تحديد المسؤولية', 'content' => <<<'HTML'
            <p>6.1. المنصة تُوفر "كما هي" بدون ضمانات صريحة أو ضمنية.</p>
            <p>6.2. لا تتحمل المنصة المسؤولية عن أي أضرار مباشرة أو غير مباشرة من استخدامها.</p>
        HTML],
        ['title' => 'تعديل الشروط', 'content' => <<<'HTML']
            <p>7.1. نحتفظ بحق تعديل هذه الشروط في أي وقت.</p>
            <p>7.2. سيتم إشعار المستخدمين بالتعديلات الجوهرية عبر البريد الإلكتروني أو إشعار داخل المنصة.</p>
        HTML],
        ['title' => 'القانون الحاكم', 'content' => <<<'HTML'
            <p>8.1. هذه الشروط محكومة بقوانين المملكة العربية السعودية.</p>
            <p>8.2. أي نزاع ينشأ عن استخدام المنصة يُحل ودياً أولاً، وإذا تعذر ذلك则 يُحال للمحاكمة المختصة في الرياض.</p>
        HTML],
    ],
    'privacy' => [
        // ...نفس النمط مع محتوى سياسة الخصوصية...
    ],
];
```

---

## 5. الواجهة — صفحة سياسة الخصوصية (`privacy.blade.php`)

**نفس هيكل `terms.blade.php`** لكن بمحتوى مختلف:

### 5.1 الأقسام

| # | القسم | المحتوى المطلوب |
|---|-------|----------------|
| 1 | مقدمة | نحن نقدر خصوصيتك... |
| 2 | المعلومات التي نجمعها | الاسم، البريد، الجوال، رقم الهوية، بيانات الاستخدام |
| 3 | كيف نجمع المعلومات | مباشرة من التسجيل + تلقائياً عبر Cookies |
| 4 | كيف نستخدم المعلومات | تقديم الخدمات + التحسين + التواصل + الأمان |
| 5 | مشاركة المعلومات | مع مقدمي الخدمات (المكتب المُسنّد فقط) + لا بيع للبيانات |
| 6 | حماية البيانات | تشفير SSL + تخزين آمن + وصول محدود |
| 7 | حقوق المستخدم | الوصول + التعديل + الحذف + نقل البيانات |
| 8 | الاحتفاظ بالبيانات | مدة الاحتفاظ = عمر الحساب + 5 سنوات بعد الحذف |
| 9 | ملفات تعريف الارتباط | Cookies ضرورية + تحليلية (مع إمكانية التعطيل) |
| 10 | التحديثات | إشعار بالتغييرات عبر البريد |
| 11 | التواصل | privacy@amrtm.sa |

---

## 6. تعديل نموذج التسجيل

### 6.1 في `login.blade.php` — تبويب التسجيل

```blade
{{-- قبل زر الإرسال --}}
<div class="flex items-start gap-3 mt-6">
    <input type="checkbox" id="terms_accepted" name="terms_accepted" required
           class="mt-1 h-4 w-4 rounded border-gray-300 text-[#006C35] focus:ring-[#006C35]" />
    <label for="terms_accepted" class="text-sm text-gray-600">
        أقر بأنني قرأت وأوافق على
        <a href="{{ route('amrtm.terms') }}" target="_blank" class="text-[var(--cui-primary)] hover:underline font-medium">
            الشروط والأحكام
        </a>
        و
        <a href="{{ route('amrtm.privacy') }}" target="_blank" class="text-[var(--cui-primary)] hover:underline font-medium">
            سياسة الخصوصية
        </a>
    </label>
</div>
@error('terms_accepted')
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
@enderror
```

### 6.2 في `provider-account.blade.php` — نموذج التسجيل

نفس التعديل أعلاه قبل زر "إنشاء حساب".

---

## 7. تعديل الفوتر

```blade
{{-- resources/views/partials/public/footer.blade.php --}}
<div class="flex flex-wrap gap-4 text-sm text-gray-400">
    <a href="{{ route('amrtm.terms') }}" class="hover:text-white transition-colors">الشروط والأحكام</a>
    <span>·</span>
    <a href="{{ route('amrtm.privacy') }}" class="hover:text-white transition-colors">سياسة الخصوصية</a>
    <span>·</span>
    <a href="{{ route('amrtm.contact') }}" class="hover:text-white transition-colors">تواصل معنا</a>
</div>
```

---

## 8. الاختبارات

### 8.1 Feature Test

```php
// tests/Feature/LegalPagesTest.php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_renders()
    {
        $response = $this->get('/terms');
        $response->assertOk();
        $response->assertSee('الشروط والأحكام');
        $response->assertSee('فهرس المحتويات');
    }

    public function test_privacy_page_renders()
    {
        $response = $this->get('/privacy');
        $response->assertOk();
        $response->assertSee('سياسة الخصوصية');
    }

    public function test_terms_links_in_footer()
    {
        $response = $this->get('/');
        $response->assertSee(route('amrtm.terms'));
        $response->assertSee(route('amrtm.privacy'));
    }

    public function test_registration_requires_terms_acceptance()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // terms_accepted NOT sent
        ]);
        $response->assertSessionHasErrors('terms_accepted');
    }

    public function test_registration_succeeds_with_terms()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => '1',
        ]);
        // Should not have terms_accepted error
        $response->assertSessionDoesntHaveErrors('terms_accepted');
    }
}
```

### 8.2 Checklist يدوي

- [ ] `/terms` تُعرض مع فهرس + 8 أقسام
- [ ] `/privacy` تُعرض مع فهرس + 11 قسم
- [ ] الفهرس الثابت (sticky) يعمل عند التمرير
- [ ] الروابط الداخلية `#section-X` تعمل
- [ ] في footer تظهر الروابط الجديدة
- [ ] تسجيل بدون قبول الشروط → خطأ
- [ ] تسجيل مع قبول الشروط → نجاح + `terms_accepted_at` محفوظ
- [ ] التجاوب على 375/768/1280px سليم
- [ ] لا أخطاء Console

---

## 9. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `database/migrations/2026_09_09_130000_add_terms_accepted_at_to_bs_users.php` | **جديد** |
| `config/legal.php` | **جديد** — محتوى الشروط والخصوصية |
| `app/Models/Business/BusinessUser.php` | **تعديل** — إضافة `terms_accepted_at` |
| `app/Http/Requests/RegisterRequest.php` | **تعديل** — إضافة `terms_accepted` |
| `app/Http/Controllers/AmrtmAuthController.php` | **تعديل** — حفظ `terms_accepted_at` |
| `resources/views/update_service/terms.blade.php` | **جديد** |
| `resources/views/update_service/privacy.blade.php` | **جديد** |
| `routes/web.php` | **تعديل** — مسارين جديدين |
| `resources/views/amrtm/auth/login.blade.php` | **تعديل** — checkbox الموافقة |
| `resources/views/update_service/provider-account.blade.php` | **تعديل** — checkbox الموافقة |
| `resources/views/partials/public/footer.blade.php` | **تعديل** — روابط جديدة |
| `tests/Feature/LegalPagesTest.php` | **جديد** |

---

## 10. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan migrate --force` | الهجرة تُنفَّذ |
| `php artisan test --filter=LegalPagesTest` | 5 اختبارات PASS |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| `/terms` + `/privacy` تُعرض | 200 OK |
| التسجيل يرفض بدون قبول | `terms_accepted` error |
| الفهرس sticky يعمل | عند التمرير |
| التجاوب 3 مقاسات | سليم |
| لا `<input>` خام (عدا checkbox) | صفر |
