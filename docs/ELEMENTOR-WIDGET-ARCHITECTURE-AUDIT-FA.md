# بررسی مستندات رسمی Elementor و ممیزی معماری ویجت‌های زرین کد

تاریخ بررسی: ۲۲ اکتبر ۲۰۲۶

نسخه مبنا: زرین کد ۲٫۷٫۰، commit `347758d`.

**وضعیت این سند: پژوهش، ممیزی کد و معیار بازنویسی است؛ نه اعلام تکمیل بازنویسی یا انتشار نسخه جدید.** بخش‌های مرتبط مستندات رسمی بررسی شدند؛ ادعای مطالعه تمام صفحات سایت یا آموزش دائمی مدل مطرح نیست. یافته‌ها در این سند ثبت شده‌اند تا مرجع توسعه همین پروژه باشند.

## ۱. نتیجه اصلی و اصلاح برداشت قبلی

قابلیت کلیک روی متن، وجود تعداد زیادی کنترل، استفاده از کلاس `Widget_Base` و موفقیت چند آزمون ذخیره‌سازی، به‌تنهایی اثبات «شخصی‌سازی کامل مثل ویجت‌های بومی Elementor» نیستند.

نسخه فعلی واقعاً از API بومی برای بخشی از ویرایش متن استفاده می‌کند؛ اما در طراحی اصلی هنوز متن‌های استخراج‌شده از HTML و مسیرهای DOM جای مدل محتوای معنایی را گرفته‌اند. کنترل‌های کامل‌تر عمدتاً به چیدمان سفارشی مربوط‌اند. بنابراین نتیجه‌گیری قبلی درباره کامل‌بودن ویرایش‌پذیری، فراتر از پوشش واقعی پیاده‌سازی و آزمون بود.

هدف صحیح پروژه: **حفظ ظاهر مورد پسند کاربر، همراه با تبدیل همان ظاهر به خروجی مستقیم تنظیمات Elementor؛ نه ارائه کنترل کامل فقط در یک طرح جایگزین.**

## ۲. چرخه استاندارد یک ویجت

طبق [ساختار ویجت](https://developers.elementor.com/docs/widgets/widget-structure/)، [تنظیمات ویجت](https://developers.elementor.com/docs/widgets/widget-settings/) و [رندر ویجت](https://developers.elementor.com/docs/widgets/widget-rendering/):

```text
Widget_Base
  ├── هویت پایدار: get_name / get_title / get_icon
  ├── register_controls: قرارداد داده و تنظیمات ظاهری
  ├── settings: مقادیر ثبت‌شده در سند Elementor
  ├── render: تولید مستقیم HTML از تنظیمات پردازش‌شده در PHP
  ├── content_template: پیش‌نمایش کلاینتی برای بخش‌های مناسب
  └── dependencies / frontend handler: تعاملات وابسته به همان نمونه ویجت
```

در PHP، `get_settings_for_display()` مبنای خواندن داده نمایشی است. در قالب JS، داده در `settings` قرار دارد. داده باید متناسب با نوع خروجی escape یا sanitize شود؛ مثال آموزشیِ دارای خروجی خام را نباید بدون بررسی وارد محصول کرد.

شناسه‌های کنترل‌ها بخشی از قرارداد ذخیره‌سازی‌اند. نام‌هایی مثل `title`، `description`، `image` و `primary_link` باید به داده مشخص اشاره کنند، نه به «متن شماره ۲۱ در مسیر سومین div».

در [ساختار داده رسمی](https://developers.elementor.com/docs/data-structure/) توضیح داده شده که سند به‌صورت JSON در post metadata ذخیره می‌شود. پیاده‌سازی نباید برای متن یک مسیر موازی ذخیره‌سازی و برای استایل یک منبع نامرتبط بسازد.

### تفاوت پیش‌فرض و HTML ثابت

- مجاز و لازم: متن و چیدمان دمو به‌عنوان default داده/استایل تعریف شوند.
- ناکافی برای این نیاز: خروجی ثابت تولید شود و سپس فقط بعضی رشته‌ها در آن جایگزین شوند.
- شکل صحیح: تمام اجزای قابل تنظیم، مستقیماً از مدل تنظیمات رندر شوند؛ پیش‌فرض‌ها همان ظاهر دمو را بازتولید کنند.

## ۳. کنترل مناسب برای هر داده

| نیاز | کنترل/سازوکار مناسب | شرط پذیرش در زرین کد |
|---|---|---|
| عنوان، نام، نشان، متن دکمه | TEXT | کلید معنایی مستقل؛ خالی‌کردن و reset رفتار تعریف‌شده داشته باشد |
| متن چندخطی ساده | TEXTAREA | شکست خطوط در پنل، پیش‌نمایش و خروجی یکسان مدیریت شود |
| متن قالب‌بندی‌شده | WYSIWYG | HTML مجاز حفظ شود؛ تگ نامعتبر داخل عنوان یا لینک تو در تو تولید نشود |
| عدد، امتیاز، تعداد | NUMBER یا SLIDER | محدوده و نوع داده معتبر؛ متن نمایشی با مقدار واقعی اشتباه نشود |
| عکس | MEDIA و در صورت نیاز Group_Control_Image_Size | id/url، اندازه تصویر، alt و fallback درست مدیریت شوند |
| آیکون یا SVG | ICONS | رندر با Icons_Manager و سازوکار مناسب JS؛ نه صرفاً تغییر کلاس i |
| لینک | URL | url، is_external، nofollow و گزینه‌های ارائه‌شده واقعاً مصرف شوند |
| نمایش/عدم نمایش | SWITCHER | در HTML و پیش‌نمایش اثر واقعی داشته باشد |
| انتخاب طرح/تگ/منبع | SELECT یا CHOOSE | تنها گزینه‌های معتبر و پشتیبانی‌شده ارائه شوند |
| کارت، مزیت، عضو، مرحله، بند | REPEATER | داده واقعی آیتم؛ افزودن، حذف، تکثیر و ترتیب، متناسب با ماهیت بخش |
| رنگ و تایپوگرافی | COLOR و Group_Control_Typography | selector درست و امکان استفاده از امکانات Global متناسب با نسخه |
| حاشیه، پس‌زمینه، سایه | Group Controls مناسب | فقط روی اجزایی ارائه شوند که آن ویژگی اثر دارد |
| فاصله، اندازه، ستون، تراز | کنترل Responsive مناسب | مقادیر دستگاه‌ها و breakpointهای فعال رعایت شوند |

منابع: [کنترل‌های ویرایشگر](https://developers.elementor.com/docs/editor-controls/)، [رندر رسانه](https://developers.elementor.com/docs/widgets/rendering-media/)، [کنترل آیکون](https://developers.elementor.com/docs/editor-controls/control-icons/)، کنترل URL [14](https://developers.elementor.com/docs/editor-controls/control-url).

«همه‌چیز قابل ویرایش» به معنی استفاده از WYSIWYG برای همه چیز نیست. تصویر، آیکون، لینک، مقدار عددی و چیدمان باید کنترل متناسب خود را داشته باشند؛ Inline Editing مکمل آن‌هاست، نه جایگزینشان.

## ۴. ویرایش مستقیم متن، به روش بومی

[مستند رسمی Inline Editing](https://developers.elementor.com/docs/widgets/rendering-inline-editing/) دو مسیر را نشان می‌دهد:

- PHP: `add_inline_editing_attributes()` و attribute renderer.
- قالب JS: `view.addInlineEditingAttributes()` و `view.getRenderAttributeString()`.

نوع toolbar باید با قرارداد داده سازگار باشد:

- `none`: متن ساده؛
- `basic`: قالب‌بندی محدود؛
- `advanced`: محتوای غنی مناسب WYSIWYG.

برای عنوانی که متن ساده ذخیره و با `esc_html()` چاپ می‌شود، ارائه toolbar قالب‌بندی غنی که خروجی‌اش بعداً به‌صورت متن چاپ شود، قرارداد متناقضی است. برای عنوان تزئینی، متن معمولی، بخش برجسته و آیکون باید داده‌های مستقل داشته باشند تا تغییر کلمات ساختار تزئینی را پاک نکند.

نمونه اتصال یک فیلد داخل Repeater، بر اساس مثال پیشرفته رسمی [1](https://developers.elementor.com/docs/widgets/advanced-example):

```php
$settings = $this->get_settings_for_display();

foreach ( $settings['items'] as $index => $item ) {
    $key = $this->get_repeater_setting_key( 'title', 'items', $index );
    $this->add_render_attribute( $key, 'class', 'zc-item-title' );
    $this->add_inline_editing_attributes( $key, 'none' );
    ?>
    <h3 <?php $this->print_render_attribute_string( $key ); ?>>
        <?php echo esc_html( $item['title'] ); ?>
    </h3>
    <?php
}
```

این قطعه فقط الگوی اتصال است، نه فایل آماده نصب. ثبت کنترل‌ها، defaults، شناسه‌های معتبر، حالت خالی و رندر JS نیز باید تکمیل و آزمون شوند.

ساخت دستی رشته‌ای مثل `items.0.title` لزوماً خطا نیست؛ helper رسمی در نسخه بررسی‌شده همین ساختار را می‌سازد. استفاده از helper، خوانایی و هماهنگی PHP/JS را بهتر می‌کند؛ مشکل معماری فعلی صرفاً استفاده‌نکردن از نام این تابع نیست.

## ۵. Repeater واقعی با فهرست متن‌های DOM فرق دارد

طبق کنترل Repeater و ساختار داده رسمی [7](https://developers.elementor.com/docs/data-structure/repeaters/)، هر آیتم مجموعه‌ای از فیلدهای معنایی و یک `_id` دارد. این شناسه برای دنبال‌کردن آیتم و استایل محلی استفاده می‌شود.

```json
{
  "items": [
    {
      "_id": "a12bc34",
      "title": "طراحی سایت",
      "description": "شرح خدمت",
      "icon": { "value": "fas fa-code", "library": "fa-solid" },
      "link": { "url": "/contact-us/", "is_external": "", "nofollow": "" }
    }
  ]
}
```

الزامات:

1. کلید Inline Editing بر اساس نام Repeater، index و نام فیلد ساخته شود.
2. selector اختصاصی آیتم با `_id` و `elementor-repeater-item-*` هماهنگ باشد؛ برای استایل ردیف از `{{CURRENT_ITEM}}` در کنار `{{WRAPPER}}` استفاده شود.
3. `_id` معتبر موجود بی‌دلیل عوض نشود. برای داده قدیمی/واردشده، اصلاح شناسه پیش از مرحله تولید CSS انجام شود؛ اصلاح فعلی این بخش باید حفظ شود.
4. افزودن/حذف/تکثیر/جابه‌جایی آیتم‌های واقعی نباید صرفاً برای ثابت‌ماندن مسیر HTML مسدود شود.
5. محدودیت ساختاری واقعی مجاز است؛ مثلاً یک Hero می‌تواند دقیقاً دو CTA داشته باشد. الزام این نیست که هر فیلد ثابت به Repeater تبدیل شود.
6. شرط یک فیلد داخل Repeater را نمی‌توان طبق مثال مستندات، بی‌واسطه به کنترل سطح والد وابسته کرد. شروط هم‌سطح و شرط کل Repeater باید درست تفکیک شوند.

منابع: [Repeater Control](https://developers.elementor.com/docs/editor-controls/control-repeater/)، [Conditional Display](https://developers.elementor.com/docs/editor-controls/conditional-display/)، مثال پیشرفته [1](https://developers.elementor.com/docs/widgets/advanced-example).

## ۶. استایل واقعی، Responsive و Global

در مستند CSS Selectors [18](https://developers.elementor.com/docs/editor-controls/selectors)، `selector` برای Group Control و `selectors` برای کنترل عادی توضیح داده شده است.

```php
'selectors' => [
    '{{WRAPPER}} .zc-hero-title' => 'color: {{VALUE}};',
]
```

وجود کنترل رنگ کافی نیست: عنصر هدف باید در همان چیدمانی که کاربر می‌بیند وجود داشته باشد. اگر خروجی کلاس `zc-hero-title` دارد ولی selector تنها `.zc-edit-title` را هدف گرفته، این کنترل طرح اصلی را شخصی‌سازی نمی‌کند.

اصول اجرایی:

- scope هر نمونه ویجت با `{{WRAPPER}}`؛ بدون تغییر ناخواسته سایر نمونه‌ها.
- Typography، رنگ، فاصله، تصویر و دکمه برای بخش‌های واقعی، نه تولید انبوه کنترل‌های نامربوط.
- Normal/Hover و در صورت لزوم Focus/Active با selector درست؛ هاور والد و هاور خود عنصر دو رفتار متفاوت‌اند.
- استفاده از `add_responsive_control()` و هماهنگی با breakpointهای فعال، نه اتکا به سه media query ثابت و مستقل از Elementor.
- reset و حالت ارث‌بری باید معنی‌دار باشند؛ صفر و رشته خالی نباید بدون دلیل یکسان تعبیر شوند.
- اولویت روشن: تنظیم اختصاصی کاربر، ارجاع Global انتخاب‌شده، سپس fallback بصری قالب.
- فعال‌کردن Global defaults نباید رنگ یا فونت سایت موجود را خودکار تغییر دهد. ظاهر زرد و Vazirmatn باید تا انتخاب کاربر حفظ شود.

نکته حاصل از بررسی کد خود Elementor نصب‌شده: کنترل Color و گروه Typography، امکانات Global را به‌صورت پیش‌فرض هم فعال می‌کنند. بنابراین نبود آرگومان صریح `global` در کد زرین کد، به‌تنهایی اثبات نبود پشتیبانی Global نیست. آنچه باید طراحی و آزمون شود، نگاشت پیش‌فرض‌ها، اولویت‌ها و رفتار عملی آن است.

منابع تکمیلی: [Responsive Control](https://developers.elementor.com/docs/editor-controls/responsive-control/)، [Responsive Data و breakpointهای اضافه](https://developers.elementor.com/docs/data-structure/responsive-data/)، [Global Style](https://developers.elementor.com/docs/editor-controls/global-style/).

## ۷. PHP، پیش‌نمایش JS و تعاملات

### `content_template()`

برای محتوای قابل رندر در مرورگر، الگوی رسمی از `content_template()` در کنار `render()` استفاده می‌کند. مدل داده، markup، کلیدهای ویرایش، وضعیت خالی و اعتبارسنجی تگ‌ها باید در هر دو مسیر هماهنگ باشند.

**نبود `content_template()` به‌تنهایی ویجت را نامعتبر نمی‌کند.** رندر سمت سرور برای خروجی مبتنی بر PHP، shortcode، WooCommerce یا query می‌تواند لازم و مناسب باشد. نباید برای ادعای پیش‌نمایش زنده، یک شبیه‌سازی JS ساخت که با خروجی واقعی محصول یا query تفاوت دارد.

در زرین کد باید برای هر ویجت تصمیم مستند گرفته شود: قالب کلاینتی، رندر سمت سرور، یا ترکیب سنجیده این دو. تعریف معیار صرفاً بر اساس وجود نام متد کافی نیست.

### JS Handler و وابستگی‌ها

طبق راهنمای رسمی Frontend Available [3](https://developers.elementor.com/docs/editor-controls/frontend-available):

- Handler استاندارد تعاملی می‌تواند از `elementorModules.frontend.handlers.Base` مشتق شود.
- چرخه init و تغییر تنظیمات از جمله `onInit()` و `onElementChange()` باید مدیریت شود.
- eventها، observerها، تایمرها و نمونه کتابخانه‌ها هنگام حذف یا بازسازی آزاد شوند؛ listener تکراری ایجاد نشود.
- ثبت handler به widget مشخص و نمونه `$element` محدود باشد؛ استفاده از global hook ذاتاً ممنوع نیست، اما نباید lifecycle را نادیده بگیرد.
- فقط تنظیمات لازم با `frontend_available` در اختیار JS قرار بگیرند.
- وابستگی واقعی به `elementor-frontend`/jQuery اعلام شود؛ شرط `if (window.jQuery)` جای dependency graph را نمی‌گیرد.

[Widget Dependencies](https://developers.elementor.com/docs/widgets/widget-dependencies/) همچنین تفاوت اسکریپت frontend/preview و اسکریپت مخصوص پنل ویرایشگر را مشخص می‌کند. handleها باید ثبت شوند؛ صرفاً برگرداندن یک نام ثبت‌نشده اثری ندارد.

### Dynamic Tags و Cache

Dynamic Tags فقط برای کنترل‌ها و نوع داده سازگار فعال شوند. بخشی از قابلیت‌ها/تأمین‌کننده‌ها به نسخه و افزونه‌های فعال، از جمله Pro، وابسته‌اند؛ وجود API به معنی دسترسی همه امکانات در Free نیست.

در کد شش ویجت جدید، فعال‌سازی explicit برای بسیاری از فیلدهای TEXT/MEDIA/URL دیده نمی‌شود. WYSIWYG در نسخه بررسی‌شده خود Elementor، پیش‌فرض Dynamic فعال دارد؛ پس نباید گفت هیچ فیلدی پشتیبانی ندارد. ممیزی و فعال‌سازی باید فیلدبه‌فیلد باشد.

طبق [Widget Output Caching](https://developers.elementor.com/docs/widgets/widget-output-caching/)، `is_dynamic_content(): false` فقط برای خروجی مناسبِ cache است. query، قیمت متغیر، سبد خرید یا محتوای وابسته به کاربر را نباید بدون طراحی invalidation و توجه به شرایط، ایستا اعلام کرد. درصدهای تبلیغی نمونه‌های مستندات، نتیجه اندازه‌گیری زرین کد محسوب نمی‌شوند.

منبع Dynamic: [Dynamic Content](https://developers.elementor.com/docs/editor-controls/dynamic-content/).

## ۸. رسانه، لینک و امنیت خروجی

- تصویر مبتنی بر attachment باید در صورت امکان از امکانات WordPress/Elementor برای اندازه، srcset و alt بهره ببرد؛ استفاده مستقیم از URL به‌خودی‌خود خلاف مستندات نیست، اما تنها قابلیت یک Image Widget کامل نیست.
- آیکون با `Icons_Manager::render_icon()` و در قالب JS با helper سازگار رندر شود؛ تفاوت Font Icon و SVG رعایت شود.
- لینک‌های URL control با `add_link_attributes()` یا پیاده‌سازی هم‌ارز کامل تولید شوند. اگر گزینه custom attributes در پنل دیده می‌شود ولی مصرف نمی‌شود، رابط کاربری وعده بی‌اثر داده است.
- متن ساده با escape متناسب، HTML غنی با allowlist متناسب، URL و attribute با تابع مناسب و تگ HTML با allowlist/validator تولید شوند.
- پیش‌نمایش JS هم باید داده و URL و تگ را امن تولید کند؛ سه آکولاد در template به معنی خروجی خام است.
- reset، خروجی خالی، تصویر ناموجود و ردیف‌های خراب داده قدیمی سناریوی عادی آزمون‌اند.

در کد بومی Heading نصب آزمایشی، مواردی مانند `get_settings_for_display()`، `add_inline_editing_attributes()`، `add_link_attributes()`، sanitization و قالب JS در کنار هم دیده شد. این زنجیره باید الگو باشد، نه فقط افزودن یک attribute به HTML.

نکته خواندن دقیق مثال‌ها: در نمونه صفحه Icons، نام کنترل ثبت‌شده و نام ارجاع JS یکسان نیست. در پیاده‌سازی واقعی باید نام‌ها یکسان و با نسخه نصب‌شده آزموده شوند. مستند رسمی مرجع API است، اما نمونه‌ها جای بررسی اجرایی و امنیتی کد محصول را نمی‌گیرند.

## ۹. یافته‌های ممیزی کد موجود

این یافته‌ها بر اساس بررسی منبع commit مبنا هستند؛ «بررسی نشد» یا «آزمون نشده» به معنی اثبات خرابی نیست.

| یافته | شاهد کد | پیامد / اصلاح لازم |
|---|---|---|
| کنترل‌های کامل‌تر به حالت custom محدودند | `Elementor_Widgets_Collection.php:44–46` و ثبت بخش‌های custom | کاربر نمی‌تواند همان طرح اصلی را با همه کنترل‌ها مدیریت کند؛ باید اتصال واقعی در طرح اصلی برقرار شود |
| تنظیمات اصلی با مدل مشترک رندر نمی‌شوند | همان فایل، `render():244–253`، مسیر Original در برابر Editable_Renderer | دو تجربه متفاوت؛ چیدمان اصلی نباید به یک استثنای فقط-متن تبدیل شود |
| متن اصلی از DOM استخراج و با path/baseline نگهداری می‌شود | `Inline_Editing.php:64–109` | لایه سازگاری برای حفظ ظاهر مفید بود، اما مدل معنایی مناسب برای همه محتوا/استایل/تکرارکننده نیست |
| فهرست متن‌های اصلی افزودن/حذف/تکثیر/مرتب‌سازی ندارد | `original_controls()` و `item_actions` | برای کارت‌ها و آیتم‌های واقعی، این فهرست جای Repeater محتوایی را نمی‌گیرد |
| قالب JS اختصاصی در کلاس‌های فعلی وجود ندارد | بررسی فایل‌های Widgets | برای قسمت‌های مناسب، قالب کلاینتی یا تصمیم روشن درباره رندر سمت سرور لازم است؛ این مورد به‌تنهایی اثبات نامعتبر بودن نیست |
| URL custom_attributes در دو renderer مصرف نمی‌شود | `Editable_Renderer::attrs()` و `Professional_Widget::link()` | گزینه‌های پنل و خروجی کامل هم‌ارز نیستند؛ API رسمی یا رفتار معادل کامل لازم است |
| تصویر عمومی فقط URL/alt را مستقیم چاپ می‌کند | `Editable_Renderer::image()` و خروجی تصاویر professional | امکانات اندازه attachment و تصویر Responsive باید تکمیل و آزموده شوند؛ روش URL ذاتاً ممنوع نیست |
| اسکریپت‌ها dependency خالی دارند، ولی hookهای Elementor/jQuery را به‌کار می‌گیرند | `Elementor_Loader::register_assets():55–58` و فایل‌های JS | ترتیب بارگذاری شکننده؛ dependency واقعی و lifecycle درست لازم است |
| JS فعلی init عمومی و data-ready دارد | `assets/widgets.js` و `assets/professional.js` | برای رفتارهای فعلی کار کرده، ولی جای پوشش چرخه تغییر، حذف، بازسازی و cleanup همه ویجت‌ها را نمی‌گیرد |
| Global defaults و Dynamic field coverage یکپارچه طراحی نشده | helperهای ثبت کنترل و کد پایه Elementor | بخشی از امکانات از هسته ارث می‌رسند؛ باید فیلدبه‌فیلد و بدون تغییر خودکار طراحی تعیین شوند |
| آزمون‌ها پوشش کامل شخصی‌سازی ندارند | `tests/RESULTS-2.7.0.md` و سناریوهای تست | ۱۳۹۶ assertion قبلی مساوی با اثبات رفتار همه کنترل‌های ۲۰ ویجت در طرح اصلی نیست |

موارد درست که باید حفظ شوند: API بومی Inline Editing، ذخیره واقعی در Elementor، escape/sanitization موجود، اصلاح زودهنگام شناسه‌های Repeater، scope بسیاری از استایل‌ها، عدم تغییر هسته Elementor، جداسازی افزونه قدیمی و عدم بازنویسی خودکار دمو.

## ۱۰. دامنه شخصی‌سازی برای هر ۲۰ ویجت

این جدول **معیار هدف** است، نه ادعای تکمیل امکانات موجود.

| ویجت | قرارداد محتوای لازم برای همان ظاهر فعلی |
|---|---|
| `zarincode_header` | لوگو/نام، منو یا منبع منو، CTA، جستجو، سبد/حالت رنگ، منوی موبایل و اجزای سبک هر بخش |
| `zarincode_footer` | ستون‌ها، عنوان و لینک‌ها، شبکه‌های اجتماعی، برند، اطلاعات تماس و کپی‌رایت |
| `zarincode_hero` | قطعه‌های عنوان تزئینی، توضیح، نشان و آیکون، دو CTA، تصویر/کارت معرفی، آمار و نشان‌های اعتماد |
| `zarincode_brands` | آیتم لوگو/تصویر/نام، لینک و تنظیمات واقعی حرکت/فاصله در صورت ارائه |
| `zarincode_services` | منبع دستی/پویا، کارت، آیکون/تصویر، عنوان، شرح، شماره/نشان و CTA |
| `zarincode_products` | query، کارت محصول، نشان‌ها و برچسب CTA؛ قیمت و موجودی واقعی از WooCommerce |
| `zarincode_portfolio` | query یا آیتم دستی، تصویر، عنوان، دسته/برچسب، لینک، فیلتر و متن حالت خالی |
| `zarincode_why_us` | مزایا، تصویر/ویدئو، آیکون، شمارنده، عدد/عنوان/پسوند و گزینه‌های واقعی انیمیشن |
| `zarincode_pricing` | دسته/تب، پلن، قیمت دستی، دوره، قیمت قبلی، مزایا، پلن ویژه و CTA |
| `zarincode_promo` | عنوان، شرح، نشان، CTA و اگر شمارش‌گر ارائه می‌شود حالت/زمان و متن‌های آن |
| `zarincode_steps` | مراحل واقعی با ترتیب قابل تغییر، شماره، آیکون، شرح و لینک |
| `zarincode_testimonials` | نام، سمت، تصویر، متن، امتیاز و تنظیمات چرخش تنها اگر واقعاً پیاده‌سازی شوند |
| `zarincode_blog` | query، اجزای کارت، عنوان/مقدمه/متا، متن ادامه مطلب و حالت خالی |
| `zarincode_page_section` | مدل صریح هر الگوی داخلی/حقوقی، بندها، FAQ و اطلاعات تماس؛ نه یک HTML کلی غیرقابل تفکیک |
| `zarincode_service_spotlight` | داده کامل کارت، تصویر/آیکون، نشان، مزایا و CTA با استایل واقعی اجزا |
| `zarincode_project_roadmap` | مرحله، شماره/برچسب، شرح، آیکون و CTA؛ ترتیب آزاد آیتم‌ها |
| `zarincode_seo_audit` | شاخص، شرح، امتیاز، مقیاس و نمایش عدد؛ داده نمونه با گزارش واقعی اشتباه نشود |
| `zarincode_before_after` | دو رسانه، alt، label، caption، موقعیت اولیه، کنترل مقایسه و استایل اجزا |
| `zarincode_team_experts` | تصویر، نام، سمت، معرفی، نشان و لینک‌های اجتماعی مستقل |
| `zarincode_service_matrix` | ستون/پکیج‌ها در محدوده طراحی‌شده، ردیف‌ها، عنوان ویژگی، مقدار هر سلول، قیمت نمایشی و CTA مستقل |

برای تمام موارد، Content/Style/Advanced، reset، حفظ داده، کنترل مناسب، خروجی امن و رفتار Responsive باید روی همان ظاهر اصلی آزموده شوند. هر گزینه شرطی باید واقعاً وضعیت خروجی را تغییر دهد.

### تفاوت مهم: «قابل تنظیم» با «عنصر مستقل قابل Drag»

یک composite widget استاندارد می‌تواند چند بخش با کنترل‌های مستقل داشته باشد، اما فرزندان HTML آن به‌خودی‌خود widget مستقل در Navigator نیستند. Repeater نیز یک درخت از ویجت‌های فرزند نیست.

اگر لازم است تصویر، عنوان، دکمه یا بخش داخلی جداگانه Drag/Drop و از Advanced خودشان مدیریت شوند، باید از Container و ویجت‌های واقعی یا قابلیت Nested مناسب نسخه هدف استفاده شود. اضافه‌کردن wrapper یا contenteditable این قابلیت را ایجاد نمی‌کند. ساختار رسمی Widget/Container این تفکیک را نشان می‌دهد [2](https://developers.elementor.com/docs/data-structure/widget-element/).

برای زرین کد: اجزای عمومی در قالب‌های جدید می‌توانند با Container و ویجت‌های بومی ساخته شوند؛ ابزار اختصاصی باید فقط رفتار/طرح اختصاصی را پوشش دهد. پشتیبانی Nested نباید با تکیه کورکورانه به کلاس‌های داخلی یا API آزمایشی تحمیل شود. ظاهر دمو باید حفظ شود و داده صفحات موجود خودکار تبدیل نشود.

## ۱۱. معماری اصلاحی و حفظ سازگاری

### اولویت P0: مدل و خروجی واقعی

1. برای هر ویجت، فهرست تمام اجزای قابل تنظیم و mapping «کنترل → settings → خروجی/selector → تست» تهیه شود.
2. محتوای پیش‌فرضِ همان طراحی اصلی به داده ساختاریافته تبدیل شود؛ markup و کلاس‌های مورد نیاز ظاهر اصلی حفظ شوند.
3. `render()` همان markup را مستقیماً از تنظیمات بسازد. حالت original نباید کنترل‌های اصلی محتوا/استایل را از کار بیندازد.
4. استخراج DOM فقط می‌تواند ابزار مهاجرت یک‌باره/سازگاری داده قدیمی باشد، نه معماری دائمی ویرایش.
5. نام ویجت‌ها و کلیدهای ذخیره‌شده بدون برنامه مهاجرت عوض نشوند. ویرایش‌های `original_texts*` نسخه ۲٫۷ نباید دور ریخته شوند.

### اولویت P1: تجربه ویرایش

6. برای محتوای مناسب، `content_template()` با مدل و کلیدهای متناظر ایجاد شود؛ برای queryها، server rendering درست حفظ شود.
7. Inline Editing مستقیم در محل رندر تعریف شود؛ نیازی به جست‌وجوی کلاس‌ها در HTML خروجی برای پیدا‌کردن فیلدها نباشد.
8. کنترل‌های رسانه/لینک/آیکون، responsive، global و dynamic با قرارداد کامل تکمیل شوند.
9. Handlerهای تعاملی، dependencyها و teardown استاندارد شوند؛ درج تازه، Duplicate و Undo نباید به reload نیاز داشته باشد.

### اولویت P2: مهاجرت و تحویل

10. آزمایش روی staging و نسخه‌های هدف؛ snapshot بصری desktop/tablet/mobile و حالت تاریک از طراحی محبوب کاربر.
11. تبدیل داده قدیمی idempotent و قابل بازگشت طراحی شود. هیچ import، جایگزینی برگه یا بازچینی خودکاری هنگام فعال‌سازی/ارتقا انجام نشود.
12. standalone core و EmbeddedCore همسان، بسته‌ها بازتولید و نتیجه هر ویجت جداگانه ثبت شود.

این مسیر «بازطراحی ظاهری» نیست. اصل تصمیم این است که طرح مورد پسند کاربر تبدیل به defaults و template داده‌محور شود، نه اینکه برای دسترسی به کنترل‌های واقعی مجبور به انتخاب طرح عمومی دیگری باشد.

## ۱۲. معیار پذیرش جدید؛ نه صرفاً شمارش کنترل‌ها

هر کنترلِ ارائه‌شده باید دست‌کم یک آزمون مثبت تغییر و یک آزمون reset/خالی متناسب داشته باشد. موارد زیر روی تمام ویجت‌ها و چیدمان‌های پشتیبانی‌شده لازم‌اند:

- تغییر متن از پنل؛ تطابق preview و frontend بعد از publish/reload.
- تغییر متن از canvas؛ برگشت تغییر در پنل و ذخیره در همان settings.
- تغییر رنگ/فونت/اندازه/فاصله واقعی و اندازه‌گیری computed style، به‌ویژه در طرح اصلی.
- چند نمونه از یک ویجت در صفحه و اثبات نشت‌نکردن استایل/رویداد.
- افزودن، حذف، تکثیر و مرتب‌سازی Repeater؛ تطابق محتوا، Inline key و `_id` پس از تغییر ترتیب.
- تغییر تصویر/آیکون/SVG و تنظیمات لینکِ واقعاً ارائه‌شده، از جمله custom attributes مجاز.
- Desktop، Tablet، Mobile و یک breakpoint اضافی؛ RTL و در صورت پشتیبانی LTR.
- Undo/Redo، Duplicate widget، Copy/Paste Style و Reset؛ حفظ داده در export/import.
- درج ویجت تازه در editor؛ تغییر کنترل، rerender، حذف و درج دوباره، بدون listener/timer تکراری.
- حالت بدون تصویر، بدون آیتم، بدون نتیجه query و داده قدیمی ناقص.
- Dynamic Tags/Global style در محیط‌هایی که قابلیتشان موجود است؛ برچسب «آزمون نشده» در غیر این صورت.
- تطابق بصری با طرح اصلی پیش از هر ویرایش؛ تغییر ظاهر تنها به اندازه انتخاب صریح کاربر.
- نبود Warning مرتبط با داده زرین کد، نبود خطای JS، نبود تغییر در هسته Elementor.

موفقیت تنها در یک سناریوی کلیک/انتشار برای Hero و یک کارت، این معیار را کامل نمی‌کند. شماره نسخه یا بسته جدید فقط بعد از پاس‌کردن این ماتریس باید به‌عنوان «شخصی‌سازی کامل» معرفی شود.

## ۱۳. دامنه و منابع مطالعه

بخش‌های زیر مستقیماً بررسی شدند؛ منابع غیررسمیِ نتایج جست‌وجو مبنای قرارداد این سند قرار نگرفتند:

- [فهرست مستندات رسمی](https://developers.elementor.com/docs/)
- [Widgets](https://developers.elementor.com/docs/widgets/) و [Widget Structure](https://developers.elementor.com/docs/widgets/widget-structure/)
- [Widget Rendering](https://developers.elementor.com/docs/widgets/widget-rendering/) و [Widget Settings](https://developers.elementor.com/docs/widgets/widget-settings/)
- [HTML Attributes](https://developers.elementor.com/docs/widgets/rendering-html-attribute/) و [Inline Editing](https://developers.elementor.com/docs/widgets/rendering-inline-editing/)
- [Rendering Media](https://developers.elementor.com/docs/widgets/rendering-media/) و Rendering Repeaters [5](https://developers.elementor.com/docs/widgets/rendering-repeaters/)
- مثال پیشرفته کامل، PHP و JS [1](https://developers.elementor.com/docs/widgets/advanced-example)
- [Editor Controls](https://developers.elementor.com/docs/editor-controls/) و [Controls](https://developers.elementor.com/docs/controls/)
- [Repeater Control](https://developers.elementor.com/docs/editor-controls/control-repeater/)، [Icons](https://developers.elementor.com/docs/editor-controls/control-icons/) و URL [14](https://developers.elementor.com/docs/editor-controls/control-url)
- CSS Selectors [18](https://developers.elementor.com/docs/editor-controls/selectors)، [Responsive](https://developers.elementor.com/docs/editor-controls/responsive-control/) و [Global Style](https://developers.elementor.com/docs/editor-controls/global-style/)
- [Dynamic Content](https://developers.elementor.com/docs/editor-controls/dynamic-content/)، [Conditional Display](https://developers.elementor.com/docs/editor-controls/conditional-display/) و Frontend Available [3](https://developers.elementor.com/docs/editor-controls/frontend-available)
- [Widget Dependencies](https://developers.elementor.com/docs/widgets/widget-dependencies/) و [Output Caching](https://developers.elementor.com/docs/widgets/widget-output-caching/)
- [Data Structure](https://developers.elementor.com/docs/data-structure/)، Widget Element [2](https://developers.elementor.com/docs/data-structure/widget-element/)، Repeaters [7](https://developers.elementor.com/docs/data-structure/repeaters/) و [Responsive Data](https://developers.elementor.com/docs/data-structure/responsive-data/)
- [Deprecations](https://developers.elementor.com/docs/deprecations/) برای پرهیز از اتکا به API حذف‌شدنی و لزوم آزمون debug.

علاوه بر صفحات وب، کد محلی Heading و کلاس‌های پایه/کنترل‌های Elementor نصب آزمایشی ۴٫۳٫۵ با کد زرین کد مقایسه شد. برای بازنویسی، نسخه دقیق API و قابلیت‌های فعال باید مجدداً ملاک آزمون باشند؛ نام «نسخه ۴» مجوز فرض سازگاری خودکار میان مسیرهای متفاوت توسعه نیست.

**خلاصه اجرایی:** نقص اصلی در تعداد کنترل‌ها یا نبود یک attribute نیست؛ در این است که طراحی اصلی هنوز منبع داده کنترل‌محور و قرارداد کامل شخصی‌سازی ندارد. اصلاح باید از مدل داده و renderer همان طرح شروع شود، نه با یک لایه دیگر روی HTML آماده.
