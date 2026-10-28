# گزارش چرخه نهایی — شش درخواست کاربر (۲۰۲۶-۱۰-۲۸)

- تاریخ: ۲۰۲۶-۱۰-۲۸ (Asia/Tehran) — پایه کد: `8b89465` (origin/main) + این چرخه
- محیط: PHP 8.4.27، WordPress 7.1.3، MariaDB 11.8.6، نصب یکبارمصرف `/tmp/zc-fix` (المنتور 4.3.2 + پرو 4.3.0 + Redux 4.5.15 + ووکامرس ۱۱٫۲٫۰)
- شواهد: `probes.json` (کاوشگرهای صفحات)، `battery.txt` (باتری کیفیت)، `article-import-counts.json`، `ocdi-files.json`

## وضعیت شش درخواست

| # | درخواست | نتیجه | شاهد کلیدی |
|---|---------|-------|-------------|
| ۱ | حذف کامل ویجت بنیان‌گذار/تایمر «Crextio Studio Tracker» و جایگزینی اسلایدر محصولات حرفه‌ای | ✅ | `hero_profile`=۰، `zc-live-timer`=۰، «Crextio Studio Tracker»=۰، اسلایدر با ۶ اسلاید/ترک/نقش carousel در هیرو؛ کارت‌های تیره خارج از هیرو (رفتار مصوب قبلی) |
| ۲ | افزایش چشمگیر سرعت بارگذاری | ✅ | پیش‌بارگذاری فونت ۳، Font Awesome بدون CSS مسدود (preload+swap و fallback در noscript)، ۶ اسکریپت defer (شامل `zarincode-main.js`)، کاهش حجم هیرو/حذف ویجت سنگین؛ صفحه خانه ۲۰۰ در ۰٫۲۹s گرم |
| ۳ | بازطراحی آیکون‌های لیست قیمت با کلاس `zc-feature-icon` | ✅ | ۳۶/۳۶ نشان در `/pricing/`؛ قواعد chip در `assets/css/zarincode-main.css` زنده است (سبک تخت، رنگ آیتم/هورکارت/پلن محبوب، `aria-hidden` روی هر دو خروجی‌دهنده) |
| ۴ | محتوای جامع، جذاب و سئوشده بخش خدمات | ✅ | `/services/`: تک `<h1>` کلیدواژه‌ای «خدمات حرفه‌ای طراحی سایت، سئو و توسعه نرم‌افزار» + معرفی `<strong>` ۶۳۵ نویسه‌ای؛ ۶ CPT خدمت بازسازی‌شده (فرایند/چرایی با خط امنیت ۹۹٪/پرسش‌متداول/پیوندها)؛ اسناد صفحه در حالت ویرایشگر/سفارشی |
| ۵ | مقالات قابل درون‌ریزی از خود قالب با سهمیه‌های دقیق | ✅ | ۷۱ مقاله: طراحی وب ۱۰ / سئو ۱۰ / تولید محتوا ۵ / آموزش وردپرس ۱۲ (همه بخش‌ها) / امنیت ۲۲ (≥۲۰، هرکدام با «امنیت تا سقف ۹۹٪»؛ مجموع نشان در XML=۲۲) / سرعت و سئو ۱۲ (≥۱۰)؛ ترجمه اختصاصی از منابع خارجی اولیه (سرعنوان `build_articles_data.py`)؛ درون‌ریزی از مسیر دمو = ورودی دوم `ocdi_import_files()` بدون Redux (`ocdi-files.json`)؛ ورود موفق `--authors=create`، تاریخ‌ها همه گذشته (۰ آینده)، شش دسته با شمارش دقیق (`article-import-counts.json`)، کاور چرخشی برای ۷۱/۷۱، وبلاگ ۸ کارت + صفحه‌بندی آرشیو |
| ۶ | ممیزی دقیق سئو و اسکیما | ✅ | گزارش تخصصی: `docs/audits/2026-10-28-seo-schema-audit/README.md`؛ کد جدید `inc/Core/Seo_Meta.php` (توضیح/canonical آرشیو/robots/OG/Twitter/WebSite+SearchAction + Sitemap در robots.txt) و ارتقای BlogPosting (publisher/inLanguage) |

## باتری کیفیت (متن کامل: `battery.txt`)

| بررسی | نتیجه |
|-------|-------|
| `php -l` کل درخت (۸۳ فایل) | ۰ خطا |
| WPCS (`composer standards`) | ۰ نقض (exit 0) |
| PHPStan سطح ۱۰ (`composer analyse`) | No errors |
| PHPUnit | OK — ۳۷ تست / ۲۰۹ assertion |
| پوشش خطی سرویس‌های حیاتی | ۸۸٫۴۸٪ (507/573) ≥ کف ۸۰ |
| قرارداد حسابرسی ویجت (node) | ۶/۶ |
| آداپترهای چندزبانه (none/polylang) | ۶/۶ + ۶/۶ |
| واحد Skill-3 | passed (۹۴ assertion) |
| قواعد معماری | exit 0 |
| نگهبان‌های بسته‌بندی | ۶/۶ OK |
| راستی‌آزمایی JSON/XML/JS | ۴ JSON + ۳ XML + JS سالم |

## نحوه بازتولید

```bash
composer install --working-dir=tools/quality
composer --working-dir=tools/quality standards && composer --working-dir=tools/quality analyse
ZARINCODE_QA_WP=/tmp/zc-fixture XDEBUG_MODE=coverage tools/quality/vendor/bin/phpunit \
  -c tools/quality/phpunit.xml --coverage-clover /tmp/zc-coverage.xml
python3 tools/quality/check-coverage.py /tmp/zc-coverage.xml 80
node --test tools/widget-audit-contract.test.cjs
for m in none polylang; do php tools/qa-multilingual.php "$m"; done
python3 tools/qa-skill-v3-architecture.py && python3 tools/test-build-release.py
```

## محدودیت‌های صادقانه

- `commercial_approved=false` و `human_release_approval=pending` (طبق `docs/governance/release-gates.json`)؛ هیچ ادعای تأیید مارکت‌پلیس یا سازگاری WPML داده نمی‌شود.
- تست مرورگری (Playwright/axe) این چرخه اجرا نشد؛ آخرین رکورد ممیزی ویجت مربوط به ۲۰۲۶-۱۰-۲۷ است.
- شواهد صفحات HTML در محیط یکبارمصرف `/tmp/zc-evidence/` نگهداری می‌شوند؛ اعداد کلیدی در `probes.json` ماندگار شده‌اند.
- سرعت «گرم» اندازه‌گیری شد؛ اولین درخواست سرد پس از تولید CSS پست‌های المنتور کندتر است (عادی وردپرس/المنتور).
