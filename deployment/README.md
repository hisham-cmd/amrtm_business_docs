# النشر إلى سيرفر الإنتاج

أدوات وتوثيق نشر الباك اند إلى استضافة FTP. **هذا المجلد لا يحتوي
أي بيانات اتصال حقيقية** — القوالب فقط.

---

## البدء السريع

```bash
# 1) انسخ القالب واملأه
copy ftp-credentials.example.cfg ftp-credentials.cfg

# 2) جرّب بدون رفع
deploy-ftp.bat -DryRun

# 3) انشر
deploy-ftp.bat
```

المتطلبات: لا شيء يُثبَّت. الأداة تستعمل `curl.exe` الموجود في ويندوز.

---

## الملفات

| الملف | الوصف |
|---|---|
| `deploy-ftp.bat` | نقطة الدخول — انقر نقراً مزدوجاً |
| `deploy-ftp.ps1` | المحرّك |
| `ftp-credentials.example.cfg` | **قالب** بيانات الاتصال |
| `production-db.example.cfg` | **قالب** إعدادات الإنتاج |
| `prod-db.example.php` | أداة استعلام قاعدة الإنتاج (مؤقتة) |
| `global-gitignore.example.txt` | حماية Git على مستوى الجهاز |
| `DEPLOY-README.md` | الدليل التفصيلي |
| `SECURITY.md` | **اقرأه قبل أي نشر** |

---

## الأوامر

| الأمر | الوظيفة |
|---|---|
| `deploy-ftp.bat` | نشر تزايدي — يرفع الجديد والمتغيّر فقط |
| `deploy-ftp.bat -DryRun` | عرض ما سيُرفع فقط، بلا أي رفع |
| `deploy-ftp.bat -Full` | رفع كل الملفات |
| `deploy-ftp.bat -Force` | رفع الكل وتجاهل ملف الحالة |
| `deploy-ftp.bat -SkipVendor` | بدون مجلد `vendor` |
| `deploy-ftp.bat -NoCache` | بدون تنظيف كاش لارافيل |
| `deploy-ftp.bat -help` | المساعدة |

---

## كيف يعمل

| المرحلة | التفصيل |
|---|---|
| 1. الإعدادات | يقرأ `ftp-credentials.cfg` |
| 2. فحص الاتصال | يتأكد من الوصول قبل أي رفع |
| 3. تنظيف الكاش | `artisan config/route/view/cache/optimize:clear` |
| 4. المقارنة | بصمة `SHA-1` لكل ملف مقابل `.ftp-deploy-state.tsv` |
| 5. الرفع | دفعات 40 ملفاً في اتصال واحد، مع إعادة محاولة |

**النشر التزايدي:** بعد أول نشر، التالي يرفع المتغيّر فقط — دقائق بدل ساعة.

---

## الأداء

| القياس | القيمة |
|---|---|
| طريقة الرفع | ملف إعدادات `curl` (عملية واحدة لكل دفعة) |
| الزمن لكل ملف | **351 مللي** |
| بدون تحسين (process لكل ملف) | 1255 مللي |
| السرعة | **3.6×** أسرع |
| أول نشر كامل | 60 – 90 دقيقة (≈10,300 ملف / 336MB) |
| نشر تزايدي | ثوانٍ |

الأسماء العربية مدعومة: المسار البعيد مُرمَّز بنسبة مئوية، والملف
المحلي يُكتب بترميز UTF-8.

---

## ما لا يُرفع أبداً

| المستثنى | السبب |
|---|---|
| `.env` | السيرفر له إعدادات إنتاج خاصة به |
| `app.yaml`, `render.yaml` | تحوي `APP_KEY` وكلمات مرور |
| `node_modules/`, `.git/`, `dist/`, `rendered/` | غير مطلوبة وقت التشغيل |
| `storage/logs/*`, `storage/framework/*` | كاش وسجلات — لارافيل ينشئها |
| `check_creds.py`, `check_db.php`, `boot_test.php` | سكربتات تكشف بيانات الاتصال |
| `*.zip`, `*.log`, `*.bak`, `*.traineddata` | ثقيلة أو مؤقتة |
| `deploy-ftp.*`, `ftp-credentials.cfg` | أدوات النشر نفسها |

**استثناء مهم:** ملفات `.gitignore` داخل `storage/framework/*` و
`storage/logs` **تُرفع** — هي ما يجعل هذه المجلدات موجودة على السيرفر.
بدونها يفشل لارافيل بـ `Please provide a valid cache path`.

---

## حدود الاستضافة (InfinityFree)

| الميزة | التوضيح |
|---|---|
| **FTP فقط** | `sql*.infinityfree.com` لا يُحل خارج شبكة الاستضافة |
| **لا حذف عبر FTP** | `DELE` و `RMD` و `QUOT` ترد `550` |
| **لا SSH** | لا يمكن تشغيل `composer install` — `vendor` يُرفع كاملاً |
| **نطاق محدود** | السيرفر يردّ `230-Your bandwidth usage is restricted` |
| **60 ثانية خمول** | انقطاع الاتصال أثناء الرفع الطويل |

### انتبه للمسار

جذر FTP محصور في مجلد حسابك. المسار الكامل:

```
/home/vol9_x/infinityfree.com/<user>/htdocs/backend
```

اكتب في `FTP_ROOT` فقط:

```
htdocs/backend
```

بلا شرطة مائلة في البداية.

---

## استكشاف الأخطاء

| المشكلة | الحل |
|---|---|
| `فشل الاتصال بالمضيف` | راجع `ftp-credentials.cfg` |
| `لا يوجد ملف حالة` | طبيعي في أول نشر |
| `لا يوجد artisan في ...` | صحّح `LOCAL_PROJECT` |
| فشل بعض الملفات | `deploy-logs\deploy-*.log` |
| `URL using bad/illegal format` | اسم ملف غير ASCII — الأداة تترجمه، تأكد أن `deploy-ftp.ps1` محدَّث |

---

## راجع أيضاً

- [`SECURITY.md`](SECURITY.md) — سياسة الأسرار، **اقرأها أولاً**
- [`DEPLOY-README.md`](DEPLOY-README.md) — الدليل التفصيلي
