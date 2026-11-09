=== Zarincode Core ===
Contributors: zarincode
Tags: elementor, agency, woocommerce, rtl
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 4.9.0
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
