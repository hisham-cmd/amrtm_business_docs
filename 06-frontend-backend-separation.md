# 06 — فصل الواجهة الأمامية عن الباك اند (تشغيل على سيرفرين منفصلين)

## ملخص

تم تأسيس الفصل الكامل:

| الطبقة | التقنية | الموقع | السيرفر |
|--------|---------|--------|---------|
| **الباك اند** | Laravel 12 + Sanctum (API خالص `/api/v1`) | جذر المشروع | سيرفر PHP (مثال: `api.amrtm.com`) |
| **الواجهة الأمامية** | React + Vite + Tailwind (SPA) | `frontend/` | سيرفر استاتيكي (مثال: `www.amrtm.com`) |

الواجهة الجديدة **مستقلة كلياً** عن Laravel: تُبنى بـ `npm run build` وتُرفع كملفات ثابتة، وتستهلك الـ API عبر `fetch`/`axios` مع `Authorization: Bearer <token>`.

---

## ما تم تنفيذه

### الباك اند (Laravel)
1. **Laravel Sanctum** مثبّت (`composer require laravel/sanctum`) مع migration لجدول `personal_access_tokens` على اتصال `business`.
2. **`HasApiTokens`** مضافة إلى `BusinessUser` و `OfficeUser`.
3. **غاردات توكنية** في `config/auth.php`: `business_token` و `office_token` (driver: sanctum).
4. **`config/cors.php`** جديد — يقرأ `CORS_ALLOWED_ORIGINS` أو `FRONTEND_URL` من `.env`.
5. **`AuthenticateApi` / `OfficeAuthMiddleware`** أصبحتا تفحصان `Bearer token` أولاً ثم الجلسة (توافق مع الواجهة القديمة).
6. **Endpoints مصادقة توكنية**:
   - `POST /api/v1/auth/login` → `{ token, user }`
   - `GET /api/v1/auth/me` → بيانات المستخدم الحالي
   - `POST /api/v1/auth/logout` → إلغاء التوكن
7. **Endpoints عامة جديدة**:
   - `GET /api/v1/consultants` — دليل المستشارين (JSON)
   - `GET /api/v1/consultant-specialties` — بطاقات التخصصات

### الواجهة الأمامية (`frontend/`)
- React 18 + React Router 6 + Vite 7 + Tailwind v4 (بنفس `--color-primary: #006C35`).
- `src/api/client.js` — axios مع حقن التوكن تلقائياً + معالجة 401.
- `src/context/AuthContext.jsx` — تسجيل دخول/خروج/مستخدم حالي (localStorage).
- صفحات: الرئيسية، الخدمات، المستشارون، تسجيل الدخول، لوحة التحكم.
- اتجاه RTL وخط Cairo.

---

## التشغيل المحلي (تطوير)

### الطرفية 1 — الباك اند
```bash
php artisan serve --port=8000
```

### الطرفية 2 — الواجهة
```bash
cd frontend
npm install
npm run dev        # http://localhost:5173
```

> أثناء التطوير، `vite.config.js` يمرر `/api/*` إلى `http://127.0.0.1:8000`
> عبر proxy (لا حاجة لضبط CORS محلياً).

---

## النشر على سيرفرين منفصلين

### 1) الباك اند (سيرفر PHP)
على سيرفر الباك اند ضع متغيرات `.env`:

```dotenv
APP_URL=https://api.amrtm.com
# اسمح للواجهة الأمامية بالوصول
FRONTEND_URL=https://www.amrtm.com
CORS_ALLOWED_ORIGINS=https://www.amrtm.com
CORS_SUPPORTS_CREDENTIALS=false
```

- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan config:clear`
- وجّه الجذر العام إلى `public/` (Nginx/Apache).
- خدّم `api.amrtm.com` على هذا السيرفر.

### 2) الواجهة الأمامية (سيرفر استاتيكي — Nginx/Netlify/Vercel)
```bash
cd frontend
echo "VITE_API_URL=https://api.amrtm.com/api/v1" > .env.production
npm run build
```
ارفع محتوى `frontend/dist/` إلى سيرفر الواجهة (أو CDN).

على Nginx أضف إعادة كتابة لكل المسارات إلى `index.html` (SPA):

```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

### 3) تطبيق Nginx الكامل للواجهة (مثال)

```nginx
server {
    listen 80;
    server_name www.amrtm.com;

    root /var/www/amrtm-frontend;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api/ {
        proxy_pass https://api.amrtm.com;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## تدفق المصادقة بين السيرفرين

```
المتصفح → www.amrtm.com (React SPA)
    POST /api/v1/auth/login  →  api.amrtm.com
    ← { token: "1|xxxx", user: {...} }

المتصفح → localStorage.amrtm_token
    GET /api/v1/requests  (Header: Authorization: Bearer <token>)
    ← بيانات المستخدم/الطلبات
```

- التوكن يُحفظ في `localStorage` و`axios` يرفقه تلقائياً.
- عند `401` يُمسح التوكن ويعاد التوجيه لتسجيل الدخول.

---

## ملاحظات أمان مهمة

1. **HTTPS إلزامي** في الإنتاج — أي توكن يُرسل عبر اتصال مشفر فقط.
2. إذا أردت منع الوصول للواجهة القديمة (Blade) نهائياً لاحقاً:
   - علّق مسارات `routes/web.php` الخاصة بالصفحات، أو
   - اجعل `config/auth.php` default guard = `business_token`.
3. صلاحيات التوكن: `createToken('api-token', ['business'])` — جرّب تقييدها حسب الحاجة.

---

## الخطوات التالية (هجرة تدريجية)

- [ ] استهلاك باقي الـ API في الواجهة (طلبات، إشعارات، ملف شخصي، مكاتب).
- [ ] نقل مكونات `components/ui/*` إلى shadcn/ui في React.
- [ ] i18n عبر `react-i18next` بنفس مفاتيح `ar.json`/`en.json`.
- [ ] عند اكتمال تكافؤ كل صفحة (1:1) → تعطيل مسارات Blade.