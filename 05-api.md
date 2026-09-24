# 05 — واجهة API (`/api/v1`)

> العقد الموحّد الذي تستهلكه واجهة Blade (عبر `fetch`) وواجهة React المستقبلية.

## غلاف الاستجابة الموحّد

```json
{
  "isSuccess": true,
  "value": {},
  "error": null,
  "statusCode": 200
}
```

- `isSuccess`: نجاح الطلب.
- `value`: البيانات (كائن/مصفوفة).
- `error`: رسالة الخطأ (null عند النجاح).
- `statusCode`: كود HTTP.

**المصادقة**: حالياً `session-based` للـ guard `business` و`office`.
**التحويل لـ SPA مستقبل**: أضف Laravel Sanctum (راجع `docs/04-react-migration.md`).

## المصادقة/الشفرة

- `auth.api:business` → `AuthenticateApi` (401 JSON عند عدم التوثيق، لا redirect لصفحة ويب).
- `no-admin` → يحظر العمليات الخاصة بالمستخدم العادي عن الأدمن.
- `business-role:admin,supervisor` + `audit-admin` → عمليات الأدمن/المشرف.
- `auth.office` + `complete.office.profile` → مكاتب القطاع المهني.

---

## A) المجال العام (لا مصادقة)

| الطريقة | المسار | الوصف |
|--------|--------|-------|
| GET | `/api/v1/services` | الخدمات العامة (4 تصنيفات) |
| GET | `/api/v1/office-types` | أنواع المكاتب |

---

## B) المستخدم العادي (محمي `auth.api:business` + `no-admin`)

| الطريقة | المسار | الوصف |
|--------|--------|-------|
| POST | `/api/v1/requests` | إرسال طلب خدمة |
| POST | `/api/v1/payments/charge` | شحن الرصيد |
| POST | `/api/v1/office-requests` | إرسال طلب مكتب |

## C) المستخدم (محمي `auth.api:business`)

| الطريقة | المسار | الوصف |
|--------|--------|-------|
| GET | `/api/v1/requests` | طلباتي |
| GET | `/api/v1/requests/{id}` | تفاصيل طلب |
| GET | `/api/v1/dashboard/user` | إحصاءات لوحة المستخدم |
| GET | `/api/v1/payments/history` | سجل المدفوعات |
| PUT | `/api/v1/profile` | تحديث الملف الشخصي |
| PUT | `/api/v1/profile/password` | تغيير كلمة المرور |
| GET | `/api/v1/notifications` | الإشعارات |
| GET | `/api/v1/notifications/unread-count` | عدد غير المقروء |
| POST | `/api/v1/notifications/{id}/read` | تحديد كقراءة |
| POST | `/api/v1/notifications/read-all` | قراءة الكل |

---

## D) الأدمن/المشرف (محمي إضافياً `business-role` + `audit-admin`)

### لوحة + طلبات
| الطريقة | المسار | الوصف |
|--------|--------|-------|
| GET | `/api/v1/dashboard/admin` | إحصاءات الأدمن |
| GET | `/api/v1/admin/requests` | كل الطلبات |
| PUT | `/api/v1/admin/requests/{id}/status` | تحديث حالة طلب |
| POST | `/api/v1/admin/requests/{id}/note` | إضافة ملاحظة |
| POST | `/api/v1/admin/requests/{id}/info` | طلب معلومات |

### الخدمات/الأسعار
| PUT | `/api/v1/admin/services/{id}/price` | تحديث السعر |
| PUT | `/api/v1/admin/services/{id}` | تحديث خدمة |

### المدفوعات
| GET | `/api/v1/admin/payments` | المعاملات |

### الكتالوج — تصنيفات
| GET/POST | `/api/v1/admin/catalog/categories` | قائمة/إنشاء |
| PUT/DELETE | `/api/v1/admin/catalog/categories/{id}` | تحديث/حذف |

### الكتالوج — كيانات
| GET/POST | `/api/v1/admin/catalog/entities` | قائمة/إنشاء |
| PUT/DELETE | `/api/v1/admin/catalog/entities/{id}` | تحديث/حذف |

### الكتالوج — خدمات حكومية
| GET/POST | `/api/v1/admin/catalog/services` | قائمة/إنشاء |
| DELETE | `/api/v1/admin/catalog/services/{id}` | حذف |

### المكاتب
| GET | `/api/v1/admin/offices` | المكاتب |
| GET | `/api/v1/admin/offices/stats` | إحصاءات المكاتب |
| GET | `/api/v1/admin/offices/{id}/details` | تفاصيل مكتب |
| POST | `/api/v1/admin/offices/{id}/verify` | توثيق مكتب |
| POST | `/api/v1/admin/offices/{id}/toggle` | تفعيل/تعطيل |
| DELETE | `/api/v1/admin/offices/{id}` | حذف |

### المستخدمون
| GET | `/api/v1/admin/users` | المستخدمون |
| GET | `/api/v1/admin/users/stats` | إحصاءات المستخدمين |
| POST | `/api/v1/admin/users/{id}/toggle` | تفعيل/تعطيل |
| POST | `/api/v1/admin/users/{id}/balance` | تعديل الرصيد |

### نشاط/تحليلات
| GET | `/api/v1/admin/logs` | سجل النشاط |
| GET | `/api/v1/admin/analytics` | التحليلات |

---

## E) المكاتب (محمي `auth.office` + `complete.office.profile`) — `/api/v1/office`

| الطريقة | المسار | الوصف |
|--------|--------|-------|
| GET | `/api/v1/office/stats` | إحصاءات المكتب |
| GET | `/api/v1/office/requests` | طلبات المكتب |
| GET | `/api/v1/office/requests/{id}` | تفاصيل طلب |
| PUT | `/api/v1/office/requests/{id}/status` | تحديث حالة |
| GET | `/api/v1/office/requests/{id}/messages` | الرسائل |
| POST | `/api/v1/office/requests/{id}/messages` | إرسال رسالة |
| GET | `/api/v1/office/services` | خدمات المكتب |
| POST | `/api/v1/office/services` | إنشاء خدمة |
| PUT | `/api/v1/office/services/{id}` | تحديث خدمة |
| DELETE | `/api/v1/office/services/{id}` | حذف خدمة |
| GET | `/api/v1/office/direct-requests` | «الاستشارات» المباشرة — للمنشأة **الاستشارية** فقط؛ لا يُستخدم لمكتب مساند (المساند يستقبل بالإسناد فقط) |
| PUT | `/api/v1/office/direct-requests/{id}/status` | تحديث حالة استشارة مباشرة — للاستشاري فقط |
| GET | `/api/v1/office/notifications` | الإشعارات |
| GET | `/api/v1/office/financial` | البيانات المالية |

---

## أمثلة

### 401 غير موثّق
```
GET /api/v1/requests
→ 401
{
  "isSuccess": false,
  "value": null,
  "error": "Unuthenticated.",
  "statusCode": 401
}
```

### نجاح (عام)
```
GET /api/v1/services
→ 200 { "isSuccess": true, "value": [ ... ], "error": null, "statusCode": 200 }
```