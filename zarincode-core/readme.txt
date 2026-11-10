=== Zarincode Core ===
Contributors: zarincode
Tags: elementor, agency, woocommerce, rtl
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 4.15.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Companion engine for the Zarincode theme, with native design widgets, independent
Hero components, six agency tools, client logos, pricing cards and Iranian credentials. Native content, icons, media, URL objects, repeaters and scoped component styles
preserve the original layout. The theme includes a synchronized engine for legacy-plugin isolation.
The original-layout runtime renders native settings without PHP DOM.
The legacy custom-layout inline adapter may use DOM; panel controls remain available. Elementor is required for widget editing.
WooCommerce is needed for shop functionality. No automatic demo imports.

== Installation ==
Back up and test on staging. Install zarincode-core.zip through WordPress Plugins.
Update the theme to the same release. Regenerate Elementor CSS and clear caches.
Do NOT reimport the demo when upgrading an existing site.

== Changelog ==

= 4.15.0 =
* **صفحات سایت دیگر «یک ویجت برای هر بخش» نیستند.** درخت هر صفحه از ویجت‌های بومی المنتور ساخته می‌شود: Containerهای تودرتو + Heading، Text Editor، Button، Image، Icon Box، Icon List و Divider. صفحهٔ اصلی ۱۰۱ Container و ۲۰۲ ویجت بومی دارد؛ هر تیتر، پاراگراف، دکمه، تصویر و کارت در Navigator جداگانه دیده می‌شود و می‌توان آن را انتخاب، جابه‌جا، تکثیر، حذف یا استایل‌دهی کرد. مجموع اسناد صفحات: ۲۲۵ Heading، ۲۰۷ Text Editor، ۱۰۷ Button، ۶۱ Icon Box، ۴۱ Icon List، ۴۰ Image — و صفر ویجت ترکیبی.
* `Elementor\Page_Composer`: سازندهٔ اسناد بومی (`widget()`, `container()`, `heading()`, `text()`, `button()`, `image()`, `icon_box()`, `icon_list()`, `badge()`, `divider()`, `section()`, `head()`, `grid()`, `card()`, `pricing()`, `home()`, `inner()`) به‌همراه `Demo\Page_Copy` که متن فارسی هر صفحه را از چیدمان جدا نگه می‌دارد.
* همهٔ صفحات بازنویسی شدند: صفحهٔ اصلی (۱۲ بخش)، خدمات، نمونه‌کارها، درباره ما، تماس، تعرفه، اعتبارنامه‌ها، سئو، فناوری‌ها، مجله و ۶ سند حقوقی (هر بند یک عنوان + متن قابل ویرایش با فهرست پیوندی).
* تصاویر دمو حالا پیوست واقعی کتابخانهٔ رسانه‌اند (نه وابسته به پوشهٔ قالب) تا از پنل المنتور قابل تعویض باشند؛ جایگزینی دوباره فایل را تکرار نمی‌کند.
* `Demo_Seeder` پس از نوشتن `_elementor_data` کش المنتور را باطل می‌کند (`_elementor_element_cache`، `_elementor_css`، `_elementor_page_assets`). پیش‌تر یک ایمپورت دوباره HTML قدیمی را نشان می‌داد و کلاس‌های Container به‌ظاهر «گم» می‌شدند.
* Kit المنتور با پالت برند هم‌تراز می‌شود: Primary `#2A2C31`، Accent `#FDD05B`، متن `#4B5563`، فونت Vazirmatn. رنگ‌هایی که مالک سایت تغییر داده باشد دست‌نخورده می‌ماند.
* `assets/css/eb.css`: لایهٔ ظاهر صفحات بومی. آخرین استایل‌شیت صفحه است (اولویت ۹۹۹) و فقط به کلاس‌های `zc-eb-*` اعمال می‌شود، پس به ویجتی که کاربر خودش می‌گذارد کاری ندارد.
* صفحهٔ `shop` دیگر سند المنتوری نمی‌گیرد: مسیر `/shop/` آرشیو ووکامرس است و قالب آرشیو رندر می‌شود؛ سند قبلی هرگز دیده نمی‌شد.
* حذف کد مرده: `Demo_Seeder::make_elementor_section()`، `section_title()`، `demo_technologies()`، `pricing_body_sections()`، `Service_Demo::legal_rows()/legal_nav()` و فایل `fixtures/pricing-widget-settings.json`.
* صادقانه: هدر سایت همچنان یک ویجت حالت‌دار (`zarincode_header`) است — کشوی موبایل، شمارش سبد، جست‌وجوی زنده و کلید حالت تیره در آن زنده‌اند؛ فوتر و تمام صفحات بومی‌اند.

= 4.14.0 =
* Icons: `Icon_Compatibility` rewrites every 6.x/7.x icon name in a widget value back to the Font Awesome 5.15.3 vocabulary Elementor resolves against, and repairs icon names already stored in `_elementor_data` once (`repair_stored_icons()`). Elementor's inlined-SVG pass logs «Undefined array key "shield-halved"» for every 7.x name it cannot find in its own FA 5.15.3 JSON; the log is now silent while the rendered glyph is still Font Awesome 7.
* Navigation: `Native_Design` marks the current and ancestor links at render time (`is-current`, `is-current-ancestor`, `aria-current="page"`), so headers and footers authored as Elementor documents show where the visitor is.
* SEO: pages accept `_zc_seo_title` / `_zc_seo_description`; the shop archive resolves to the WooCommerce shop page for its metadata; an Elementor page's copy is read from `_elementor_data` when `post_content` is empty, so each page gets its own description instead of the site slogan.
* Demo: `seed_seo_meta()` writes a Persian title and description for the 15 demo pages, `import_summary()` reports the real counts, and the import banner no longer claims fixed numbers.

= 4.13.1 =
* Ships with theme 4.13.1 (no veil above the footer). `Demo_Seeder::ensure_permalinks()` switches a fresh install from «Plain» to `/%postname%/` during the demo import so every demo URL resolves; an owner-chosen structure is kept. `HF_Builder` now stamps every document it generates with `_zc_hf_builder` and rebuilds it when the stamp is older than the current tree — but only while `_zc_hf_generated` is set and `_zc_hf_user_edited` is not, so an upgraded site receives the current design on the next `ensure()` and a footer the owner has edited in the panel is never rewritten.

= 4.13.0 =
* Demo importer writes Flexbox Containers, not Sections: `Demo_Seeder::make_elementor_section()` emits `elType: container` with `content_width: full`, explicit zero padding and a Persian `_title` taken from the widget's own `get_title()`. The dead `Service_Demo::section()` helper (still building sections) is gone, and `seed_all()` clears Elementor's CSS cache again, and the demo import writes `woocommerce_coming_soon = no` unconditionally — the option is created by WooCommerce after the import on a fresh install, so the old guarded write left the site showing Woo's coming-soon page.
* Ships with theme 4.13.0. `HF_Builder::footer_tree()` is a pure Container tree: the plate, the CTA card, the four columns and the bottom bar are Containers, and everything inside them is a stock Elementor widget (heading, text-editor, button, icon-list, icon, `zarincode_social_profiles`). 17 Containers / 23 widgets, five levels deep, no raw HTML.
* `container()` writes `padding` explicitly (Elementor's nested default of 10px silently shrank every column), supports per-device `wrap_t` / `wrap_m` through `flex_wrap_tablet` / `flex_wrap_mobile`, and keeps the responsive width controls (`width`, `width_tablet`, `width_mobile`) in the same place the editor stores them.
* `typo()` accepts `size_t` / `size_m`, so every heading and the CTA badge carry real per-device font sizes instead of a CSS clamp in the theme.
* New `docs/standards/elementor-footer-architecture.md`: the standards this tree follows (Elementor container schema, control naming, responsive control pairs, editability rules, CSS scoping, RTL logical values) with the sources that were read before coding.
* Verified: `php -l` clean, `ensure(true)` + `elementor flush-css` run without a warning, the front end renders the plate, and `debug.log` stays at the two known `WP\MCP` deprecations of the QA install.

= 4.12.0 =
* Ships with theme 4.12.0. `HF_Builder::footer_tree()` is rebuilt from native widgets so the Elementor footer reproduces the approved 4.9.0 design exactly (CTA card, brand column, two link lists, credential chips, bottom bar) while every element stays editable. `HF_Builder::header_tree()` now returns the theme's own designed header widget, so a rebuild never redraws the header.
* `ensure()` adopts an existing `zc_hf_template` with `_zc_hf_type` instead of creating a duplicate document; the legacy-tree upgrade only touches the footer, never the header.
* Font Awesome icon names in the generated trees use FA5 names (`fa-balance-scale`, `fa-shield-alt`, `fa-undo`, `fa-globe`), removing the Elementor «Undefined array key» warnings.

= 4.11.0 =
* Ships with theme 4.11.0. The header/footer builder now creates real `wp-post` Elementor documents (fully editable), adds the «شبکه‌های اجتماعی قابل ویرایش» widget, never overwrites an owner's edit, and fixes the PHP 8.1+ `add_submenu_page( null )` deprecation in the demo importer.

= 4.10.1 =
* No API change. Ships with theme 4.10.1 (header/footer geometry revert, footer plate colour, icon sizing untouched, icon fonts render again).

= 4.10.0 =
* New `Security\Hardening`: error-display shield (works even when the host forces `display_errors On`), `scrub()` path sanitizer used by every message returned to a browser, security headers, XML-RPC off, author/REST user enumeration blocked, `debug.log` guard, admin audit panel.
* Elementor editor speed: one scoped widget per inner-page model (page controls payload 2,457 KB → 95 KB); legacy v2.6 layout controls are registered only for elements that actually use them (editor.js saves and reloads when the mode changes).
* Legal/inner pages: designed document layout with real demo clauses (hero, table of contents, numbered cards, sidebar, CTA) instead of bare heading/text widgets.
* Demo import: 10 WooCommerce products (type/price/stock/visibility/image fixed), 10 blog posts, coming-soon disabled, shop page wired.
* Widget skin: full Elementor stock-widget skin + Vazirmatn enforced inside widget containers.

= 4.9.1 =
* Companion release for theme 4.9.1 — no functional change to the core engine; the version is bumped so WordPress reports the pair as one update.

= 4.9.0 =
* Homepage integration: every homepage section can now be fed by real site content (services CPT, WooCommerce
  products, product reviews, posts, projects) with a safe fallback to the configured cards when a site is empty.
* Services and testimonials gained a «منبع داده» control; testimonials support a real client photo and author
  avatars, plus optional links per quote.
* Pricing: four working tracks (design / development / SEO / content) as an accessible tab list, 12 initial plans,
  per-plan «مسیر» field so the Elementor panel stays the single source of truth.
* Ticker: seamless horizontal marquee, links per item, pause on hover/focus and a visible pause button.
* Header: current-menu state resolved from the request path with aria-current="page"; footer now shares the
  header's dark plate, gold hairline and hover language so the site reads as one piece.
* Icons: one centred box for every icon container (grid + place-items:center); keyboard focus parity everywhere.
* Brand bar: lives off the «کارفرما» field of published projects (unique clients, each logo linking to its project),
  with an optional auto counter; the hero trust cards can show the real project count and average review rating.
* Media: product, project and article cards use the real featured image when the post has one, with the generated
  gradients kept as the fallback; the hero code card is editable (function signature + one row per line).
* Ticker terms resolve to a matching service / product / project / article before falling back to site search.
* Touch: 34-44px hit areas for the ticker, chips, tags and card CTAs on small or coarse pointers; no horizontal
  overflow at 390px.
* Performance: the homepage title index (22 queries), the average product rating and the partner brands are cached
  for 12 h in a non-autoloaded transient + object cache, flushed on content, term, review and theme changes.
  Front page: 182 queries before, 152 warm (196 on the first request that builds the caches); per section warm:
  hero +3, partners +1, services +3, products +12, portfolio +9, testimonials +3, blog +16, why/steps/pricing +0.
* Colour continuity: one page-wide 180deg backdrop (stylesheet-owned) from the header edge to the footer edge, a
  full-bleed footer plate that rises out of it through a 9rem veil with no hairline or drop shadow, and the colour
  switcher no longer repaints a fixed diagonal on body (light and dark verified).
* Accessibility: axe-core 4.10.2 (WCAG 2.0/2.1 A + AA + best practice) reports 0 violations on the homepage at
  1366/768/390px and on the 404 and search templates. Muted text token #84888F -> #5F6369 (3.55:1 -> 5.6:1 on
  white, >=4.8:1 on the pastel cards), struck prices #A9AEB8 -> #6E7278, card headings h4 -> h3 so the outline is
  h1 hero > h2 section > h3 card, star ratings exposed as role="img", the duplicated ticker track taken out of the
  tab order, the pricing matrix keyboard-scrollable, the announcement bar a labelled region and the footer CTA /
  column headings h2/h3. Card typography is unchanged by the heading fix.

= 4.8.0 =
* New «سئو و هوش مصنوعی» admin module (two screens): search-engine indexing + AI-visibility scanner.
* Indexing: robots.txt groups for 18 AI crawlers, verification metas (Google/Bing/Yandex/Baidu/Pinterest, tag or token),
  snippet controls, selective noindex, sitemap advertising, sanitised custom head/robots code.
* IndexNow: auto-generated key served by a virtual route, queued submissions via WP-Cron, response history with codes.
* llms.txt / llms-full.txt: curated, cached (12 h), rebuilt on content change, served as plain text via rewrite.
* AI scan: ~43 weighted checks in five groups, per-item Persian fix hints, live preview of how models read the site.
* Zero front-end cost: one autoloaded option, no extra queries, no files, no CSS/JS; all meta suppressed when an
  SEO plugin owns the page. Companion theme 4.8.0 fixes the front-page document title.

= 3.7.0 =
* Theme: bespoke native Woo account portal, configurable header account URL, fixed viewport contact controls.
* Continuous article-rating stars, compact sharing, actual Woo product gallery and linked responsive product artwork.
* Refined editable customer-logo carousel and brand wall; locale-aware gallery direction.

= 3.6.0 =
* Theme: native threaded discussion across products, posts, services, projects and pages.
* Theme: unified header/content/footer geometry and new Share Studio interface.

= 3.5.0 =
* Explicit-import-only service-first copy, six long articles and editable legal drafts.
* Editable social icons, physical article sidebars and moderated article ratings (theme).
* Shared and per-product warranty/FAQ, without fabricated product promises (theme).

= 3.4.0 =
* Restore original homepage card design; six uniform opt-in section-level skins.
* Editorial reading layout, server-side TOC with 1–4 columns, illustrated adjacent/related cards.
* Commerce-specific author/review design; Iranian social icons and brand profile links.
* Native Iranian credentials widget: strict HTML compiler, no arbitrary provider scripts.

= 3.3.0 =
* Shared single-content discussion/share/author/navigation/related tools; WooCommerce native reviews.
* Uploaded client-logo carousel and independent pricing-plan cards.
* Distinct native card skins, in-place testimonial photos, linked technology cards.
* Purposeful demo distribution and explicit backup-first layout relocation tool.

= 3.2.0 =
* Three independent hero widgets with explicit backed-up conversion.
* Published content queries, root product-category filters and standard featured images.
* Unlimited per-plan icon lists, customer media, Persian labels and complete demo usage.

= 3.1.0 =
* Safe Font Awesome SVG data validation, native editor component filtering and tabs.
* Smaller controls payload; preserves original design and saved field identities.
= 3.0.0 =
Settings-driven original layouts (23 models), native repeaters and component styles;
legacy saved-text migration; scoped frontend handlers; query sources; contact-form shortcode.
= 2.7.0 =
Native inline text editing on original and custom layouts; six optional agency
widgets; optional professional Elementor JSON kit; optional upstream Xpro installer.
No upstream Xpro runtime or assets are bundled; see includes/Widgets/THIRD-PARTY-NOTICES.md.

== Documentation ==
https://github.com/Ehsan-Np/Zarincode-1/blob/main/RELEASE-3.2.0.md
