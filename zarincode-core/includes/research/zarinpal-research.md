# ZarinPal — تحقیق و نیازسنجی (بازنگری ۲۰۲۶-۱۱-۰۱)

حقیقت‌های کلیدی از مستندات رسمی (docs.zarinpal.com):

| موضوع | نتیجه |
| --- | --- |
| درخواست پرداخت v4 | `POST https://api.zarinpal.com/payment/request.json` (MerchantId, Amount تومان, CallbackURL, Description, ...) |
| هدایت کاربر | `GET https://api.zarinpal.com/payment/start/{Authority}` یا StartPay سندباکس |
| تأیید تراکنش v4 | `POST https://api.zarinpal.com/payment/verify.json` با Authority + Amount |
| استعلام | `Authority` قابل استعلام است؛ استعلام بدون تأیید، تأیید را باطل نمی‌کند |
| سندباکس | مرچنت تاب‌عده `zarin.example`؛ StartPay: `https://sandbox.zarinpal.com/pg/StartPay/`؛ Verify: `https://sandbox.zarinpal.com/pg/Verify`؛ بدون هزینه |
| REST v1 | `api.zarinpal.com/v1` — `PaymentRequest.json` / `PaymentVerification.json` / `Unverified.json` با `Bearer` |
| تأیید هم‌زمان | درگاه روی CallbackURL تأیید و تراکنش را هم‌زمان ارسال می‌کند — باید تأیید شود وگرنه Refund می‌شود |

نکات پیاده‌سازی هسته:
- مبلغ همیشه سمت سرور محاسبه و در Verify دوباره ارسال می‌شود (مطابق مستندات).
- `Amount` بر حسب «تومان» است (نه ریال).
- سندباکس برای محیط توسعه؛ تولید فقط با مرچنت واقعی زرین‌کد.
- خطاهای `101`(تأییدشده)، `102`(اتمام اعتبار)، `104`,`105` و ... در پیام کاربر نمایش داده شوند.
