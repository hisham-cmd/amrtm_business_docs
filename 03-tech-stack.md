# 03 — التقنيات المستخدمة (Tech Stack)

| الطبقة | التقنية | النسخة/ملاحظات |
|-------|----------|----------------|
| Backend | Laravel | PHP |
| Frontend | Blade + Tailwind CSS v4 | Tailwind v4 |
| UI Library | **Flowbite** | 4.0.2 (متوافق Tailwind v4) |
| Build | Vite | `laravel-vite-plugin` + `@tailwindcss/vite` |
| Fonts | Cairo + Inter | Google Fonts |
| Icons | Tabler Icons + Font Awesome | CDN |
| Database | SQL (Eloquent) | — |
| API | Laravel API | `/api/v1` |
| Testing | PHPUnit + **Playwright** | Playwright للواجهة |

> **ملاحظة**: أُزيل **Vue** (و`@vitejs/plugin-vue`) من المشروع — كان كوداً يتيماً لا تستهلكه أي صفحة. الحزم المعترضة الآن `import 'flowbite'` فقط في `app.js`.

## Flowbite Integration (موثّق)

في `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "flowbite/plugin";
@source "../node_modules/flowbite";
```

وفي `resources/js/app.js`:

```js
import 'flowbite';
```

## Design Tokens (`@theme`)

- `--color-primary: #006C35` (اللون السعودي الرسمي — أخضر).

## RTL

- الصفحات `dir="rtl"` الافتراضي.
- لترجي الخط والمكونات الحساسة للاتجاه (`Switch`، الأرقام، العملة) يُفعَّل `dir="ltr"` محلياً.

## قواعد UI/UX الصارمة

- لا عناصر HTML أصلية عند وجود مكوّن Flowbite (`<Input>`, `<Button>`, `<Select>`…).
- لا `<textarea>` نقي — استبدل بـ `RichTextEditor`.
- الحقول enumerates → `<Select>` أو toggle chips.
- المتغيرات في قوالب الرسائل عبر أزرار إدراج `{{name}}` إلخ.
- الرموز السرية تُدار بزر عين `Eye/EyeOff`.
- مكان الـ placeholder وصفي بالعربية.