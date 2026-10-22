# بررسی معماری ادیتور — ۳٫۱٫۰

این بررسی مبنای پیاده‌سازی مستقل زرین کد است؛ کد PHP/JS افزونه‌های زیر به پروژه منتقل نشده است.

## منابع بررسی‌شده

- [مقاله معرفی‌شده](https://elementor.com/blog/custom-wordpress-widget/): تفکیک منبع داده، ورودی‌های ویجت، fallback، امنیت خروجی و وابستگی‌ها. برای API، مستندات فعلی بر نام قدیمی `_register_controls()` ذکرشده در مقاله مقدم است؛ اعداد تبلیغاتی مقاله بنچمارک پروژه نیستند.
- [نمونه پیشرفته رسمی](https://developers.elementor.com/docs/widgets/advanced-example/) و [رندر Repeater](https://developers.elementor.com/docs/widgets/rendering-repeaters/): کنترل‌های بومی، عنوان ردیف، render از settings و حفظ هویت رکوردها.
- [وابستگی‌های ویجت](https://developers.elementor.com/docs/widgets/widget-dependencies/): تفاوت دارایی frontend/preview با اسکریپت پنل editor؛ اسکریپت فیلتر پنل از hook ادیتور بارگذاری شده است.
- [Popover](https://developers.elementor.com/docs/editor-controls/control-popover-toggle/): ابزار معتبر خلوت‌سازی؛ در این اجرا برای دسترسی واضح‌تر، زبانه و فیلتر اجزا انتخاب شد، نه پنهان‌کردن داده با condition.
- [Xpro](https://github.com/wpxpro/xpro-elementor-addons)، commit `7f2457547708ccf02623d15dec79ed9fcf211edc`، header 1.0.6؛ کنترل‌های ویجت‌ها و Woo Product Grid بررسی شدند.
- [Essential Addons Lite](https://wordpress.org/plugins/essential-addons-for-elementor-lite/) 6.8.7، از جمله `includes/Elements/Info_Box.php`.
- [Happy Addons](https://wordpress.org/plugins/happy-elementor-addons/) 3.50.1، از جمله خانواده Card.
- کد واقعی Elementor 4.3.5: `controls-stack.php`، `icons.php` و `font-icon-svg/font-awesome.php`.

## نتیجه مقایسه و تصمیم اجرایی

الگوی مشترک این خانواده افزونه‌ها استفاده از Widget_Base، بخش‌های محتوایی/استایل، MEDIA/ICONS/URL، Repeater برای مجموعه و رندر از تنظیمات ذخیره‌شده است. جایگزینی پیش‌فرض یعنی خواندن مقدار کاربر از settings؛ افزودن صرفِ کلیک یا یک فرم سفارشی جدا از طرح کافی نیست. در زرین کد، موتور native همین اتصال را برای طرح اصلی نگه می‌دارد؛ پیش‌فرض تنها مقدار اولیه است، نه HTMLی که دوباره روی محتوای کاربر نوشته شود.

شرط‌های Elementor در `get_active_settings()` می‌توانند فیلد نامرئی و فرزندان Repeater را از داده فعال حذف کنند. بنابراین انتخاب جزء/جستجو در این نسخه **فیلتر DOM** است؛ منبع دستی/پویا و نمایش واقعی اجزا همچنان تنظیمات محتوایی‌اند. شناسه‌ها ثابت‌اند تا به‌روزرسانی محتوای قبلی را قطع نکند.

ثبت هزاران فیلد غیرقابل‌انتخاب ویدئو برای گروه پس‌زمینه‌ای که فقط classic/gradient می‌پذیرد سودی ندارد. حذف فقط همین فیلدها حجم داده را کم کرد؛ گروه‌های عمومی برای حالت‌های قدیمی حذف نشده‌اند. cache مدل محدود به درخواست است تا داده اختصاصی یک ویجت وارد schema مشترک نشود.

در Elementor، نام کتابخانه/آیکن ناسازگار ممکن است داده null را به مدیر SVG برساند. قبل از render و صدور تنظیمات ویجت، فقط فیلدهای ICONS ثبت‌شده نرمال می‌شوند؛ داده دلخواه نامرتبط بازنویسی نمی‌شود. لایه ایمنی جایگزین نصب سالم Elementor یا پاک‌سازی SVG نیست.
