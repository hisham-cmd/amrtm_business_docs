# توثيق نظام العقود — منصة آمر تم

---

## فهرس المحتويات

1. [نظرة عامة](#1-نظرة-عامة)
2. [الخريطة المفاهيمية](#2-الخريطة-المفاهيمية)
3. [شرح الفلو (Workflow)](#3-شرح-الفلو)
4. [شرح كل ملف بالتفصيل](#4-شرح-كل-ملف-بالتفصيل)
5. [هيكل قاعدة البيانات](#5-هيكل-قاعدة-البيانات)
6. [تحليل مستوى الأمان](#6-تحليل-مستوى-الأمان)
7. [قائمة المسارات (Routes)](#7-قائمة-المسارات)
8. [أنواع العقود المحصّلة](#8-أنواع-العقود-المحصّلة)

---

## 1. نظرة عامة

نظام العقود في منصة **آمر تم** يتيح:
- **إنشاء عقود** إلكترونية بأنواع مختلفة (تجاري / صناعي)
- **إرسال العقد** للطرف الثاني عبر بريد إلكتروني مع رابط موقّع
- **عرض العقد** للطرف الثاني في صفحة مقروءة فقط (بدون تعديل)
- **تحميل PDF** مع رمز QR للتحقق من صحته
- **تتبع حالة العقد** (مسودة → نشط → منتهي / مُلغى / مُعلّق)

### التقنيات المستخدمة

| الطبقة | التقنية |
|--------|---------|
| Backend | Laravel 12 (PHP 8.2+) |
| Database | MySQL (`amrtmco_business`) |
| PDF | barryvdh/laravel-dompdf |
| QR Code | bacon/bacon-qr-code |
| النص العربي | khaled.alshamaa/ar-php |
| الخط | Tajawal (Regular + Bold) |
| البريد | resend/resend-laravel |

---

## 2. الخريطة المفاهيمية

```
┌──────────────────────────────────────────────────────────────────────────┐
│                        خريطة مفاهيم نظام العقود                          │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────────┐     ┌─────────────────┐     ┌─────────────────────┐
│   Admin     │     │  Office (مكتب)  │     │  الطرف الثاني       │
│  (مدير)     │     │  (مُصدر العقد)  │     │  (المستلم)          │
└──────┬──────┘     └───────┬─────────┘     └──────────┬──────────┘
       │                    │                          │
       │ 1. يُعرّف أنواع   │                          │
       │    العقود والبنود  │                          │
       ▼                    │                          │
┌──────────────────┐        │                          │
│ bs_contract_types│        │                          │
│ bs_contract_clauses      │                          │
└──────────────────┘        │                          │
                            │ 2. يختار نوع العقد       │
                            │    ويُنشئ عقداً جديداً   │
                            ▼                          │
                   ┌──────────────────┐                │
                   │ bs_contracts     │                │
                   │ (status: draft)  │                │
                   └────────┬─────────┘                │
                            │                          │
                            │ 3. يرسل العقد عبر البريد │
                            │    (يولّد view_token)    │
                            ▼                          ▼
                   ┌──────────────────┐     ┌──────────────────┐
                   │  البريد الإلكتروني│────▶│ صفحة العرض       │
                   │  (contract_link) │     │ العامة (قراءة    │
                   └──────────────────┘     │ فقط + حماية)     │
                                            └────────┬─────────┘
                                                     │
                                          4. يُحمّل PDF
                                             مع رمز QR
                                                     ▼
                                            ┌──────────────────┐
                                            │  PDF معتمد       │
                                            │  + رمز QR        │
                                            └──────────────────┘
```

---

## 3. شرح الفلو

### الخطوة 1: تعريف الأنواع (Admin)
```
Admin ──▶ /admin/contract-types ──▶ ContractController::adminStoreType
                │
                ├── إنشاء نوع العقد (اسم + فئة + سعر)
                └── إنشاء البنود لكل نوع (ContractController::adminStoreClause)
```
- المدير يُعرّف أنواع العقود (مثل: عقد البيع والشراء، عقد الخدمات...)
- لكل نوع: **بنود افتراضية** تحتوي نصوصاً مع placeholders مثل `{start}`, `{end}`, `{price}`
- السعر الافتراضي لكل نوع

### الخطوة 2: إنشاء العقد (Office)
```
Office ──▶ /office/contracts ──▶ OfficeDashboardController::storeContract
                │
                ├── اختيار نوع العقد
                ├── تعبئة بيانات الطرف الثاني (اسم + بريد + تواريخ)
                ├── إضافة بنود مخصصة اختيارية
                ├── توليد رقم تلقائي: CNT-XXXX
                └── حفظ بنود الأدمن + بنود المكتب في clauses_json (Snapshot)
```
**مهم:** عند الإنشاء يتم أخذ **لقطة (Snapshot)** للبنود والسعر، حتى لو تغيرت لاحقاً البنود الأصلية.

### الخطوة 3: إرسال العقد (Office → الطرف الثاني)
```
Office ──▶ /office/contracts/{id}/send ──▶ ContractController::sendToParty
                │
                ├── التحقق من صلاحية المكتب
                ├── توليد view_token فريد (Str::random(40))
                ├── إنشاء رابط موقّع (URL::signedRoute) صالح 30 يوم
                ├── إرسال بريد إلكتروني بقالب HTML جميل
                └── تحديث الحالة إلى "active" + تسجيل وقت الإرسال
```

### الخطوة 4: عرض العقد (الطرف الثاني)
```
الطرف الثاني ──▶ /contract-view/{token} ──▶ ContractController::publicShow
                │
                ├── التحقق من صحة التوقيع (URL::hasValidSignature)
                ├── تحميل العقد من قاعدة البيانات
                ├── تسجيل وقت أول مشاهدة (second_party_viewed_at)
                └── عرض صفحة مقروءة فقط مع:
                    ├── حماية من النسخ (user-select: none)
                    ├── حماية من الطباعة (CSS @media print)
                    ├── حماية من الكليك يمين (contextmenu)
                    ├── حماية من اختصارات الكيبورد (Ctrl+C, Ctrl+P, etc)
                    └── علامة مائية (watermark)
```

### الخطوة 5: تحميل PDF
```
أي طرف ──▶ /office/contracts/{id}/pdf ──▶ ContractController::downloadPdf
                │
                ├── التحقق من الصلاحية (المُصدر أو الطرف الثاني)
                ├── تحويل النصوص العربية (Arabic glyph shaping)
                ├── تسجيل خط Tajawal
                ├── توليد رمز QR من الرابط الموقّع
                └── تحميل PDF بتنسيق A4 مع:
                    ├── بيانات العقد
                    ├── البنود المُعالَجة (بالتواريخ والسعر)
                    ├── أماكن التوقيع
                    └── رمز QR في الأسفل
```

---

## 4. شرح كل ملف بالتفصيل

### 4.1. الكونترولرات (Controllers)

#### `ContractController.php` — الكونترولر الرئيسي
**المسار:** `app/Http/Controllers/UpdateService/ContractController.php`
**عدد الأسطر:** 597

| الدالة | الوظيفة | ملاحظات |
|--------|---------|---------|
| `officeUser()` | جلب المستخدم الحالي (مكتب) | Auth guard: office |
| `office()` | جلب بيانات المكتب | من خلال Relationship |
| `findUsableContract($id)` | جلب عقد مع بيانات نوعه | JOIN مع bs_contract_types |
| `ensureViewToken($token)` | توليد توقيع فريد للعقد | Str::random(40) + حفظ في DB |
| `renderClauses($contract)` | معالجة البنود واستبدال المتغيرات | `{start}` → تاريخ البداية، `{end}` → النهاية، `{price}` → السعر |
| `formatDate($date)` | تنسيق التاريخ بالعربي | Carbon translatedFormat |
| `sendToParty()` | إرسال العقد للطرف الثاني | يُنشئ رابط موقّع + يرسل بريد |
| `downloadPdf()` | تحميل PDF للعقد | DomPDF + QR + خط عربي |
| `qrHtml()` | توليد HTML لرمز QR | bacon-qr-code → HTML table |
| `publicShow()` | عرض العقد للطرف الثاني | صفحة مقروءة فقط + تتبع المشاهدة |
| `publicPdf()` | تحميل PDF من الرابط العام | يحتاج توقيع صالح |
| `adminListTypes()` | عرض أنواع العقود (Admin) | مع عدد البنود |
| `adminStoreType()` | إنشاء نوع جديد (Admin) | + تحقق من البيانات |
| `adminUpdateType()` | تحديث نوع (Admin) | |
| `adminDeleteType()` | حذف نوع (Admin) | CASCADE يحذف البنود |
| `adminListClauses()` | عرض بنود نوع معين (Admin) | |
| `adminStoreClause()` | إنشاء بند جديد (Admin) | |
| `adminUpdateClause()` | تحديث بند (Admin) | |
| `adminDeleteClause()` | حذف بند (Admin) | |
| `adminListContracts()` | عرض كل العقود (Admin) | فلترة + بحث + JOIN مع المكاتب |
| `adminStoreContract()` | إنشاء عقد نيابة عن مكتب (Admin) | مع لقطة البنود |
| `adminContractPdf()` | تحميل PDF (Admin) | |
| `arShape()` | تحويل النص العربي للopedf | ArPHP glyph shaping |
| `registerArabicFont()` | تسجيل خط Tajawal في DomPDF | Regular + Bold |

#### `OfficeDashboardController.php` — كونترولر مكتب العمل
**المسار:** `app/Http/Controllers/UpdateService/OfficeDashboardController.php`
**الأسطر ذات الصلة:** 466-691

| الدالة | الوظيفة |
|--------|---------|
| `listContractTypes()` | عرض أنواع العقود المتاحة |
| `listContractClauses($typeId)` | عرض بنود نوع معين |
| `listContracts()` | عرض عقود المكتب (فلترة: الكل/صادر/وارد) |
| `storeContract()` | إنشاء عقد جديد مع بنود مخصصة |
| `showContract($id)` | عرض تفاصيل عقد |
| `updateContractStatus($id)` | تحديث حالة العقد |
| `deleteContract($id)` | حذف العقد (مسودة فقط) |
| `generateContractNumber()` | توليد رقم CNT-XXXX |

---

### 4.2. الترحيلات (Migrations)

#### Migration 1: `2026_08_31_100000_create_contract_tables.php`
**الوظيفة:** إنشاء الجداول الأساسية + تهيئة 4 أنواع عقود

```
┌─────────────────────────────────────┐
│ bs_contract_types                   │
│─────────────────────────────────────│
│ id, name (unique), sort_order,     │
│ created_at, updated_at             │
└─────────────────────────────────────┘
         │ 1:N
         ▼
┌─────────────────────────────────────┐
│ bs_contract_clauses                 │
│─────────────────────────────────────│
│ id, contract_type_id (FK CASCADE), │
│ name, description, sort_order,     │
│ created_at, updated_at             │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ bs_contracts                        │
│─────────────────────────────────────│
│ id, number, contract_type_id       │
│ (FK NULL ON DELETE),               │
│ start_date, end_date, status,      │
│ created_at, updated_at             │
└─────────────────────────────────────┘
```

**البيانات المحصّلة:** 4 أنواع (خدمات فورية، سنوية، اشتراكات، وساطة)

#### Migration 2: `2026_08_31_100100_add_company_profile_and_contract_price.php`
**الوظيفة:**
- إضافة عمود `price` لـ `bs_contract_types`
- إنشاء جدول `bs_company_profiles` (بيانات الشركة: اسم، سجل تجاري، عنوان، بريد، هاتف، مدير)

#### Migration 3: `2026_08_31_100200_seed_contract_type_prices.php`
**الوظيفة:** تعبئة أسعار الأنواع الـ 4 الأولية (2999، 4999، 1499، 3499 ريال)

#### Migration 4: `2026_08_31_100500_add_snapshot_to_contracts.php`
**الوظيفة:** إضافة 3 أعمدة حيوية لـ `bs_contracts`:
- `price` — لقطة السعر عند الإنشاء
- `clauses_json` — لقطة البنود عند الإنشاء (JSON)
- `party_name` — اسم الطرف الثاني

> **لماذا Snapshot؟** لأن البنود قد تتغير لاحقاً، لكن العقد يجب أن يبقى كما هو عند التوقيع.

#### Migration 5: `2026_09_03_100000_add_office_tracking_to_contracts.php`
**الوظيفة:** إضافة أعمدة تتبع المكاتب:
- `created_by_office_id` — المكتب المُصدر
- `party_office_id` — مكتب الطرف الثاني
- `party_email` — بريد الطرف الثاني
- `description` — وصف العقد
- `category` — فئة العقد (تجاري/صناعي)

#### Migration 6: `2026_09_03_100500_add_category_to_contract_types.php`
**الوظيفة:** إضافة عمود `category` لـ `bs_contract_types`

#### Migration 7: `2026_09_03_110000_seed_all_contract_types.php`
**الوظيفة:** إعادة تهيئة **كل أنواع العقود** (28 نوع):
- 18 نوع تجاري (بيع وشراء، توريد، توزيع، وكالة، فرنشايز، شراكة، خدمات، إدارة، تسويق، وساطة، نقل، تخزين، إيجار، مقاولات، صيانة، استشارات، تمويل، استثمار، تصنيع، دولي)
- 10+ نوع صناعي

كل نوع يحتوي **7-10 بنود** مع نصوص تفصيلية ومتغيرات `{start}`, `{end}`, `{price}`

#### Migration 8: `2026_09_03_120000_add_contract_sharing_fields.php`
**الوظيفة:** إضافة أعمدة المشاركة والتحقق:
- `view_token` — توقيع فريد للرابط العام (64 حرف، unique)
- `second_party_email_sent_at` — وقت إرسال البريد
- `pdf_path` — مسار ملف PDF المُولَّد
- `second_party_viewed_at` — وقت أول مشاهدة من الطرف الثاني

---

### 4.3. القوالب (Blade Templates)

#### `contract_view.blade.php` — صفحة العرض العامة
**المسار:** `resources/views/update_service/public/contract_view.blade.php`
**السطور:** 232

**الوظيفة:** صفحة مقروءة فقط يراها الطرف الثاني عند فتح الرابط.

**الحماية:**
- `user-select: none` — منع تحديد النص
- `contextmenu` block — منع الكليك يمين
- `copy, cut, paste` block — منع النسخ والقص
- `dragstart, drop` block — منع السحب
- `PrintScreen, Ctrl+P, Ctrl+U, Ctrl+C/X/S/A` block — منع اختصارات الكيبورد
- watermark — علامة مائية باسم الطرف الثاني
- `@media print` — إخفاء العناصر الحساسة عند الطباعة

**المحتوى:**
- شريط علوي (آمر تم + شارة "عرض للطرف الثاني")
- جدول بيانات العقد (نوع، طرف، تواريخ، سعر)
- وصف العقد
- البنود المعالجة (مع التواريخ والسعر المُħدَّث)
- ملاحظة التحقق

#### `contract_pdf.blade.php` — قالب PDF
**المسار:** `resources/views/update_service/office/contract_pdf.blade.php`
**السطور:** 212

**الوظيفة:** قالب HTML يُحوَّل إلى PDF عبر DomPDF.

**المحتوى:**
- ترويسة (اسم المنصة + رقم العقد)
- عنوان العقد
- جدول البيانات
- البنود المعالجة
- أماكن التوقيع (الطرف الأول + الطرف الثاني)
- رمز QR (يربط بالرابط الموقّع العام)
- تذييل (رسالة إصدار إلكتروني)

**ملاحظات تقنية:**
- `direction: ltr` على الـ body لأن النص العربي يُعالَج مسبقاً عبر ArPHP
- `text-align: right` لتحقيق الاتجاه العربي الصحيح

#### `contract_link.blade.php` — قالب البريد الإلكتروني
**المسار:** `resources/views/update_service/emails/contract_link.blade.php`
**السطور:** 95

**الوظيفة:** بريد إلكتروني HTML يُرسل للطرف الثاني.

**المحتوى:**
- ترويسة (رقم العقد + نوعه)
- رسالة ترحيب بالعربي
- جدول بيانات مختصر (رقم، نوع، طرف، تواريخ)
- زر "عرض العقد الإلكتروني" (يربط بالرابط الموقّع)
- ملاحظة: "الرابط صالح لمدة 30 يوماً"
- تذييل: رسالة آلية

#### `contracts_create_static.blade.php` — صفحة الإنشاء التفاعلية
**المسار:** `resources/views/update_service/contracts_create_static.blade.php`
**السطور:** 1636

**الوظيفة:** صفحة تفاعلية بإمكانيات 3 خطوات:

| الخطوة | المحتوى |
|--------|---------|
| 1 — بيانات العقد | اختيار النوع + السعر + بيانات الطرف + تواريخ |
| 2 — معاينة العقد | معاينة البنود الكاملة مع المعالجة |
| 3 — إنشاء العقد | تأكيد الإنشاء + توليد العقد |

**البيانات المُضمنة:** كائن `CONTRACT_TYPES` يحتوي 7 أنواع مع بنودها (صفحة demo/static).

---

### 4.4. ملف SQL

#### `amrtmco_business.sql`
**الوظيفة:** نسخة احتياطية كاملة لقاعدة البيانات تتضمن:
- هيكل جداول العقود
- البيانات المحصّلة (أنواع + بنود)
- بيانات المكاتب والعلاقات

---

## 5. هيكل قاعدة البيانات

### النهاية النهائية لجدول `bs_contracts`

```
bs_contracts
├── id                    BIGINT PK AUTO_INCREMENT
├── number                VARCHAR  (CNT-0001)
├── contract_type_id      BIGINT FK → bs_contract_types (SET NULL ON DELETE)
├── created_by_office_id  BIGINT  (المكتب المُصدر)
├── party_office_id       BIGINT  (مكتب الطرف الثاني)
├── price                 DECIMAL(12,2)  (لقطة السعر)
├── clauses_json          JSON  (لقطة البنود)
├── party_name            VARCHAR  (اسم الطرف الثاني)
├── party_email           VARCHAR  (بريد الطرف الثاني)
├── start_date            DATE
├── end_date              DATE
├── status                VARCHAR  (draft|active|expired|suspended|completed|cancelled)
├── view_token            VARCHAR(64) UNIQUE  (توقيع الرابط العام)
├── second_party_email_sent_at  TIMESTAMP  (وقت إرسال البريد)
├── pdf_path              VARCHAR  (مسار PDF)
├── second_party_viewed_at      TIMESTAMP  (وقت أول مشاهدة)
├── description           TEXT  (وصف العقد)
├── category              VARCHAR  (تجاري|صناعي)
├── created_at            TIMESTAMP
└── updated_at            TIMESTAMP
```

### حالة العقد (State Machine)

```
                ┌──────────┐
                │  draft   │  ← عند الإنشاء
                └────┬─────┘
                     │ إرسال للطرف الثاني
                     ▼
                ┌──────────┐
                │  active  │  ← العقد ساري
                └────┬─────┘
                     │
          ┌──────────┼──────────┐
          ▼          ▼          ▼
    ┌───────────┐ ┌───────────┐ ┌───────────┐
    │ completed │ │suspended  │ │cancelled  │
    └───────────┘ └───────────┘ └───────────┘
                       │
                       ▼
                 ┌───────────┐
                 │  expired  │  ← انتهاء المدة
                 └───────────┘
```

**ملاحظة:** فقط المسودات (draft) يمكن حذفها. لا يمكن حذف عقد نشط أو منتهي.

---

## 6. تحليل مستوى الأمان

### نقاط القوة

| الميزة | التفاصيل | المستوى |
|--------|----------|---------|
| **روابط موقّعة** | `URL::signedRoute` — التوقيع المادي يمنع التلاعب بالرابط | عالي |
| **صلاحية محدودة** | الروابط تنتهي بعد 30 يوماً `now()->addDays(30)` | عالي |
| **حماية العرض** | صفحة مقروءة فقط مع حماية JS شاملة | متوسط |
| **تسلسل رقمي** | `view_token` فريد لكل عقد (64 حرف عشوائي) | عالي |
| **تتبع المشاهدة** | تسجيل وقت أول مشاهدة للطرف الثاني | متوسط |
| **صلاحيات المكاتب** | كل مكتب يرى عقوده فقط (created_by / party_office) | عالي |
| **حذف آمن** | فقط المسودات يمكن حذفها | عالي |
| **لقطة البنود** | `clauses_json` يحفظ البنود ك snapshot — لا تتغير مع تحديث البنود الأصلية | عالي |
| **تحقق من البيانات** | `$request->validate()` على كل endpoints | عالي |
| **عدم استخدام Eloquent** | Query Builder مباشرة — يقلل المشاكل المعقدة | متوسط |

### نقاط ضعف وتحسينات مقترحة

| المشكلة | التفاصيل | الخطورة | التحسين المقترح |
|---------|----------|---------|----------------|
| **لا يوجد Eloquent Model** | كل الاستعلامات raw عبر Query Builder | متوسط | إنشاء Model + Relationships |
| **لا يوجد Rate Limiting** | endpoints الحساسة بدون حدود | عالية | إضافة `throttle` middleware |
| **لا يوجد CSRF على JSON** | بعض endpoints ترجع JSON بدون حماية CSRF | منخفضة | التأكد من وجود CSRF token |
| **لا يوجد JWT/Session timeout** | لا يوجد انتهاء صلاحيات تلقائي | متوسط | إضافة session timeout |
| **SQL Injection محتمل** | `LIKE "%$search%"` بدون parameterization | عالية | استخدام `?` placeholders |
| **لا يوجد signing key مُحدّث** | `APP_KEY` هو المفتاح الوحيد للتوقيع | متوسط | rotation دوري لـ APP_KEY |
| **لا يوجد audit trail** | لا يوجد سجل تغييرات على العقود | عالية | إنشاء جدول audit_log |
| **لا يوجد تشفير للبيانات** | بيانات العقد محفوظة كنص واضح | منخفضة | تشفير حقول حساسة |
| **الحماية بالـ JS قابلة للتجاهل** | Developer Tools يتجاوز الحماية | متوسط | إضافة watermark خادم + DRM |
| **لا يوجد تحقق من البريد** | `party_email` يُقبل أي بريد بدون تحقق | منخفضة | إضافة email verification |
| **لا يوجد مؤقت للholm** | عقد قد يبقى "نشط" بعد انتهاء المده | متوسط | Cron job لتحديث الحالة |

### تقييم الأمان الإجمالي

```
الأمان: ★★★☆☆ (3/5)

نقاط القوة الرئيسية:
✓ الروابط الموقّعة (Signed URLs)
✓ الصلاحيات المُقسّمة
✓ تتبع المشاهدة
✓ حماية العرض الأساسية

ما ينقص:
✗ Rate Limiting
✗ Audit Trail
✗ Parameterized Queries في البحث
✗ تشفير البيانات الحساسة
```

---

## 7. قائمة المسارات

### مسارات عامة (بدون مصادقة)

| Method | URI | الوظيفة |
|--------|-----|---------|
| GET | `/create-contract` | صفحة إنشاء العقد (static/demo) |
| GET | `/contract-view/{token}` | عرض العقد للطرف الثاني |
| GET | `/contract-view/{token}/pdf` | تحميل PDF من الرابط العام |

### مسارات المكتب (Auth: office)

| Method | URI | الوظيفة |
|--------|-----|---------|
| GET | `/office/contract-types` | عرض الأنواع |
| GET | `/office/contract-types/{id}/clauses` | عرض بنود نوع |
| GET | `/office/contracts` | عقود المكتب |
| POST | `/office/contracts` | إنشاء عقد |
| GET | `/office/contracts/{id}` | تفاصيل عقد |
| PUT | `/office/contracts/{id}/status` | تحديث الحالة |
| DELETE | `/office/contracts/{id}` | حذف (مسودة فقط) |
| POST | `/office/contracts/{id}/send` | إرسال للطرف الثاني |
| GET | `/office/contracts/{id}/pdf` | تحميل PDF |

### مسارات الإدارة (Auth: admin)

| Method | URI | الوظيفة |
|--------|-----|---------|
| GET | `/admin/contract-types` | عرض كل الأنواع |
| POST | `/admin/contract-types` | إنشاء نوع |
| PUT | `/admin/contract-types/{id}` | تحديث نوع |
| DELETE | `/admin/contract-types/{id}` | حذف نوع |
| GET | `/admin/contract-types/{id}/clauses` | عرض بنود |
| POST | `/admin/contract-types/{id}/clauses` | إنشاء بند |
| PUT | `/admin/contract-types/{id}/clauses/{clauseId}` | تحديث بند |
| DELETE | `/admin/contract-types/{id}/clauses/{clauseId}` | حذف بند |
| GET | `/admin/contracts` | كل العقود |
| POST | `/admin/contracts` | إنشاء عقد نيابة عن مكتب |
| GET | `/admin/contracts/{id}/pdf` | تحميل PDF (Admin) |

---

## 8. أنواع العقود المحصّلة

### تجاري (18 نوع)

| النوع | السعر (ريال) | عدد البنود |
|-------|-------------|-----------|
| عقد البيع والشراء | 3,999 | 10 |
| عقد التوريد | 5,999 | 9 |
| عقد التوزيع | 5,499 | 10 |
| عقد الوكالة التجارية | 4,999 | 9 |
| عقد الامتياز التجاري (فرنشايز) | 9,999 | 10 |
| عقد الشراكة | 7,999 | 10 |
| عقد الخدمات | 3,499 | 9 |
| عقد الإدارة والتشغيل | 7,499 | 9 |
| عقد التسويق | 5,999 | 8 |
| عقد الوساطة التجارية | 4,499 | 8 |
| عقد النقل | 4,999 | 8 |
| عقد التخزين | 3,999 | 8 |
| عقد الإيجار التجاري | 3,499 | 8 |
| عقد المقاولات | 8,999 | 9 |
| عقد الصيانة والتشغيل | 6,499 | 7 |
| عقد الاستشارات | 5,999 | 7 |
| عقد التمويل | 9,499 | 9 |
| عقد الاستثمار | 8,499 | 8 |
| عقد التصنيع للغير | 7,999 | 8 |
| عقد التجارة الدولية والاستيراد والتصدير | 9,999 | 8 |

### صناعي (10+ أنواع)
-types صناعية مُضافة في Migration #7 تشمل عقود التصنيع، مراقبة الجودة، الصيانة الصناعية، إلخ.

---

> **تاريخ الإنشاء:** سبتمبر 2026
> **المنصة:** آمر تم للخدمات التجارية (amrtm.com.sa)
> **آخر تحديث للتوثيق:** بناءً على كود المشروع الحالي
