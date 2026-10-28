# تاریخچهٔ توسعهٔ چرخهٔ نهایی — ۲۰۲۶/۱۰/۲۸ (ZarinCode 4.0.0-rc.2)

این سند ادامهٔ [به‌روزرسانی ۲۰۲۶/۱۰/۲۷](2026-10-27-COMPLETION-UPDATE-FA.md) است و **فقط کارهایی را ثبت
می‌کند که واقعاً در این چرخه اجرا، آزموده و push شده‌اند**. مرجع درخواست‌ها:
[`docs/prompts/2026-10-28-six-requests-and-cleanup.md`](../prompts/2026-10-28-six-requests-and-cleanup.md)
و [`docs/prompts/USER-REQUESTS.md`](../prompts/USER-REQUESTS.md). پایهٔ کد: `8b89465`؛ کامیت‌های این چرخه:
**`0ac3b64` → `610c76c` → `f4ea8f3`** (+ کامیت مستنداتِ همین پیام).

---

## ۱. رویدادهای محیطی و ترمیم‌ها (صادقانه، برای بازتولید)

| رویداد | ترمیم انجام‌شده |
|--------|------------------|
| ریست کانتینر و حذف کامل `/tmp` (فیکسچر، مدرن‌کنندهٔ وردپرس، شواهد، MariaDB) و ناپدیدشدن `.git/config` | نصب مجدد `php8.4-cli/xml/mbstring/curl/mysql/gd/zip/intl`، `mariadb-server`، `composer`، `php-xdebug`؛ دانلود `wp-cli.phar`؛ دیتابیس‌های `zc_fix`/`zc_unit` با کاربر `zc/zcpass` |
| بازگشت `HEAD` به `b87f639` (۷ کامیت عقب‌تر) در حالی که درخت کار = محتوای `origin/main` + کارهای این چرخه | **جراحی git:** `git reset --soft origin/main` سپس `git add -A` و راستی‌آزمایی diff پشته‌شده فقط با فایل‌های چرخه؛ بازگردانی `packages/` به ایندکس از HEAD؛ سپس کامیت و push. هیچ تاریخچه‌ای بازنویسی نشد |
| ناقص بودن object store (partial clone با `.promisor` ولی بدون تنظیم remote) و خطای `invalid object` | `remote.origin.promisor=true` + `partialclonefilter=blob:none` + `extensions.partialClone=origin` → بازیابی تنبل آبجکت‌های گمشده در push |
| حذف مکرر `.git/config` و بازنشانی مجوز کلید SSH بین پیام‌ها (این مسیرها در snapshot مستقر نمی‌شوند) | بازسازی remote SSH + هویت + تنظیمات sparse/promisor و `chmod 600 .ssh/id_ed25519`؛ مراحل ترمیم به بخش «خودترمیمی» در همین سند و `AGENTS.md` افزوده شد |
| پوشش‌شدن `packages/` با sparse-checkout | بازگردانی `core.sparseCheckout=true` + `git sparse-checkout reapply` → `git status`=صفر |

## ۲. کارهای کدنویسی شش درخواست (با فایل‌های تغییریافته)

### ۲.۱ حذف کامل ویجت هیرو و اسلایدر محصولات (درخواست ۱)
- `models/hero_profile.json` **حذف کامل**؛ همهٔ ارجاع‌ها از `hero.json` با prune استاندارد پاک شد
  (۷۳/۶۲/۲۲ گره، صفر `zc-dark-card` داخل هیرو)؛ `wpml-config.xml` بلوک hero_profile حذف شد.
- ویجت تایمر «Crextio Studio Tracker» و کارت بنیان‌گذار از render حذف شدند (`Original_Design::render_hero`
  بدون `$phone`، `Hero_Layout::migrate_tree`، سیم‌کشی `Demo_Seeder:903`).
- **`Widget_Products_Slider`** جدید در `Professional_Widgets.php` (~۱۱ ویرایش): حالت query با
  `Editable_Renderer::query_items('products')` + حالت دستی `records.N.*`، گزینه‌های اسلایدر،
  `raw_link()`/`slider()`، RTL با `data-zc-dir`، scroll-snap، نقش‌های `region/group`، دکمه‌ها/نقطه‌ها فقط
  اگر `total>1`، `per_view` از طریق `--zc-per-view`. سیم‌کشی `native.js` + `professional.js/css`.
- مدل `models/products.json` با `fallback_image_for`×۶؛ دموی XML: گره `932991aa` به
  `zarincode_products_slider` (شمارش‌ها: hero_profile 1→0، slider 0→۱).
- **کاوشگر:** `hero_profile=۰`، تایمر=۰، Tracker=۰، ترک اسلایدر=۱ با ۶ اسلاید و نقش carousel.

### ۲.۲ سرعت (درخواست ۲) — `zarincode/inc/Core/Assets.php`
- ۴ متد + هوک: (الف) پیش‌بارگذاری فونت بحرانی Vazirmatn (۲–۳ `<link rel=preload>`)؛ (ب) Font Awesome
  **غیرمسدودکننده**: preload با `onload` swap + fallback در `<noscript>` (شناسه حفظ شد)؛ (ج) `defer`
  برای اسکریپت‌های تم (`zarincode-main`، `zarincode-dialogs` — ۶ اسکریپت defer در خروجی)؛ (د) dequeue
  شرطی `wp-block-library` فقط در صفحات بدون بلوک.
- **کاوشگر:** فونت‌ preload=۳؛ CSS مسدود FA=۰ (خارج از noscript)؛ خانه ۲۰۰ در ~۰٫۳s گرم.

### ۲.۳ آیکون‌های قیمت `zc-feature-icon` (درخواست ۳)
- `zarincode/assets/css/zarincode-main.css`: بلوک chip جایگزین بلوک دایرهٔ تیره — پوشش هر سه انتخاب‌دهنده
  (`li > i`، `li > svg`، `li .zc-feature-icon`)، ریست wrapper داخلی (رنگ inline هر آیتم = رنگ glyph)،
  حالت `.is-popular` با `--color-accent`/`--color-accent-contrast` (#2A2C31)، هاور + احترام
  `prefers-reduced-motion`.
- `aria-hidden` در هر دو خروجی‌دهنده: `Pricing_Features::render` + `Original_Design.php:1004`.
- **کاوشگر:** ۳۶/۳۶ نشان در `/pricing/` + قاعدهٔ chip در CSS زنده.

### ۲.۴ محتوای خدمات (درخواست ۴)
- `tools/build-service-demo.py`: H1 کلیدواژه‌ای دوتایی «خدمات حرفه‌ای طراحی سایت، سئو و توسعه نرم‌افزار» +
  «از تحلیل تا رشد پایدار» و معرفی `<strong>` ۶۳۵ نویسه‌ای؛ بازسازی ۶ CPT خدمت (content1=2652 نویسه:
  فرایند اجرا / چرا زرین کد با خط «امنیت تا سقف ۹۹٪» / پرسش‌های پرتکرار، لینک قیمت و تماس).
- `documents.json`: عنوان + توضیح بلند + ۶ بند خدمات برای حالت ویرایشگر/سفارشی؛
  `Legal_And_Inner_Pages::documents()` رزولور بازگشتی `{{HOME}}`/`{{THEME}}` (پورتابل‌سازی فقط models/ را
  پوشش می‌دهد).
- **کاوشگر:** `/services/` تک H1 درست + معرفی unescaped + ۲۹ پیوند `/service/`؛ پست خدمت ۲۰۰؛
  صفر placeholder تحت‌اللفظی.

### ۲.۵ بستهٔ مقالات دمو (درخواست ۵)
- **ابزارها:** `tools/build-articles.py` (تولیدکنندهٔ WXR با دروازهٔ راستی‌آزمایی: سهمیه دقیق،
  نشان امنیت، slug انگلیسی، خلاصه≥۸۰، ≥۲ H2، بدون H1، پیوند `https://zarincode.com`، minidom)
  + `tools/build_articles_data.py` (۷۱ مقاله با سرعنوان سیاست منبع: **فقط مستندات اولیهٔ خارجی** —
  WordPress.org، Google Search Central، web.dev، MDN، WCAG، NNG؛ کپی از وب فارسی ممنوع).
- **سهمیهٔ نهایی:** طراحی وب ۱۰ / سئو ۱۰ / تولید محتوا ۵ / آموزش وردپرس **۱۲** (پیشخوان، نوشته، برگه،
  رسانه، نظرات، پوسته‌ها، منو و ابزارک‌ها، ویرایشگر بلوک، افزونه‌ها، کاربران و نقش‌ها، ابزارها و تنظیمات،
  ووکامرس) / امنیت **۲۲** (≥۲۰؛ هر مقاله شامل «امنیت تا سقف ۹۹٪»؛ مجموع نشان در XML=۲۲) / سرعت و سئو
  **۱۲** (≥۱۰، موضوع: رفع کندی + بهبود سئو).
- خروجی: `zarincode/demo-data/zarincode-articles.xml` (۲۳۸,۲۹۷ بایت، ۷۱ آیتم، ۶ دستهٔ
  `term_id` ۴۱۰–۴۱۵، متا نشان‌گر `_zc_demo_article`).
- **درون‌ریزی از دمو:** ورودی دوم `Demo_Seeder::ocdi_import_files()` (بدون Redux، پیش‌نمایش/اطلاعیه) —
  نتیجه در `docs/audits/2026-10-28-final-cycle/ocdi-files.json`.
- **کاورها:** `Demo_Seeder::assign_demo_article_covers()` + `sideload_demo_image()` — ۴ کاور چرخشی تم
  برای ۷۱/۷۱ مقاله پس از درون‌ریزی.
- اصلاح تاریخ‌ها (پایه ۲۰۲۶-۰۶-۰۱ + i×۲روز؛ صفر مقالهٔ آینده)، درون‌ریزی `--authors=create`،
  شمارش دسته‌ها: 10/10/5/12/22/12 دقیق؛ وبلاگ ۸ کارت با تصویر + صفحه‌بندی آرشیو.
- شواهد: `docs/audits/2026-10-28-final-cycle/{article-import-counts.json,ocdi-files.json,probes.json}`.

### ۲.۶ ممیزی و پیاده‌سازی سئو/اسکیما (درخواست ۶)
- کلاس جدید **`zarincode/inc/Core/Seo_Meta.php`** (boot در `Bootstrap/Theme.php`):
  متا توضیح (خلاصه/اولین پاراگراف/short_description محصول/توضیح دسته → شعار برند، ≤۱۶۰ نویسه)،
  canonical برای آرشیوها (تکرار با `rel_canonical` هسته در singular وجود ندارد؛ صفحه‌بندی → URL پایه)،
  `noindex, follow` فقط برای جستجو و 404، Open Graph کامل (`og:title/description/type/url/image/
  site_name/locale` + زمان article)، Twitter Card (`summary_large_image`/`summary`)،
  اسکیمای `WebSite`+`SearchAction` فقط در صفحه اصلی، و خط `Sitemap:` در `robots.txt`.
- `Article_Ratings::schema()`: افزودن `inLanguage` و `publisher` (Organization + لوگو از site icon یا
  screenshot)؛ `aggregateRating` همچنان فقط پشت فیلتر صریح.
- همهٔ خروجی‌ها پشت `Compatibility/SEO::has_owner()` (Yoast/RankMath/AIOSEO) — بدون متادیتای دوتایی.
- **گزارش ممیزی:** `docs/audits/2026-10-28-seo-schema-audit/README.md` (جدول ۱۶ بنده‌ای + تصمیم‌های
  مستندشده: BreadcrumbList حذف چون UI نانما ندارد؛ FAQPage چون بلوک ساختاریافته نیست؛ hreflang خارج از
  دامنه؛ JSON-LD محصول متعلق به ووکامرس).
- **کاوشگر:** متا توضیح ۱/۱/۱ در خانه/مقاله/دسته؛ OG=۷ خانه و۷ مقاله؛ Twitter=۴؛ canonical=۱/۱/۱؛
  noindex جستجو✓/404✓/مقاله✗؛ `SearchAction`✓ `BlogPosting+publisher`✓ `ProfessionalService`=۱؛
  `lang=fa-IR` `dir=rtl` `og:locale=fa_IR`؛ robots.txt+سایت‌مپ✓.

## ۳. باتری کیفیت تازه (۲۰۲۶-۱۰-۲۸ — متن خام: `docs/audits/2026-10-28-final-cycle/battery.txt`)

| بررسی | نتیجه |
|-------|-------|
| `php -l` کل درخت | ۰ خطا در **۸۳ فایل** |
| WPCS (`composer standards`) | ۰ نقض (exit 0) |
| PHPStan سطح ۱۰ | No errors |
| PHPUnit (فیکسچر یکبارمصرف `/tmp/zc-fixture`) | **OK — ۳۷ تست / ۲۰۹ assertion** |
| پوشش خطی سرویس‌های حیاتی | **۸۸٫۴۸٪ (507/573) ≥ کف ۸۰** (`check-coverage.py`) |
| قرارداد حسابرسی ویجت (node --test) | ۶/۶ |
| آداپترهای چندزبانه none/polylang | ۶/۶ + ۶/۶ |
| واحد Skill-3 (`qa-skill-v3-unit.php`) | passed — ۹۴ assertion |
| قواعد معماری (`qa-skill-v3-architecture.py`) | exit 0 |
| نگهبان‌های بسته‌بندی (`test-build-release.py`) | ۶/6 OK |
| راستی‌آزمایی ۴ JSON + ۳ XML + JS | سالم |
| بسته‌سازی (`build-release.py`) | `crc_and_source_parity: passed` |

## ۴. بسته‌ها و تحویل

- `deliverables/` (محلی، خارج از درخت گیت): `ZarinCode-4.0.0-rc.2-project.zip` (22,922,987B)،
  `zarincode-4.0.0-rc.2.zip` (10,729,997B)، `zarincode-core-4.0.0-rc.2.zip` (431,877B) +
  `BUILD-MANIFEST.json` (`source_commit: 0ac3b64`) + `SHA256SUMS`.
- بایگانی در گیت‌هاب: `packages/candidates/4.0.0-rc.2/2026-10-28-final-six-requests/` (کامیت `610c76c`)؛
  تازه‌سازی سطح بالای `candidates/4.0.0-rc.2/` و ریشهٔ `packages/`.
- `commercial_approved=false` و `human_release_approval=pending` (به‌روزشده در `docs/governance/release-gates.json`).

## ۵. پاک‌سازی‌ها (به ترتیب)

1. **پیش از تکمیل کدها:** فایل زائد `i`، کش‌های `.phpstan-cache`/`.wp-cli`/`__pycache__` → ۱۸۶→۱۰۷MB.
2. **حذف کامل محیط تست** (دستور کاربر): توقف سرور فیکسچر؛ `apt-get purge mariadb-server` + حذف
   `/var/lib/mysql` (۱۹۵MB)؛ حذف `/tmp/zc-fix` (۳۰۲MB)، `/tmp/zc-fixture` (۱۸۲MB)، `/tmp/zc-zips` (۴۶MB)،
   `wp-cli.phar`، شواهد HTML؛ `apt-get clean`.
3. **داخل ورک‌اسپیس:** کش بازسازی‌شدهٔ PHPStan (۲۵MB)، خروجی‌های خام QA در docs (installed-widgets ×۲
   و coverage ×۳ = ۷٫۵MB؛ نتایج در dispositions/release-gates ماند و فایل‌ها در تاریخ گیت محفوظند —
   کامیت `f4ea8f3`)؛ `git gc --prune=now` (loose ۳۳MB→۰، pack ۷۳→۶۵MB).
   **نتیجه: ۴۸۸ → ۱۹۰ → ۱۱۶MB** و `git status`=صفر.

## ۶. خودترمیمی محیط گیت (برای پیام‌های بعدی — این مسیرها در snapshot ذخیره نمی‌شوند)

```bash
# ۱) مجوز کلید SSH (پس از هر بازیابی snapshot دوباره 644 می‌شود)
chmod 700 ~/.ssh && chmod 600 ~/.ssh/id_ed25519
# ۲) remote + هویت + partial-clone + sparse (اگر .git/config ناپدید/خالی بود)
git remote add origin git@github.com:Ehsan-Np/Zarincode-1.git 2>/dev/null || \
  git remote set-url origin git@github.com:Ehsan-Np/Zarincode-1.git
git config user.name "Ehsan NaderiPanah" && git config user.email "info@zarincode.com"
git config remote.origin.promisor true && git config remote.origin.partialclonefilter blob:none
git config extensions.partialClone origin
git config core.sparseCheckout true && git config core.sparseCheckoutCone false
# ۳) راستی‌آزمایی
ssh -o BatchMode=yes -T git@github.com   # انتظار: Hi Ehsan-Np
git fetch origin && git rev-parse --short HEAD origin/main
```
اگر `git add` روی `packages/` خطای sparse داد: `git add --sparse <مسیرها>`.
اگر status پس از sparse پر از ` D packages/…` شد: `git sparse-checkout reapply`.

## ۷. وضعیت نهایی این چرخه

- `git status` = صفر؛ `HEAD == origin/main`؛ push‌شده روی `github.com/Ehsan-Np/Zarincode-1`.
- ورک‌اسپیس: **۱۱۶MB** = `.git` ۶۶MB (تاریخچهٔ حفظ‌شده) + `deliverables` ۳۳MB (نسخهٔ نهایی درخواستی) +
  سورس/مستندات/آپلودها ~۱۷MB؛ صفر فایل تست/کش/خروجی موقت.
- محیط تست (فیکسچر، دیتابیس، سرور، افزونه‌های دانلودی) **کاملاً حذف‌شده**؛ بازتولید طبق
  `tools/quality/README.md` ممکن است.
- محدودیت‌های باز: `commercial_approved=false`؛ تست مرورگری این چرخه اجرا نشد (آخرین رکورد ۲۰۲۶-۱۰-۲۷)؛
  `human_release_approval=pending`؛ WPML خارج از دامنه (تصمیم صریح کاربر).
