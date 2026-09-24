# ADMIN SSR Handoff — Parallel Page-Conversion Contract

> وثيقة داخلية موجهة للوكلاء الفرعيين (subagents). لا تُقرأ ولا تُعدَّل إلا من الرئيس.
> الغرض: تحويل صفحات لوحة تحكم الأدمن من عرض JS (hash/loadPageData) إلى عرض أولي بلade
> (server-render) مع بقاء التفاعلات عبر progressive enhancement.

## ⚠️ قواعد الالتزام (لا تنازل)
1. **لا تعدّل ملفات مشتركة**: `AdminServiceController.php`, `app/Support/**
   `DashboardRegistry.php`, `resources/views/update_service/dashboard/admin/layout.blade.php`,
   `resources/views/update_service/dashboard/admin/_admin_js.blade.php`,
   `_modals.blade.php`, `_admin_css.blade.php`, `routes/web.php`, `DashboardRegistry.php`.
   الملكية حصرية للرئيس — أي تعارض = فسخ.
2. **الملكية**: كل وكيل يملك ملفات `pages/*.blade.php` المجمّعة له فقط.
   لا تلمس page file لوكيل آخر. لا تُنشئ files جديدة.
3. **Render كامل**: الصفحة تُعرض بالكامل من `$pageData` عبر `@foreach` + `x-ui.*`.
   لا روابط `#` بدل بيانات، لا `href="#"`, لا `onclick` يُظهر بيانات مكررة من fetch.
4. **مسار البناء**: عند إزالة عنصر `@section('page-##')` من صفحاتك، لا تحذف `init()`
   الانتقالي في الـ init المشترك — الرئيس يتولى الإزالة النهائية. أنت تنقل القالب = كود Blade.
5. **المتبقيات**: أي عنصر لم تُحوّله لـ Blade ✓ = تُسد الإسناد إليه في نهاية ملفك بـ
   `{{-- TODO(ssr): ... --}}` — لكن الوكلاء ملزمون بإنجاز التحويل وليس ترك TODOs خلفهم.

## ══ البنية العامة ══
كل صفحة = `@extends('update_service.dashboard.admin.layout')` + `@section('admin-content')`
تعرض:
- **الشلب**: `layout.blade.php` → metas + sidebar + init + render loop.
- **البيانات**: عبر `$pageData` (من سيطرة `adminPage()`)، تُمرَّر للـ view مباشرة.
- **التفاعلات**: تبقى في `_admin_js.blade.php` → كل init يرصد `window.AMRTM_PAGE_DATA`
  إن وُجد، ولا يُعيد جلب البيانات (لا loadPageData عند وجود بيانات أولية).

## ══ عقد البيانات (Data Contract) ══
كل صفحة تتلقى `$pageData` وهو نفس بنية الاستجابة الـ JSON التي كان يستهلكها السكريبت؛
مفاتيح متطابقة حرفياً مع `toApiArray()` و `adminStats()`:

| الصفحة | key أساسي | مصدر (method) |
|--------|-----------|----------------|
| overview  | `stats` → `{requests, users, revenue, byStatus, chart_last7, top_services, recent_payments}` | `adminStats()` |
| requests  | `requests` → `{data, current_page, last_page, total, per_page}` (كل صف = `toApiArray()`) | `adminRequests()` |
| catalog   | `catalog` → `{categories, entities(base with category), services}` (+`specialties` clé) | `adminServices()` + `adminEntities()` + `adminSpecialties()` |
| pricing   | `pricing` → `{services (govService shape)}` | `adminServices()` |
| contracts | `contracts` → `{types, contracts, clauses}` | `adminListContracts()` + `ContractController` |
| finance   | `finance` → `{transactions, totals, types, week, pending, avg}` | `adminTransactions()` + `adminFinance()` |
| off-finance| `off_finance` → `{offices (with settlements), total, pending}` | `adminOffices()` + `adminSettlements()` |
| catalog-offices | `catalog_offices` → `{catalog, offices}` | `adminCategories()` + `adminOffices()` |
| office-specialties | `office_specialties` → `{specialties, offices}` | `adminSpecialties()` + `adminOffices()` |
| services-approvals | `approvals` → `{pending, approved, rejected, services (office), offices}` | `adminPendingOfficeServices()` |
| users     | `users` → `{data (BusinessUser), roles, stats}` | `adminUserStats()` + `adminUsers()` |
| analytics | `analytics` → `{chart, top, logs}` | `adminAnalytics()` |
| logs      | `logs` → `{data (RequestLog with request.user), types}` | `adminActivityLogs()` |
| permissions | `permissions` → `{roles, permissions_map}` | `adminPermissions()`/`permissionsMap` |
| settings  | `settings` → `{profile, contracts_visibility, commission}` | `adminSettings()`/`settingsData` |

> ملاحظة: المفاتيح أعلاه مضبوطة على أسماء `adminXxx` الموجودة فعلاً في
> `AdminServiceController`. إذا كانت أي صفحة تحتاج `{data}` ببنية مختلفة، استخدم
> الـ `@foreach` على `$pageData['key']` نفسه الذي يبنيه الـ controller؛ لا تكرر استعلامات.

## ══ اصطلاحات الكتابة ══
- `@foreach($pageData['requests']['data'] as $req)` مع `x-ui.*` لكل عنصر تفاعلي.
- `@forelse` للحالة الفارغة مع رسالة عربية مناسبة.
- لا `@foreach` داخل `@if` متداخل بدون حاجة حقيقية؛ عوّض بـ `@forelse`.
- الأيقونات: `<i class="ti ti-xxx"></i>` (tabler-icons) — نفس المتوفر في الـ dashboard.
- الألوان/الحالات: `ServiceRequest::statusLabels()` + `stInfo()` في `dashboard-react` JS
  تبقى كما هي (لا تغير بنية الـ hash).
- القيمة الافتراضية `0` لأي عداد قبل أن يملأه الـ JS (structure matches dashboard JS).

## ══ للتحقق ══
`php artisan view:cache` ثم `php artisan test --filter=DashboardViewsTest`.

## ══ التخصيص لكل وكيل (انظر كل موجه) ══
الصفحات:
A: requests-page  (الوكيل A)
B: catalog + pricing  (الوكيل B)
C: overview (الوكيل C — أمامه overview blade بالفعل؛ يبني على نمطه)
D: contracts + finance  (الوكيل D)
E: off-finance + catalog-offices  (الوكيل E)
F: office-specialties + services-approvals  (الوكيل F)
G: users + analytics + logs  (الوكيل G)
H: permissions + settings  (الوكيل H)

كل وكيل: يحوّل صفحاته بنية `@foreach` + `x-ui.*`, ويعدّل `init()`-كما-هو فقط في
أسطر صفحته (لا ينقل `init()` نفسه)، ثم يشغّل `view:cache` و `--filter=DashboardViewsTest`
ويُبقي المجموعة خضراء قبل أن يسلّم.
