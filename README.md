# افزونه MSR Report Receiver

این افزونه برای سایت‌های مقصد طراحی شده تا بتوانند رپورتاژهای خبری را از یک سایت مبدا به‌صورت مرحله‌ای، ایمن و ساخت‌یافته دریافت و منتشر کنند.

---

## ✳️ امکانات اصلی

- احراز هویت ایمن با استفاده از JWT
- ثبت رپورتاژ مرحله به مرحله:
  1. عنوان و محتوا
  2. تصویر شاخص
  3. دسته‌بندی‌ها
  4. برچسب‌ها
  5. اطلاعات سئو (Yoast SEO)
- ثبت لاگ برای هر مرحله در دیتابیس
- داشبورد مدیریتی در پیشخوان وردپرس
- تنظیم نویسنده پیش‌فرض برای پست‌ها
- امکان ارسال مجدد توکن JWT به سایت مبدا

---

## 🧩 مسیرهای API

| متد | مسیر | توضیح |
|------|------|--------|
| POST | `/wp-json/msr/v1/report` | ایجاد پست با عنوان و محتوا |
| PUT | `/wp-json/msr/v1/report/{id}/image` | افزودن تصویر شاخص |
| PUT | `/wp-json/msr/v1/report/{id}/category` | افزودن دسته‌بندی‌ها |
| PUT | `/wp-json/msr/v1/report/{id}/tags` | افزودن برچسب‌ها |
| PUT | `/wp-json/msr/v1/report/{id}/seo` | افزودن اطلاعات سئو |
| GET | `/wp-json/msr/v1/report/{id}` | دریافت اطلاعات پست |
| DELETE | `/wp-json/msr/v1/report/{id}` | حذف پست |

تمام درخواست‌ها باید دارای هدر JWT معتبر باشند:


# MSR Report Receiver Plugin

This WordPress plugin is designed for **target websites** to securely and incrementally receive press release (reportage) posts from a central sender site.

---

## ✳️ Main Features

- Secure JWT-based authentication
- Step-by-step post registration:
  1. Title and content
  2. Featured image
  3. Categories
  4. Tags
  5. Yoast SEO metadata
- Internal logging per step
- Admin dashboard in WordPress
- Default author selector
- Manual JWT resend for registration

---

## 🧩 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/wp-json/msr/v1/report`              | Create post with title and content |
| PUT    | `/wp-json/msr/v1/report/{id}/image`   | Set featured image |
| PUT    | `/wp-json/msr/v1/report/{id}/category`| Set categories |
| PUT    | `/wp-json/msr/v1/report/{id}/tags`    | Set tags |
| PUT    | `/wp-json/msr/v1/report/{id}/seo`     | Set Yoast SEO metadata |
| GET    | `/wp-json/msr/v1/report/{id}`         | Retrieve post info |
| DELETE | `/wp-json/msr/v1/report/{id}`         | Delete post |

All requests must include a valid JWT token in the headers:

