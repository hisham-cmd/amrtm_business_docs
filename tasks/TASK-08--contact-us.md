# TASK-08: صفحة "تواصل معنا" (`/contact`)

> **المستوى**: صعب | **المدة المقدرة**: 3 أيام | **الأولوية**: متوسطة

---

## 1. نظرة عامة

صفحة اتصال احترافية تُمكّن العملاء من إرسال استفسارات أو شكاوى أو اقتراحات مباشرة للإدارة، مع إشعارات بريدية فورية.

**الملفات الجديدة**:
- `contact.blade.php` — واجهة الصفحة
- `ContactController.php` — التحكم
- `ContactMessage.php` — النموذج
- `ContactMessageReceivedMail.php` — البريد
- `2026_09_09_..._create_bs_contact_messages_table.php` — الهجرة

---

## 2. قاعدة البيانات

### 2.1 Migration

```php
// database/migrations/2026_09_09_120000_create_bs_contact_messages_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('business')->create('bs_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('category', ['inquiry', 'complaint', 'suggestion', 'partnership'])
                  ->default('inquiry');
            $table->string('subject');
            $table->text('message');
            $table->enum('status', ['new', 'read', 'replied'])->default('new');
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::connection('business')->dropIfExists('bs_contact_messages');
    }
};
```

---

## 3. Model

```php
// app/Models/Business/ContactMessage.php
<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $connection = 'business';
    protected $table = 'bs_contact_messages';

    protected $fillable = [
        'name', 'email', 'phone', 'category', 'subject',
        'message', 'status', 'admin_reply', 'replied_at', 'ip_address',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    const CATEGORY_LABELS = [
        'inquiry' => 'استفسار',
        'complaint' => 'شكوى',
        'suggestion' => 'اقتراح',
        'partnership' => 'تعاون',
    ];

    const STATUS_LABELS = [
        'new' => 'جديد',
        'read' => 'مقروء',
        'replied' => 'تم الرد',
    ];
}
```

---

## 4. Route + Controller

### 4.1 Route

```php
// routes/web.php
Route::get('/contact', [ContactController::class, 'show'])->name('amrtm.contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact,5,1')  // 5 رسائل / ساعة / IP
    ->name('amrtm.contact.store');
```

### 4.2 Controller

```php
// app/Http/Controllers/ContactController.php
<?php

namespace App\Http\Controllers;

use App\Models\Business\ContactMessage;
use App\Mail\ContactMessageReceivedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function show()
    {
        return view('update_service.contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'category' => 'required|in:inquiry,complaint,suggestion,partnership',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string|max:5000',
        ], [
            'name.required'     => 'الاسم مطلوب',
            'email.required'    => 'البريد الإلكتروني مطلوب',
            'email.email'       => 'صيغة البريد غير صحيحة',
            'category.required'  => 'يرجى اختيار الفئة',
            'subject.required'   => 'الموضوع مطلوب',
            'message.required'   => 'الرسالة مطلوبة',
            'message.max'       => 'الرسالة لا تتجاوز 5000 حرف',
        ]);

        $validated['ip_address'] = $request->ip();

        $message = ContactMessage::create($validated);

        // Send email to admin
        try {
            Mail::to('admin@amrtm.sa')->send(new ContactMessageReceivedMail($message));
        } catch (\Exception $e) {
            // Log but don't fail the request
            \Log::warning('Failed to send contact email: ' . $e->getMessage());
        }

        return redirect()->route('amrtm.contact')
            ->with('success', 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.');
    }
}
```

---

## 5. Mail

```php
// app/Mail/ContactMessageReceivedMail.php
<?php

namespace App\Mail;

use App\Models\Business\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $message) {}

    public function build()
    {
        return $this
            ->subject("رسالة جديدة: {$this->message->subject}")
            ->view('emails.contact_received')
            ->with([
                'name' => $this->message->name,
                'email' => $this->message->email,
                'category' => ContactMessage::CATEGORY_LABELS[$this->message->category],
                'subject' => $this->message->subject,
                'message' => $this->message->message,
            ]);
    }
}
```

```blade
{{-- resources/views/emails/contact_received.blade.php --}}
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="utf-8"></head>
<body style="font-family: 'Cairo', sans-serif; background: #f8faf9; padding: 40px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #006C35, #0B3B2C); padding: 32px; text-align: center;">
            <h1 style="color: white; margin: 0;">رسالة جديدة من صفحة تواصل معنا</h1>
        </div>
        <div style="padding: 32px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; width: 120px;">الاسم</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee;">{{ $name }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;">البريد</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee;"><a href="mailto:{{ $email }}">{{ $email }}</a></td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;">الفئة</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee;">{{ $category }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;">الموضوع</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eee;">{{ $subject }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; vertical-align: top;">الرسالة</td>
                    <td style="padding: 12px;">{{ $message }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
```

---

## 6. الواجهة الأمامية

### 6.1 هيكل الصفحة

```
┌─────────────────────────────────────────────────────┐
│  Navbar مشترك                                       │
├─────────────────────────────────────────────────────┤
│  Hero Section (cui-hero)                            │
│  ┌─────────────────────────────────────────────┐   │
│  │  breadcrumb: الرئيسية > تواصل معنا          │   │
│  │  العنوان: تواصل معنا                        │   │
│  │  الوصف: نحن هنا لمساعدتك...                 │   │
│  └─────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────┤
│  المحتوى الرئيسي: شبكة 2 أعمدة                     │
│  ┌──────────────────────────┬───────────────────┐  │
│  │  النموذج                 │  معلومات الاتصال  │  │
│  │  ┌──────────────────┐   │  📍 العنوان        │  │
│  │  │ الاسم *          │   │  📞 الهاتف         │  │
│  │  ├──────────────────┤   │  📧 البريد         │  │
│  │  │ البريد *         │   │  ⏰ ساعات العمل    │  │
│  │  ├──────────────────┤   │                   │  │
│  │  │ الجوال (اختياري)  │   │  ┌─────────────┐ │  │
│  │  ├──────────────────┤   │  │  خريطة      │ │  │
│  │  │ الفئة *          │   │  │  Google Maps │ │  │
│  │  │ [استفسار] [شكوى] │   │  │             │ │  │
│  │  │ [اقتراح] [تعاون]  │   │  └─────────────┘ │  │
│  │  ├──────────────────┤   │                   │  │
│  │  │ الموضوع *        │   │  وسائل التواصل    │  │
│  │  ├──────────────────┤   │  🐦 📸 💼         │  │
│  │  │ الرسالة *        │   │                   │  │
│  │  │                  │   │                   │  │
│  │  │                  │   │                   │  │
│  │  ├──────────────────┤   │                   │  │
│  │  │ [  إرسال الرسالة ]│   │                   │  │
│  │  └──────────────────┘   │                   │  │
│  └──────────────────────────┴───────────────────┘  │
├─────────────────────────────────────────────────────┤
│  FAQ Section (الأسئلة الشائعة)                      │
│  ┌─────────────────────────────────────────────┐   │
│  │  ▶ كيف أطلب خدمة؟                           │   │
│  │  ▶ كيف أتابع طلبي؟                           │   │
│  │  ▶ كيف أشحن رصيدي؟                           │   │
│  │  ▶ هل يمكنني استرجاع المبلغ؟                  │   │
│  └─────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────┤
│  Footer مشترك                                       │
└─────────────────────────────────────────────────────┘
```

### 6.2 ألوان الفئات (Chip Buttons)

| الفئة | اللون | الأيقونة |
|-------|-------|---------|
| استفسار | أزرق `bg-blue-50 text-blue-700` | `ti-help` |
| شكوى | أحمر `bg-red-50 text-red-700` | `ti-alert-triangle` |
| اقتراح | أخضر `bg-green-50 text-green-700` | `ti-bulb` |
| تعاون | بنفسجي `bg-purple-50 text-purple-700` | `ti-handshake` |

### 6.3 النموذج — أنماط إدخال

**لا `<input>` خام** — استخدام مكوّنات UI:

```blade
<x-ui.input
    name="name"
    label="الاسم الكامل"
    placeholder="مثال: محمد أحمد"
    required
/>

<x-ui.input
    name="email"
    type="email"
    label="البريد الإلكتروني"
    placeholder="example@email.com"
    dir="ltr"
    required
/>

<x-ui.input
    name="phone"
    type="tel"
    label="رقم الجوال (اختياري)"
    placeholder="+966 5X XXX XXXX"
    dir="ltr"
/>

{{-- فئة كأزرار toggle (chips) --}}
<div>
    <x-ui.label required>Fئة الرسالة</x-ui.label>
    <div class="flex flex-wrap gap-2">
        @foreach(['inquiry' => 'استفسار', 'complaint' => 'شكوى', 'suggestion' => 'اقتراح', 'partnership' => 'تعاون'] as $key => $label)
            <label class="cursor-pointer rounded-full border px-4 py-2 text-sm transition-all
                         has-[:checked]:bg-[#006C35] has-[:checked]:text-white has-[:checked]:border-[#006C35]
                         border-gray-200 text-gray-600 hover:border-[#006C35]">
                <input type="radio" name="category" value="{{ $key }}" class="sr-only" required />
                {{ $label }}
            </label>
        @endforeach
    </div>
    @error('category') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
</div>

<x-ui.textarea
    name="message"
    label="الرسالة *"
    placeholder="اكتب رسالتك هنا..."
    rows="5"
    required
/>
```

---

## 7. Rate Limiting

### 7.1 تعريف Throttle

```php
// app/Providers/AppServiceProvider.php — في boot()
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('contact', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});
```

### 7.2 التعامل مع الخطأ

```php
// في Controller — عند تجاوز الحد
if (RateLimiter::tooManyAttempts('contact:'.$request->ip(), 5)) {
    $seconds = RateLimiter::availableIn('contact:'.$request->ip());
    return back()->withErrors([
        'contact' => "تم تجاوز الحد المسموح. يرجى الانتظار {$seconds} ثانية."
    ]);
}
```

---

## 8. الربط بالفوتر

**إضافة رابط "تواصل معنا"** في `partials/public/footer.blade.php`:

```blade
<a href="{{ route('amrtm.contact') }}">تواصل معنا</a>
```

---

## 9. الاختبارات

### 9.1 Feature Test

```php
// tests/Feature/ContactTest.php
public function test_contact_page_renders()
{
    $response = $this->get('/contact');
    $response->assertOk();
    $response->assertSee('تواصل معنا');
}

public function test_store_validates_required_fields()
{
    $response = $this->post('/contact', []);
    $response->assertSessionHasErrors(['name', 'email', 'category', 'subject', 'message']);
}

public function test_store_creates_message()
{
    $response = $this->post('/contact', [
        'name' => 'محمد أحمد',
        'email' => 'test@example.com',
        'category' => 'inquiry',
        'subject' => 'استفسار عن الخدمة',
        'message' => 'أريد معرفة تفاصيل أكثر عن...',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('bs_contact_messages', [
        'email' => 'test@example.com',
        'category' => 'inquiry',
    ]);
}

public function test_rate_limiting_blocks_excessive_submissions()
{
    for ($i = 0; $i < 6; $i++) {
        $this->post('/contact', [
            'name' => 'Test',
            'email' => "test{$i}@example.com",
            'category' => 'inquiry',
            'subject' => 'Subject',
            'message' => 'Message content here',
        ]);
    }
    // The 6th should be rate-limited (429 or redirect with error)
    $this->assertDatabaseCount('bs_contact_messages', 5);
}
```

### 9.2 Checklist يدوي

- [ ] الصفحة تُعرض بشكل جميل على 375/768/1280px
- [ ] كل الحقول المطلوبة تُظهر رسالة خطأ عند التفريغ
- [ ] إرسال ناجح → رسالة نجاح + البريد يصل للإدارة
- [ ] 6 إرسالات متتالية → رسالة rate limit
- [ ] الفئات تظهر كأزرار toggle (ليست `<select>` خام)
- [ ] لا أخطاء Console

---

## 10. الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `database/migrations/2026_09_09_120000_create_bs_contact_messages_table.php` | **جديد** |
| `app/Models/Business/ContactMessage.php` | **جديد** |
| `app/Http/Controllers/ContactController.php` | **جديد** |
| `app/Mail/ContactMessageReceivedMail.php` | **جديد** |
| `resources/views/emails/contact_received.blade.php` | **جديد** |
| `resources/views/update_service/contact.blade.php` | **جديد** |
| `routes/web.php` | **تعديل** — إضافة مسارين |
| `app/Providers/AppServiceProvider.php` | **تعديل** — إضافة RateLimiter |
| `resources/views/partials/public/footer.blade.php` | **تعديل** — إضافة رابط |

---

## 11. معايير الإنجاز

| المعيار | التحقق |
|---------|--------|
| `php artisan migrate --force` | الهجرة تُنفَّذ |
| `php artisan test --filter=ContactTest` | 4 اختبارات PASS |
| `php artisan view:cache` | يمر |
| `npm run build` | يمر |
| البريد يصل عند الإرسال | مُتحقَّق |
| Rate limiting يعمل | 6 إرسالات → حظر |
| لا `<input>` خام في النموذج | صفر |
| التجاوب على 3 مقاسات | سليم |
