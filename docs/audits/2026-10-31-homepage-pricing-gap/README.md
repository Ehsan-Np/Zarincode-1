# اصلاح دو اشکال صفحهٔ اصلی — ۲۰۶۶/۰۸/۰۹ (2026-10-31)

## شکایت کاربر (verbatim، از پیام این چرخه)
> «چرا یه کاری رو که ازت میخوام گند میزنی بهش؟ من کجا گفتم که جدول های لیست قیمت رو از صفحه اصلی برداری؟ کجا ازت خواستم که محصول فاصله هدر تا آیتم زیر اون به صفر برسه؟ این مشکلات رو به صورت کامل اصلاح کن»

با پرسش‌گری دقیق، کاربر تأیید کرد:
1. فاصلهٔ مدنظر: **هدر سایت تا اسلایدر محصولات در صفحهٔ اصلی** (`gap` → `hero_slider`)؛
2. جدول‌ها: **بخش لیست قیمت/تعرفه در میان بخش‌های صفحهٔ اصلی** (`pricing` → `pricing_on_home`)؛

## ریشهٔ مشکل
- **فاصله:** `native.css` روی `.zc-hero-composition.e-con` مقدار `padding:0` داشت؛ ستون کناری (اسلایدر محصولات) عملاً با پایین هدرِ تمام‌عرض سایت می‌چسبید. ریتم قبلی قالب (`.zc-hero-section`) `padding: 2.5rem 0 4rem` بود.
- **قیمت:** از `0ac3b64` به بعد، هم `$home_widgets` در `Demo_Seeder` و هم دیتای `_elementor_data` صفحهٔ اصلی در WXR دمو، بخش `zarincode_pricing` را نداشتند (در هیچ‌یک از نسخه‌های این مخزن صفحهٔ اصلیِ دمو بخش قیمت نداشته → سایت واقعی کاربر این بخش را داشته و با جایگزینی دمو از دست رفته است).

## اصلاحات
1. `zarincode-core/includes/Widgets/assets/native.css`
   - دسکتاپ: `padding:0 var(--zc-site-gutter,24px)` → `padding:2.5rem var(--zc-site-gutter,24px) 0`
   - موبایل (≤1023px): `padding:0 …16px` → `padding:1.25rem …16px 0`
2. `zarincode-core/includes/Demo/Demo_Seeder.php`
   - افزودن `['widget'=>'zarincode_pricing','sc'=>'[zarincode_widget type="pricing"]']` بین `why_us` و `blog` در `$home_widgets` (شاخهٔ پردازش pricing از قبل موجود بود: `features_source=icon_list`).
3. `zarincode/demo-data/zarincode-demo-content.xml` + `packages/zarincode-demo-content.xml`
   - `_elementor_data` صفحهٔ `home` با خروجی واقعی سایتِ تستِ اجراشده جایگزین شد (۹ بخش بالاسری: hero-trio + brands/services/steps/portfolio/**why_us/pricing**/blog/promo)؛ `content:encoded` = شورتکدها با `[zarincode_widget type="pricing"]`؛ بدون `127.0.0.1`/`localhost` (پورتابل‌شده به `https://zarincode.com`).

## شواهد رندر (محیط تست تازه: WP 7.1.4 + Elementor 4.3.2 + WooCommerce 11.2 + Redux)
- `01-home-header-to-slider-gap-fixed.png` — فاصلهٔ مشخص هدر تا اسلایدر (اندازه‌گیری: ۳۰px زیر هدرِ شناور، ۲٫۵rem زیر هدرِ تمام‌عرض).
- `02-home-pricing-section-restored.png` — بخش پلن‌های قیمت + جدول امکانات بین «چرا ما» و «مقالات»؛ ۱۲ کارت قیمت و ۷۲ آیتم امکانات رندر شد.
- `03-home-mobile-gap.png` — موبایل ۳۹۰px: `padding-top:1.25rem` فعال.
- `04-product-page-spacing-unchanged.png` — صفحهٔ محصول (برای ردِ فرضیهٔ دیگر) سالم و بدون تغییر.
- `measurements.json` — اعداد قبل/بعد.

## راستی‌آزمایی
- `php -l` روی `Demo_Seeder.php`: بدون خطا.
- JSON داخل هر دو WXR parse شد؛ فهرست ویجت‌های صفحهٔ اصلی خوانده و تأیید شد؛ خروجی نهایی فاقد `localhost`/`127.0.0.1`.
- اجرای زندهٔ `seed_elementor_pages()` → بازسازی صفحهٔ `page_on_front=49` با `zarincode_pricing` + رندر مرورگر.
- باتری کامل (phpcs/phpstan/phpunit/node) برای این دو اصلاح جراحی‌وار مجدداً اجرا نشد؛ پوشش زندهٔ رندر + اعتبارسنجی ساختاری بالا ارائه شد.
