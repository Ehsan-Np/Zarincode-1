# 2026-11-02 — v4.5.0 demo rebuild evidence

Fresh WP + Elementor 3.32.4 + WooCommerce env, Demo_Seeder::seed_all() from
scratch, fa_IR locale, headless-Chromium full-page captures (CDP, lazy-primed):

- v-home.png — full homepage RTL: header/hero flipped correctly, service cards
  with unique English sub-labels, single pricing block (tabs + cards + matrix),
  testimonials section, Jalali blog dates («۱۱ آبان ۱۴۰۵»), promo + footer.
- v-pricing.png — pricing page: single h1 + one plan grid (duplicate static grid
  removed), comparison matrix, footer.
- v-services.png — services page (bento, journey, steps, roadmap) unchanged + RTL.
- v-product-single.png — product page with «… تومان» prices (currency TOMAN,
  number-first order), no $ anywhere.
- v-blog.png — blog grid, Jalali dates, author احسان نادری پناه, no Hello world.
- v-home-mobile.png — full homepage at 390px (scrollWidth == innerWidth, no
  horizontal overflow; verified programmatically for home/contact/product/
  pricing/services).
- v-contact.png — contact page with brief form.

Checks: php -l on every touched file; zero PHP warnings in server log; visitor
chat start→send→poll round-trip; DOM assertions (dir=rtl lang=fa-IR, unique
service labels, pricing widgets=0 duplicates, toman prices, 0$).
