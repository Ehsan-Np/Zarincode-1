# ممیزی دقیق سئو و اسکیما — زرین کد ۴٫۰٫0-rc.2

- تاریخ: ۲۰۲۶-۱۰-۲۸ (Asia/Tehran)
- دامنه: کد تم و همراه (`zarincode/`، `zarincode-core/`) روی نصب یکبارمصرف `/tmp/zc-fix` (وردپرس ۷٫۱٫۳، PHP 8.4، المنتور 4.3.2، ووکامرس ۱۱٫۲٫۰)
- شرط کلی: همه خروجی‌های بومی سئو زمانی فعال‌اند که **هیچ افزونه سئویی (Yoast/RankMath/AIOSEO) فعال نباشد** — این تصمیم در `zarincode/inc/Compatibility/SEO.php` (`has_owner()`) محصور شده تا متادیتای دوتایی تولید نشود. با فعال‌شدن افزونه، قالب کاملاً کنار می‌رود.

## ۱. جدول ممیزی (الزامات فنی نتایج حرفه‌ای جستجو)

| # | الزام | وضعیت | پیاده‌سازی / شاهد |
|---|------|-------|--------------------|
| 1 | تگ `<title>` داینامیک و جدا از توضیح | ✅ | `add_theme_support('title-tag')` در `inc/Core/Setup.php`؛ خروجی واقعی صفحات با `wp_get_document_title()` تأیید شد |
| 2 | متا توضیح (description) منحصربه‌فرد هر صفحه | ✅ | جدید در `inc/Core/Seo_Meta.php::meta()`؛ منابع: خلاصه برگه/پست → توضیح اولین پاراگراف → short_description محصول → توضیح دسته‌بندی → شعار برند؛ حداکثر ۱۶۰ نویسه. شاهد: خانه/مقاله/دسته هرکدام ۱ خروجی (`probes.json`) |
| 3 | تگ `canonical` بدون تکرار | ✅ | هسته وردپرس روی صفحات singular خودش خروجی می‌دهد (`rel_canonical`)؛ `Seo_Meta` فقط آرشیوها (خانه/دسته/برچسب/نویسنده/آرشیو نوع محتوا) را پوشش می‌دهد و روی singular تکرار نمی‌کند. صفحه‌بندی به URL بدون صفحه‌بندی canonical می‌شود. شاهد: پست=۱، دسته=۱، خانه=۱ |
| 4 | `robots` دقیق (noindex صفحات غیرنتیجه‌محور) | ✅ | فقط `is_search()` و `is_404()` → `noindex, follow`؛ بقیه صفحات بدون متا ربات (ایندکس پیش‌فرض). شاهد: search=true، 404=true، مقاله غایب |
| 5 | `lang` و `dir` زبان محتوا | ✅ | `language_attributes()` → پس از نصب زبان فارسی: `<html dir="rtl" lang="fa-IR">`. توجه: زبان پایه وردپرس (en_US در نصب خام) تعیین‌کننده است؛ نصب زبان فارسی لازم است |
| 6 | Open Graph کامل | ✅ | `og:title/description/type/url/image/site_name/locale` (+ `article:published_time` و `article:modified_time` برای مقاله). شاهد: خانه=۷، مقاله=۷ متا OG. `og:type=article|product|website` |
| 7 | کارت‌های Twitter | ✅ | `twitter:card` (`summary_large_image` با تصویر، `summary` بدون) + `twitter:title/description/image`. شاهد: مقاله=۴ |
| 8 | تصویر اجتماعی (`og:image`) | ✅ | تصویر شاخص پست → آیکون سایت (site icon) → `screenshot.png` تم |
| 9 | اسکیمای سایت `WebSite` | ✅ | جدید: `Seo_Meta::website_schema()` فقط در صفحه اصلی، با `@id`، `url`، `name`، `inLanguage` و `SearchAction`. توجه: گوگل باکس «جستجوی سایت» (sitelinks search box) را از نتایج حذف کرده اما مارک‌آپ معتبر و مفید موجودیت است |
| 10 | اسکیمای مقاله `BlogPosting` | ✅ | `inc/Core/Article_Ratings.php::schema()`؛ در این چرخه `inLanguage` و `publisher` (Organization + لوگو از site icon یا screenshot) اضافه شد. `aggregateRating` عمداً فقط با فیلتر صریح و دیدگاه‌های تأییدشده فعال می‌شود (بدون امتیازسازی ساختگی) |
| 11 | اسکیمای کسب‌وکار `ProfessionalService` | ✅ | `inc/Redux/OptionsConfig.php:~1048` با نام، تلفن، ایمیل، بنیان‌گذار، توضیح و `sameAs`؛ پشت سوییچ `enable_schema_markup` و `theme_schema_allowed()` |
| 12 | `wp-sitemap.xml` | ✅ | هسته وردپرس فعال؛ شاهد: `200` |
| 13 | اعلام Sitemap در `robots.txt` | ✅ | جدید: `Seo_Meta::robots_txt` خط `Sitemap: …/wp-sitemap.xml` را اضافه می‌کند (بدون حذف خروجی هسته/افزونه‌ها). شاهد: `robots.txt` = ۲۰۰ و خط Sitemap موجود |
| 14 | هدرهای استاندارد | ✅ | charset، viewport، author در `header.php`؛ `wp_head` کامل |
| 15 | تداخل با افزونه سئو | ✅ | همه خروجی‌های بالا پشت `SEO::has_owner()` — با نصب Yoast/RankMath صفر خروجی بومی |
| 16 | دسته‌بندی و محتوای قابل خزیدن | ✅ | آرشیوهای دسته ۲۰۰ با صفحه‌بندی؛ مقالات imported با slug انگلیسی + خلاصه + ساختار H2 (شماره ۵ گزارش چرخه) |

## ۲. تصمیم‌های مستندشده (آگاهانه انجام نشده)

| مورد | تصمیم و دلیل |
|------|----------------|
| BreadcrumbList schema | **حذف آگاهانه**: طرح مصوب هیچ ناوبری نانمای breadcrumb ندارد؛ دستورالعمل گوگل مارک‌آپ را فقط هم‌ارزِ نمایش بصری می‌پذیرد. افزودن مارک‌آپ بدون UI یا UI جدید بدون تأیید طرح، خلاف قواعد پروژه است |
| FAQPage schema | در صفحات مصوب بلوک FAQ ساختاریافته (کارت سوال/جواب دارای داده‌های ساخت‌یافته) وجود ندارد؛ متن پرسش‌های مقالات/CPT ها صرفاً عنوان H3 است. مارک‌آپ بدون نمایش سازگار، ریسک اسپم‌ریچ‌بودن دارد |
| hreflang بین‌المللی | خارج از دامنه (WPML طبق درخواست صریح کاربر حذف شد؛ gettext/RTL حفظ) |
| Product JSON-LD | متعلق به ووکامرس است؛ قالب تکرار تولید نمی‌کند |
| داده زمینه‌ای (Core Web Vitals میدانی)، GSC | خارج از دسترس این محیط — بدون ادعا |
| تأیید فروشگاه ThemeForest/Zhaket | `commercial_approved=false` باقی می‌ماند |

## ۳. شاهدان اجرا (`probes.json` همین پوشه)

- متا توضیح: خانه ۱ / مقاله ۱ / دسته ۱ — بدون تکرار
- OG: خانه ۷ و مقاله ۷؛ Twitter: مقاله ۴
- canonical: مقاله ۱ (هسته) / دسته ۱ (قالب) / خانه ۱ — بدون تکرار
- noindex: جستجو ✓، 404 ✓، مقاله ✗ (غایب)
- JSON-LD: `SearchAction` ✓، `BlogPosting` + `publisher` ✓، `ProfessionalService` ۱ ✓
- `lang=fa-IR`، `dir=rtl`، `og:locale=fa_IR`، robots.txt + سایت‌مپ ✓

## ۴. بازتولید

```bash
php wp-cli.phar eval 'echo Zarincode\Theme\Core\Seo_Meta::class;' --path=/tmp/zc-fix   # موجود
curl -s http://127.0.0.1:8080/ | grep -E 'name="description"|og:|application/ld\+json'
curl -s http://127.0.0.1:8080/robots.txt
curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/wp-sitemap.xml
```

کد: `zarincode/inc/Core/Seo_Meta.php` (نو)، `zarincode/inc/Bootstrap/Theme.php` (boot)، `zarincode/inc/Core/Article_Ratings.php` (publisher/inLanguage)، `zarincode/inc/Compatibility/SEO.php` (سقف اختیار).
