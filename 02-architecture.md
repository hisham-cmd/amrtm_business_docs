# 02 — المعمارية (Architecture)

## 1. البنية العامة

```
┌─────────────────────────────────────────────────────────┐
│                     Client (Browser)                     │
│   Flowbite UI (Blade)     ────▶  Laravel Web             │
│   React (مستقبلاً)        ────▶  Laravel API /api/v1     │
└─────────────────────────┬───────────────────────────────┘
                          │
                   ┌──────▼──────┐
                   │  Laravel    │  (Model-View-Controller)
                   │  Controllers│
                   └──────┬──────┘
                          │
                   ┌──────▼──────┐
                   │    Models   │  Eloquent / DB
                   └─────────────┘
```

- **Blade views** تحت `resources/views/` مع Layouts/Partials مشتركة.
- **API layer** تحت `routes/api.php` (`/api/v1`) بغلاف استجابة موحّد.
- **Middleware** للمصادقة (`auth.api`, `OfficeAuthMiddleware`).

## 2. طبقات الدليل (المستهدف)

```
app/
  Http/Controllers/          ← رقيق، بلا منطق أعمال
  Http/Middleware/           ← مصادقة وتفويض
  Models/                    ← Eloquent + علاقات + scopes
  ...
resources/
  views/
    layouts/                 ← public, public-page, auth, dashboard
    partials/public/         ← navbar, footer
    components/ui/           ← button, input, textarea, label, select, badge, card
    amrtm/auth/              ← صفحات الأوتوكات
    update_service/          ← الصفحات الوظيفية (monoliths + pages)
routes/
  web.php, api.php
docs/
```

## 3. هيكلية Blade الجديدة

| الملف | الدور |
|------|-------|
| `layouts/public.blade.php` | الـ`<html>` الأساسي: fonts، iOS، CSRF، `@vite`، `@yield('content')`، `@stack('styles'/'scripts')` |
| `layouts/public-page.blade.php` | قبل navbar + footer العام والمساحة الرئيسية `@yield('page-content')` |
| `layouts/auth.blade.php` | تمد `public` وتوفر `@yield('auth-content')` |
| `layouts/dashboard.blade.php` | هيكل Sidebar + Topbar للوحات التحكم |
| `partials/public/navbar.blade.php` | الـ navbar الموحّد (حافظ على IDs المتوافقة مع المونوليثات) |
| `partials/public/footer.blade.php` | الـ footer الموحّد |
| `components/ui/*` | مكونات Flowbite/Tailwind قابلة لإعادة الاستخدام |

## 4. قاعدة الاستجابة (API envelope)

```json
{
  "isSuccess": true,
  "value": {},
  "error": null,
  "statusCode": 200
}
```

## 5. الجراحة البرمجية (Surgical)

- المس فقط ما يحتاج للمس.
- لا كود تجريبي/placeholder.
- كل feature غير مكتمل يُعيَّن إلى `pending` في `PROJECT_MAP.md`.
- لا imports/أو orphaned code.

## 6. نموذج الأدوار وقواعد العمل (Business Roles)

> حلقة الطلب تُحدَّد بنوع المنشأة. القواعد النهائية في قسم «قواعد العمل» بـ [`PROJECT_MAP.md`](PROJECT_MAP.md).

| الدور | طريقة استقبال الطلبات | بيانات العميل/التواصل | تدخل الأدمن |
|-------|------------------------|------------------------|-------------|
| **مكتب مساند** | إسناد الأدمن فقط (يدوي أو بث لشبكة المكاتب ثم حجز) — **بلا أي «طلبات مباشرة»** | بعد القبول: التواصل داخل المنصة فقط — **يمنع إفصاح بيانات التواصل** | إجباري (إسناد) حتى القبول |
| **المنشأة الاستشارية** | مباشرة («الاستشارات») | يُعرض ملف العميل + محادثة داخل المنصة | غير مطلوب — تنفيذ بلا إسناد |

- لا توجد واجهة/تبويب/مسار «طلبات مباشرة» لمكتب مساند إطلاقاً.
- التواصل دائماً داخل المنصة (رسائل/محادثة موثقة) ولا يُمرَّر رقم جوال/بريد/واتساب بين العميل والمكاتب المساندة.