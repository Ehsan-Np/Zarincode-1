# ارَتا/۴٫۰٫۰-rc.2 — اصلاح دو اشکال صفحهٔ اصلی (۲۰۲۶-۱۰-۳۱)

## بحرانیت
شکایت مستقیم کاربر: حذف ناخواستهٔ «جدول‌های لیست قیمت» از صفحهٔ اصلی + صفر شدن فاصلهٔ هدر تا
اسلایدر محصولات. هر دو مورد تأیید و رفع شد؛ بدون دست زدن به درخواست‌های قبلی (اسلایدر، سرعت،
آیکون‌ها، مقالات، سئو).

## تغییرات سورس
| فایل | تغییر |
|---|---|
| `zarincode-core/includes/Widgets/assets/native.css` | `padding:0` روی `.zc-hero-composition.e-con` → `2.5rem … 0` (دسکتاپ، هم‌ریتم با `.zc-hero-section` قدیمی) و `1.25rem … 0` (موبایل ≤۱۰۲۳px) |
| `zarincode-core/includes/Demo/Demo_Seeder.php` | افزودن `zarincode_pricing` به `$home_widgets` میان `why_us` و `blog` (شورتکد `[zarincode_widget type="pricing"]`؛ شاخهٔ `features_source=icon_list` از قبل موجود) |
| `zarincode/demo-data/zarincode-demo-content.xml` | جایگزینی `_elementor_data` صفحهٔ `home` با خروجی واقعی سایت تستِ اجرای seeder (۹ بخش بالاسری، حاوی `zarincode_pricing`) + به‌روزرسانی `content:encoded` شورتکدها |
| `packages/zarincode-demo-content.xml` | همان به‌روزرسانی (کپی بایگانی‌شده در بسته‌ها) |

ترتیب نهایی صفحهٔ اصلی:
`hero(HERO-TRIO: intro + products_slider + cta) → brands → services → steps → portfolio → why_us → pricing → blog → promo`

## راستی‌آزمایی (محیط تست تازه: WP 7.1.4، Elementor 4.3.2، WooCommerce 11.2، Redux)
- بازسازی کامل از صفر + اجرای `Demo_Seeder::seed_elementor_pages()`؛ `php -l` بدون خطا؛
- رندر مرورگر (Playwright/Chromium): اسلایدر ۳۰px پایین‌تر از هدرِ شناور (و ۲٫۵rem زیر هدرِ تمام‌عرض)،
  بخش قیمت با ۱۲ کارت و ۷۲ آیتم امکانات بین «چرا ما» و «مقالات»؛
- اعتبارسنجی هر دو WXR: parse موفق JSON، فهرست ویجت‌ها تأیید، نبود `localhost`/`127.0.0.1`؛
- شواهد تصویری: `docs/audits/2026-10-31-homepage-pricing-gap/` (۴ اسکرین‌شات + `measurements.json`).
- باتری کامل کیفی مجدد برای این دو اصلاح جراحی‌وار اجرا نشد (اعلام صادقانه).

## یادداشت برای سایت موجود کاربر
پس از جایگزینی این نسخهٔ قالب، با **اجرای مجدد درون‌ریزی دمو** (یا افزودن دستی ویجت
`zarincode_pricing` در المنتور میان «چرا ما» و «مقالات») بخش قیمت به صفحهٔ اصلی برمی‌گردد؛
اصلاح فاصله با نصب همین نسخهٔ قالب به‌صورت خودکار اعمال می‌شود (نیازی به درون‌ریزی ندارد).

## وضعیت دروازه‌ها
`docs/governance/release-gates.json`: `commercial_approved=false` (تأیید انسانی همچنان باقی است؛
این ارَتا صرفاً اصلاحیهٔ rc.2 است و نسخهٔ نهایی اعلام نمی‌شود).
