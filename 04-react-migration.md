# 04 — خطة هجرة الواجهة إلى React

## الهدف

تحويل الواجهة الحالية (Blade) إلى **React frontend** مع بقاء **Laravel كـ API فقط**.

## لماذا الآن؟

هذه الوثيقة تُوثّق المسار حتى لا تتعطل الهجرة لاحقاً. الباك اند أصبح جاهزاً جزئياً عبر `/api/v1`.

## الاستراتيجية

### المرحلة 0 — تثبيت الباك اند
- [ ] إكمال توثيق كل endpoint في `docs/05-api.md`.
- [ ] توحيد غلاف الاستجابة `{isSuccess, value, error, statusCode}`.
- [ ] حماية كل endpoint حساس بالـ auth middleware.

### المرحلة 1 — النموذج الأولي (Pilot)
- إنشاء مدخل React (`/catalogue`) مستقل بجوار Laravel (Vite دعم multi-page).
- تصيير قائمة التصنيفات من `GET /api/v1/...` فقط.
- التحقق: نفس البيانات التي يراها Blade.

### المرحلة 2 — هجرة صفحة-بصفحة
- اعتماد Router (React Router) لمحاكاة مسارات Laravel.
- تحويل أقسام هوية الواجهة (Layout, navbar, footer, cards) لمكوّنات React.
- نقل `components/ui/*` لمكوّنات shadcn/ui.

### المرحلة 3 — الإيقاف التدريجي للـ Blade
- تعطيل view routes بعد تأكيد تكافؤ كل صفحة.
- تحويل الـ monoliths (index, admin_dashboard…) كأولوية أخيرة.

## قرارات معمارية مطلوبة

1. **Serving**: أحادي الأصل (Laravel يخدم `dist/` من React) أم منفصل؟
2. **State**: React Query للـ API + Zustand للحالة المحلية.
3. **Styling**: Tailwind (مشترك حالياً) + shadcn/ui كبديل Flowbite.
4. **i18n**: `react-i18next` بنفس مفاتيح `ar.json`/`en.json`.

## قواعد التحويل

- كل feature في المصدر يجب أن يتكافأ **1:1** في React قبل حذف Blade.
- لا endpoints جديدة داخل React — كل اتصال عبر `/api/v1`.
- الـ tokens والألوان (`#006C35`) تُنقل كمتغيّرات تصميم مشتركة.