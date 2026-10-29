# ممیزی محدود اصلاح تصاویر همراه — 4.0.1

تاریخ اجرا: ۲۰۲۶/۱۰/۳۰؛ محیط موقت و مستقل از سایت مشتری. علت قطعی هاست گزارش‌شده تشخیص داده نشده؛ پوشش خطا و راه جایگزین آزموده شده‌اند، نه خود هاست.

## محیط قابل بازسازی
- سورس رسمی WordPress 6.8.3 از `https://codeload.github.com/WordPress/WordPress/tar.gz/refs/tags/6.8.3`.
- SQLite integration از `https://codeload.github.com/WordPress/sqlite-database-integration/tar.gz/d2082798c7371934a3565d692bf7aeb6a20376da`، کپی `db.copy` در `wp-content/db.php` و سورس افزونه در `wp-content/plugins/sqlite-database-integration`.
- `@php-wasm/cli` نسخه 3.1.61، PHP 8.5، GD واقعی. این یک وردپرس واقعی با پایگاه داده است؛ MySQL یا Imagick نیست.
- کپی قالب و هسته جاری در wp-content؛ تنظیم `WP_ENVIRONMENT_TYPE=local` و `ZARINCODE_DISPOSABLE_QA=true`. با `WP_INSTALLING` و `wp_install` یک مدیر موقت بسازید، سپس `active_plugins=['zarincode-core/zarincode-core.php']` و `switch_theme('zarincode')`.
- `WP_HOME`/`WP_SITEURL` برابر `http://example.test`؛ جلوگیری از HTTP بیرونی با `pre_http_request` و ارسال ایمیل با `pre_wp_mail` در mu-plugin موقت. دامنه صرفاً URL آزمایش است؛ ادعای درخواست HTTP موفق/CDN مطرح نیست.
- برای تست دمو، `WP_TEMP_DIR=/nonexistent-temp/` عمداً خراب بود؛ درون‌ریز از uploads استفاده می‌کند. برای آزمون عمومی آپلود، مسیر موقت قابل‌نوشتن استفاده شد.

پس از bootstrap وردپرس، ابزارها را با مسیر مطلق `require` کنید. ابزارها مخرب‌اند: **هرگز روی سایت واقعی اجرا نشوند**. پیش از هر مجموعه کامل، DB و uploads موقت را تازه کنید تا رکوردهای قبلی خطای تزریق‌شده را دور نزنند. هر دو stdout و stderr را بررسی کنید؛ خروج صفر PHP-WASM به‌تنهایی دلیل موفقیت نیست.

## نتایج
| آزمون | نتیجه |
|---|---|
| `tools/build-demo-webp.php` | ۱۶ WebP واقعی encode/decode، ابعاد یکسان با منبع؛ مجموع ۶۷۴٬۷۳۲ بایت |
| `tools/qa-demo-articles.php` | ۵۸۸ بررسی واقعی وردپرس: ۷۱ مقاله، ۶ دسته، تصاویر و ترمیم، تکرار، حفظ داده مشتری و UI اصلی |
| `tools/qa-demo-media.php` | ۲۴۲ بررسی واقعی وردپرس: نام قدیمی/جدید، URL/ID المنتور، حفظ منابع، ترمیم بدون ریست تنظیمات، lazy و محدودیت MIME |
| `tools/qa-demo-media-errors.php` | ۵ بررسی واقعی: exception افزونه، خطای پوشه، حفظ تشخیص و رد traversal |
| `tools/qa-bundled-demo.php` | ۲۹۵ بررسی واقعی: manifest، مسیر فیلترشده/خراب، نبود editor، exception پیش از sideload، uploads غیرقابل‌نوشتن، MIME ممنوع، دموی کامل با تمام sideloadها مسدود، thumbnail بومی، تکرار و حذف ایمن |
| `tools/qa-webp-wordpress.php` | ۲۵ بررسی واقعی عمومی آپلود: متادیتا، subsizes/srcset، غیرفعال‌سازی و WebP بدون بازتبدیل |
| `tools/qa-webp-uploads.php` | ۱۴۵ بررسی با GD و کلاس‌های رسمی editor؛ APIهای محیط وردپرس doubles، نه یک integration جداگانه |
| `tools/qa-site-contract.php` | ۳۱۱ قرارداد با doubles و syntax-check برای ۹۰ فایل PHP runtime |
| `tools/qa-skill-v3-unit.php` | ۹۴ قرارداد مستقل |
| `tools/qa-skill-v3-architecture.py` | ۹ قاعده محدود معماری |
| Python: builder / package / stable-version | ۶ / ۵ / ۴ تست موفق |
| `node --test tools/widget-audit-contract.test.cjs` | ۶ قرارداد موفق؛ آزمون مرورگر نیست |

کلاس‌های GD بالادستی وردپرس در PHP 8.5 پیام deprecation مربوط به `imagedestroy` داشتند؛ ۱۴۵ assertion کامل شد. این پیام‌ها از سورس افزونه/قالب جدید نبودند.

## تصمیم‌های ایمنی
- فقط مسیر sideload مشاهده‌شده و معتبر داخل uploads برای ترمیم رکورد جدید قابل استفاده است؛ مسیر دلخواه renderer مبنا نیست.
- fallback هیچ `_wp_attached_file` به تصویر قالب ثبت نمی‌کند. حذف رکورد با WordPress آزموده شد و SHA-256 فایل قالب ثابت ماند.
- رکورد نامعتبرِ دارای مسیر غیرقابل اعتماد خودکار حذف نمی‌شود؛ از حذف اشتباه فایل مشتری جلوگیری می‌شود. در ثبت موفق fallback، دفعات بعد همان رکورد معتبر بازاستفاده می‌شود.
- WebPهای پیش‌ساخته نیاز به encoder در زمان درون‌ریزی ندارند. آزمون نبود editor با فیلتر واقعی WordPress انجام شد؛ GD به‌طور فیزیکی از محیط حذف نشده بود.
- در حالت fallback، اندازه کامل تصویر ارائه می‌شود و srcset نامعتبر ساخته نمی‌شود؛ رسانه برای ویرایش فایل محلی ندارد. برای ویرایش تصویر شخصی بارگذاری شود.

## خارج از شواهد
هاست مشتری، MySQL، Imagick، نسخه‌ها و تنظیمات واقعی Elementor/WooCommerce و مسیر HTTP/CDN آن محیط در این آزمون‌ها حاضر نبودند. آزمون رابط OCDI واقعی و ممیزی کامل همه افزونه‌ها/مجوزهای دارایی‌ها انجام نشد. دروازه‌های تجاری و تأییدهای باز قبلی حفظ شده‌اند. گزارش‌های قدیمی شواهد تاریخی‌اند و بازاجراشده تلقی نمی‌شوند.
