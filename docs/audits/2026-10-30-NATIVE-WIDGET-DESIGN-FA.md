# ممیزی طراحی ابزارک‌های بومی — 4.0.2

## ساختار
- `zarincode/assets/css/widgets/tokens.css`: نگاشت به توکن‌های پالت و سطوح قالب، semantic success/error متناسب با حالت روشن/تیره.
- `wordpress.css`: ابزارک‌های کلاسیک و جایگزین‌های بلوکی؛ فقط در scope بدنهٔ `zc-wp-widget-skin`.
- `elementor/official.css`: گسترش پوستهٔ بومی موجود؛ scope `zc-elementor-widget-skin`، specificity کم و بدون important؛ ویجت‌های سفارشی `zarincode_*` هدف قرار نمی‌گیرند.
- `woocommerce.css`: خانواده‌های کلاسیک و بلوکی؛ scope `zc-wc-widget-skin`، بدون override قالب PHP افزونه یا تغییر رفتار جاوااسکریپت.
- `Compatibility/Native_Widget_Styles.php`: body_class و enqueue عمومی، وابستگی به سبک اصلی و ترتیب Woo، cache key با filemtime و کلیدهای مستقل.
- `Compatibility/Elementor_Styles.php`: hook عمومی Elementor، حذف gate قدیمی singular/_elementor_edit_mode تا template/archive نیز پوشش بگیرند.

هیچ اسکریپت رفتاری جدیدی در frontend، فونت خارجی یا درخواست CDN افزوده نشده است. JS تغییر حالت در صفحهٔ fixture فقط ابزار QA است، نه runtime قالب. کلاس‌های WP/WC و نقش‌ها/پیام‌های خطا حذف یا پنهان نمی‌شوند. شبکهٔ محصول count کلاسیک columns-N را در دسکتاپ و دو/یک ستون در اندازه‌های کوچک دنبال می‌کند.

## اجرای ثبت‌شده
- WordPress 6.8.3 از codeload رسمی؛ SQLite integration pinned به `d2082798c7371934a3565d692bf7aeb6a20376da`، `db.copy` در wp-content/db.php؛ قالب و هستهٔ جاری فعال.
- محیط محلی یک‌بارمصرف، `WP_ENVIRONMENT_TYPE=local`، `ZARINCODE_DISPOSABLE_QA=true`، HTTP و mail بیرونی مسدود؛ PHP-WASM CLI 3.1.61/PHP 8.5.
- `tools/qa-wordpress-widget-skin.php`: **۱۸ assertion واقعی** شامل body_class، بارگذاری/ترتیب وابستگی و opt-out، رندر واقعی Search، Recent Posts، Categories، Tag Cloud، Calendar و Text. callback المنتور مستقیم فراخوانی شد؛ خود افزونهٔ المنتور نصب نبود.
- `tools/qa-native-widget-styles.php`: **۲۰ assertion با doubles** برای hookها، نبود Woo، کلاس mock Woo، گزینه‌ها، فایل‌ها و dependency. این بخش integration ووکامرس نیست.
- `tools/qa-native-widget-styles.cjs`: **۱۳۲۴ assertion** شامل AST همه selector/declarationها و مرورگر Chromium 133 در ۶ عرض ۳۲۰، ۳۷۵، ۴۳۰، ۷۶۸، ۱۰۲۴، ۱۴۴۰ × دو جهت × دو حالت = **۲۴ حالت**. بدون overflow، رنگ و سطح پویا، کارت‌ها، ستون‌های Woo، فوکوس و disclosure بومی، حفظ CSS اختصاصی نویسنده حتی پیش از CSS پوسته، عدم تغییر ویجت سفارشی، کنتراست متن سه نمونه حداقل ۴٫۵، کاهش حرکت و opt-out بررسی شد.
- همین ماتریس یک بار با fixture ثابت و یک بار با جایگزینی بخش وردپرس به HTML رندرشده توسط وردپرس واقعی اجرا شد؛ ۱۳۲۴ در هر اجرا، نه ۲۶۴۸ حالت مستقل افزونه‌ها. خروجی‌های Elementor/Woo همچنان نمونهٔ HTML هستند.
- `qa-site-contract.php`: **۳۱۷ قرارداد با doubles** و syntax-check **۹۱ فایل PHP runtime**.
- `qa-skill-v3-unit.php`: **۹۴ قرارداد مستقل**؛ architecture: **۹ قاعدهٔ محدود**.
- نسخه/بسته/builder و widget-audit JS: ۴ / ۵ / ۶ / ۶ تست؛ snapshot تاریخی آزمون‌های رسانه دوباره اجراشده محسوب نمی‌شود و کد اصلاح رسانه در این نوبت تغییر نکرده است.

## بازسازی QA
ابزارهای مرورگر فقط در `tools/browser/package*.json` هستند و به ZIP runtime وارد نمی‌شوند:
```
npm ci --prefix tools/browser
NODE_PATH=tools/browser/node_modules node tools/qa-native-widget-styles.cjs
php tools/qa-native-widget-styles.php
php tools/qa-site-contract.php
```
برای HTML واقعی وردپرس، در fixture تازه پس از wp-load، `qa-wordpress-widget-skin.php` را require کنید؛ متغیر `ZC_NATIVE_WP_MARKUP=/tmp/widgets.html` رندر واقعی را ذخیره می‌کند. سپس همین متغیر را به تست مرورگر بدهید. **ابزار integration مخرب است و نباید روی سایت مشتری اجرا شود.**

در sandbox فاقد NSS، کتابخانه‌های بسته Chromium 133 (`al2023.tar.br`) موقتاً باز شدند و LD_LIBRARY_PATH به lib آن اشاره کرد؛ این فایل‌ها جزو تحویل نیستند. در دستگاه عادی با کتابخانه‌های Chromium نیازی به این مرحله نیست. خروج صفر PHP-WASM به‌تنهایی کافی نیست؛ stdout PASS و stderr هر بار بررسی شدند.

`NODE_PATH=tools/browser/node_modules node tools/qa-native-widget-styles.cjs --serve` گالری نمایشی را روی 0.0.0.0:4173 با دسترسی محدود به assetها ارائه می‌دهد. صفحه صریحاً نمونهٔ HTML است، نه فروشگاه عملیاتی؛ پرداخت یا خرید انجام نمی‌دهد.

## محدودیت و دروازه‌های باز
خود Elementor/Pro و WooCommerce در محیط اجرای این نوبت نصب نشده بودند؛ صحت کامل lifecycle، همه نسخه‌ها/افزونه‌های جانبی، AJAX/سبد واقعی، Select2، درگاه پرداخت و iframe ادیتور تأیید نشده است. این نیازمند staging با نسخه‌های نصب‌شدهٔ مشتری است. کنتراست نمونه‌ها ممیزی کامل WCAG نیست. no-important تضمین نمی‌کند هر CSS سفارشی/inline/external بدون بررسی دقیق قابل‌پیش‌بینی باشد. هیچ دروازهٔ تجاری/سازگاریِ باز با این گزارش بسته نشده است.
