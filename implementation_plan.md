# تهيئة وتطوير صفحة المستشارين — خطة تنفيذ شاملة

## الهدف
تطوير صفحة المستشارين الحالية (`/consultants`) لتصبح صفحة متكاملة تتضمن: باركود QR، إحصائيات الزوار والاستشارات، استشارات فيديو، حفظ البيانات 5 سنوات، وعرض أكثر مكتب تفاعلاً في أعلى الصفحة.

---

## 📊 تحليل الوضع الحالي

### الملفات الموجودة
| الملف | الحالة | الحجم |
|-------|--------|-------|
| [`consultants_directory.blade.php`](file:///c:/react_projects/amrtm_business/resources/views/update_service/consultants_directory.blade.php) | ✅ موجود | 356 سطر |
| [`consultant_detail.blade.php`](file:///c:/react_projects/amrtm_business/resources/views/update_service/consultant_detail.blade.php) | ✅ موجود | 337 سطر |
| [`ServiceCatalogController.php`](file:///c:/react_projects/amrtm_business/app/Http/Controllers/UpdateService/ServiceCatalogController.php) | ✅ يحوي `consultantsDirectory()` + `consultantDetail()` |
| [`Office.php`](file:///c:/react_projects/amrtm_business/app/Models/Business/Office.php) | ✅ يحوي `scopeConsultants()` + `office_code` + `public_token` |
| [`OfficeProfile.php`](file:///c:/react_projects/amrtm_business/app/Models/Business/OfficeProfile.php) | ✅ يحوي `office_code` + `qr_code` |
| [`OfficeRequest.php`](file:///c:/react_projects/amrtm_business/app/Models/Business/OfficeRequest.php) | ✅ يحوي `status`, `completed_at`, `ref_number` |

### Routes الموجودة
```
GET /consultants → consultantsDirectory() → consultants_directory.blade.php
GET /consultants/{officeId} → consultantDetail() → consultant_detail.blade.php
```

### بنية قاعدة البيانات ذات الصلة
- `bs_offices`: `office_code`, `public_token`, `is_active`, `is_verified`, `subscription_type`, `account_types`
- `bs_office_profiles`: `office_code`, `qr_code`
- `bs_office_requests`: `ref_number`, `status`, `completed_at`, `created_at`
- `bs_office_services`: `name_ar`, `price`, `duration`

---

## المتطلبات المطلوب تنفيذها

| # | الميزة | الأولوية |
|---|--------|---------|
| 1 | **باركود QR لكل مستشار** — رمز QR فريد يوجّه إلى صفحة المستشار | 🔴 عالية |
| 2 | **عداد الزوار** — عدد مرات مشاهدة صفحة كل مستشار | 🔴 عالية |
| 3 | **عدد الاستشارات المُقدَّمة** — إحصائية حقيقية من `bs_office_requests` | 🔴 عالية |
| 4 | **أكثر مكتب/مستشار تفاعلاً** — يظهر في أعلى الصفحة كبطاقة مميزة | 🔴 عالية |
| 5 | **استشارات فيديو** — إمكانية طلب استشارة بالفيديو | 🟡 متوسطة |
| 6 | **حفظ بيانات الاستشارات 5 سنوات** — سياسة احتفاظ + عمود `data_retention_until` | 🟡 متوسطة |
| 7 | **تهيئة الصفحة بالكامل** — توحيد التصميم وإضافة الأقسام الجديدة | 🔴 عالية |

---

## Proposed Changes

### 1️⃣ قاعدة البيانات — Migration جديد

#### [NEW] `2026_09_06_100000_enhance_consultants_page.php`
Migration واحد يُنفَّذ بشكل آمن (مع `hasColumn` checks):

```
bs_offices:
  + views_count (unsignedBigInteger, default 0) — عداد الزوار
  + video_consultation_enabled (boolean, default false) — دعم استشارات الفيديو

bs_office_requests:
  + consultation_type (enum: 'standard','video', default 'standard') — نوع الاستشارة
  + video_meeting_url (string, nullable) — رابط اجتماع الفيديو
  + data_retention_until (timestamp, nullable) — تاريخ انتهاء حفظ البيانات (5 سنوات من الإنشاء)
```

> [!IMPORTANT]
> **حفظ 5 سنوات**: عند إنشاء أي `OfficeRequest` جديد، يُحسب `data_retention_until = now() + 5 years` تلقائياً. هذا **لا يحذف** البيانات تلقائياً، بل يُسجّل التاريخ لاستخدامه لاحقاً في عمليات تنظيف مجدولة إن رُغب.

---

### 2️⃣ نموذج Office — تعديل

#### [MODIFY] [`Office.php`](file:///c:/react_projects/amrtm_business/app/Models/Business/Office.php)
- إضافة `views_count` و `video_consultation_enabled` إلى `$fillable` و `$casts`
- إضافة method `incrementViews()` لزيادة العداد
- إضافة accessor `getCompletedConsultationsCountAttribute()` — يحسب عدد الاستشارات المكتملة (`status = 'done'`)

---

### 3️⃣ نموذج OfficeRequest — تعديل

#### [MODIFY] [`OfficeRequest.php`](file:///c:/react_projects/amrtm_business/app/Models/Business/OfficeRequest.php)
- إضافة `consultation_type`, `video_meeting_url`, `data_retention_until` إلى `$fillable` و `$casts`
- إضافة `boot()` method مع `creating` event لتعيين `data_retention_until = now()->addYears(5)` تلقائياً

---

### 4️⃣ Controller — تعديل

#### [MODIFY] [`ServiceCatalogController.php`](file:///c:/react_projects/amrtm_business/app/Http/Controllers/UpdateService/ServiceCatalogController.php)

**`consultantsDirectory()`**:
- حساب **أكثر مستشار تفاعلاً** (الأكثر طلبات مكتملة `done`) → `$topConsultant`
- حساب إحصائيات عامة: `$totalConsultations` (عدد كل الاستشارات المُقدَّمة)، `$totalVisitors` (مجموع `views_count`)
- تمرير المتغيرات الجديدة للـ view

**`consultantDetail()`**:
- استدعاء `$office->incrementViews()` لزيادة عداد الزوار عند كل زيارة
- تحميل `withCount(['requests as completed_consultations_count' => fn($q) => $q->where('status', 'done')])`
- تحميل عدد الطلبات الإجمالي `requests_count`
- توليد QR Code URL عبر Google Charts API أو مكتبة JS (بدون حاجة لحزمة PHP إضافية)

---

### 5️⃣ الواجهة الأمامية — صفحة الدليل

#### [MODIFY] [`consultants_directory.blade.php`](file:///c:/react_projects/amrtm_business/resources/views/update_service/consultants_directory.blade.php)

**الإضافات:**

1. **بطاقة أكثر مستشار تفاعلاً (أعلى الصفحة)**:
   - بطاقة مميزة بتدرج ذهبي (`linear-gradient(135deg, #B8860B, #FFD700)`)
   - شارة "الأكثر تفاعلاً" + أيقونة `ti-crown`
   - عدد الاستشارات المكتملة + عدد الزوار
   - زر "عرض التفاصيل" يوجّه إلى صفحة المستشار
   - يظهر فقط إذا وُجد `$topConsultant`

2. **شريط إحصائيات عام**:
   - 4 بطاقات إحصائية (`cui-stat` cards):
     - عدد المستشارين المعتمدين
     - عدد المستشارين الموثقين
     - إجمالي الاستشارات المُقدَّمة
     - إجمالي الزوار

3. **شارة QR على كل بطاقة مستشار**:
   - أيقونة `ti-qrcode` صغيرة عند hover تفتح modal بالـ QR Code
   - الـ QR يحمل URL صفحة المستشار: `route('amrtm.consultants.detail', $office->id)`

4. **شارة استشارة فيديو**:
   - إذا `$office->video_consultation_enabled` → شارة `ti-video` خضراء "استشارة بالفيديو"

---

### 6️⃣ الواجهة الأمامية — صفحة التفاصيل

#### [MODIFY] [`consultant_detail.blade.php`](file:///c:/react_projects/amrtm_business/resources/views/update_service/consultant_detail.blade.php)

**الإضافات:**

1. **بطاقة QR Code**:
   - قسم جديد يعرض الـ QR Code الخاص بالمستشار (صورة SVG مُولَّدة بـ JS عبر مكتبة `qrcode.js` CDN)
   - زر "نسخ الرابط" + زر "تحميل QR"
   - يعرض `office_code` تحت الـ QR

2. **إحصائيات المستشار**:
   - عدد الزوار (`views_count`)
   - عدد الاستشارات المُقدَّمة (إجمالي الطلبات)
   - عدد الاستشارات المكتملة

3. **نوع الاستشارة في Modal الطلب**:
   - إضافة حقل اختيار نوع الاستشارة: `standard` (حضورية/نصية) أو `video` (فيديو)
   - يظهر خيار الفيديو فقط إذا `video_consultation_enabled = true`
   - عند اختيار فيديو → يُرسل `consultation_type: 'video'` في الـ request body

4. **ملاحظة حفظ البيانات**:
   - نص صغير أسفل Modal الطلب: "📋 يتم حفظ بيانات الاستشارة لمدة 5 سنوات وفقاً لسياسة المنصة"

---

### 7️⃣ QR Code — التنفيذ التقني

> [!NOTE]
> سنستخدم مكتبة JavaScript `qrcode.js` (CDN) لتوليد QR Code في المتصفح. لا حاجة لحزمة PHP.
> - الـ QR content = URL الصفحة العامة للمستشار: `{APP_URL}/consultants/{id}`
> - يُعرض كـ `<canvas>` يُحوَّل إلى صورة قابلة للتحميل

---

## Open Questions

> [!IMPORTANT]
> **استشارات الفيديو — التكامل الفعلي**:
> هل تريد تكاملاً فعلياً مع منصة فيديو (مثل Jitsi أو Zoom API)، أم يكفي حالياً إضافة الحقل والبنية التحتية فقط، بحيث يُدار رابط الفيديو يدوياً من لوحة تحكم المكتب؟ الخطة الحالية تُضيف البنية التحتية فقط.

> [!IMPORTANT]
> **عداد الزوار — الدقة**:
> هل تريد عداد بسيط (كل زيارة تُحسب حتى من نفس المستخدم)، أم تريد عداد فريد (unique visitors) يتطلب جدولاً إضافياً لتسجيل الزيارات الفريدة؟ الخطة الحالية تستخدم عداداً بسيطاً `views_count++`.

---

## Verification Plan

### Automated Tests
```bash
php artisan view:cache
npm run build
```

### Manual Verification
- زيارة `/consultants` والتحقق من:
  - ظهور بطاقة "الأكثر تفاعلاً" في الأعلى (إن وُجد)
  - شريط الإحصائيات يعرض أرقام حقيقية
  - شارة QR على كل بطاقة
  - شارة "استشارة بالفيديو" على المستشارين المُفعَّلين
- زيارة `/consultants/{id}` والتحقق من:
  - زيادة عداد الزوار
  - عرض QR Code صحيح + زر التحميل
  - إحصائيات المستشار
  - Modal الطلب يعرض خيار نوع الاستشارة
  - نص حفظ البيانات 5 سنوات
- تنفيذ الـ migration بنجاح

---

## ملخص الملفات المتأثرة

| الملف | العملية |
|-------|---------|
| `database/migrations/2026_09_06_100000_enhance_consultants_page.php` | **جديد** |
| `app/Models/Business/Office.php` | **تعديل** |
| `app/Models/Business/OfficeRequest.php` | **تعديل** |
| `app/Http/Controllers/UpdateService/ServiceCatalogController.php` | **تعديل** |
| `resources/views/update_service/consultants_directory.blade.php` | **تعديل** |
| `resources/views/update_service/consultant_detail.blade.php` | **تعديل** |
