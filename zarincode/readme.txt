=== Zarincode ===
Contributors: zarincode
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 4.12.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: e-commerce, custom-background, custom-colors, custom-header, custom-logo, custom-menu, editor-style, featured-images, flexible-header, full-width-template, microformats, post-formats, rtl-language-support, sticky-post, theme-options, threaded-comments, translation-ready, blog, portfolio

Professional Elementor-based theme for digital agencies and product stores, built for Persian (RTL) sites. Ships with the companion plugin «Zarincode Core» (26 Elementor widgets, service and project post types, header/footer builder, WooCommerce integration, demo importer and Iranian legal pages).

== Description ==

* Homepage built from real site content: products from WooCommerce, services from the `zc_service` post type, case studies from `zc_project`, articles from posts, client quotes from real product reviews.
* Every homepage section is an Elementor widget with a data-source switch and a manual fallback, so the layout works on an empty site and turns dynamic as content is added.
* Uniform interaction layer: one hover/focus language for cards, buttons, links, chips, menus, tables and the client bar.
* Accessible by default: axe-core reports no violations on the homepage at 1366, 768 and 390 px.
* Redux options panel (single entry), WordPress and Elementor coding standards, no automatic demo import.

Companion plugin required: Zarincode Core (same version number).

== Installation ==

1. Upload the `zarincode` folder to `/wp-content/themes/`, or install the zip from Appearance → Themes → Add New → Upload Theme.
2. Install and activate the companion plugin `zarincode-core.zip`.
3. Install Elementor (required for widget editing) and WooCommerce (needed for the shop).
4. Activate the theme, then open Appearance → Customize / the theme panel and set your brand colours, phone, developer name and legal page links.
5. Optional: import the demo content from the theme panel on a fresh staging site only. Do not import the demo over an existing site.

== Frequently Asked Questions ==

= Does the homepage need demo data? =
No. Every section falls back to the cards you configure in the Elementor panel; as soon as the matching content exists, the section reads it from the site.

= Can I use the theme without WooCommerce? =
Yes. Product sections then render the manual cards or stay hidden, and the rest of the site works normally.

== Copyright ==

Zarincode bundles Font Awesome Free (CC BY 4.0 icons, SIL OFL 1.1 fonts, MIT code) and Vazirmatn (SIL OFL 1.1). Third-party notices are listed in the companion plugin.

== Changelog ==

= 4.12.0 =
* Footer design is the 4.9.0 footer again, pixel for pixel — and still fully editable in Elementor. The 4.11.0 experiment (a generic Elementor document footer) is withdrawn: it changed the plate, the CTA card, the columns, the chips and the bottom bar. `assets/css/hf-footer.css` carries the 4.9.0 rules verbatim (plate `#2A2C31`, radius 2rem, `3rem 2.25rem 2rem`, the accent glow inside the top edge, CTA card `rgba(255,255,255,.05)` + 1px `rgba(255,255,255,.08)` + radius 1.5rem, four columns `1.6fr 1fr 1fr 1.2fr` with `2.25rem` gaps, gold dot titles, `#9CA3AF` links that turn `#FDD05B` with `translateX(-4px)`, credential chips, `.zc-footer-bottom` bar, and the 1099px/767px stacking rules).
* The design is applied to Elementor markup instead of coded markup: the footer document is built from native widgets — CTA card (badge heading, H2, direct-call button, order button), brand column (monogram, name, tagline, about text, contact icon-list, socials widget), services and legal icon-lists, credential buttons, and the two bottom-bar texts. Every text, link, icon, button and social entry is editable in the panel; the stylesheet only dresses the widgets.
* `hf-footer.css` is scoped to `.zc-footer-host` (the landmark `footer.php` prints around the Elementor footer document) and to the widgets inside it, so it cannot reach the header, the content or any other Elementor page. It targets the widget root as well as `.elementor-widget-container`, and carries four classes per selector, so it wins on specificity — not on order — on both Elementor 3.x and 4.x.
* Verified parity at 1440px: `/tmp/base_footer.png` (4.9.0, 1031.30px including the 9rem lead-in) vs the Elementor footer (866.83px + the same 9rem lead-in), same colours, same gradient, same radii, same icons, same RTL order. Mobile at 390px stacks exactly like 4.9.0.
* Footer template adoption: `ensure()` adopts an existing `zc_hf_template` carrying `_zc_hf_type` instead of creating a second document, so an install never ends up with two footers. QA: the demo template 13 is adopted, no duplicate is created.
* The header is left alone: it ships as the theme's own designed header widget inside a real Elementor document and is never re-drawn by the builder, so updating cannot change the approved header.
* Verified: `/` → 200 with `footer.zc-footer-host`, Elementor editor for the footer document → 200 `elementor-editor-active`, a saved edit appears on the front end immediately and survives `ensure()`, and `debug.log` stays empty (the `Cannot redeclare control with same name "count"` notices and the Font Awesome `Undefined array key` warnings of 4.11.0 are gone).

= 4.11.0 =
* Header and footer: the approved design is back, byte for byte — the theme now paints NOTHING inside `.zc-hf`. The whole 4.10.0/4.10.1 experiment (footer plate override, `margin-top`, `overflow`, gold hairline, rounded CTA card, link dots, icon-box boxes, header plate) is gone; the header/footer look is exactly the Elementor document. Measured: header 227.56px, footer 694.72px (4.9.1: 228.19 / 691.84).
* Widget skin is declarative only: every rule in `widgets.css` now lives in `@layer zcw-skin`, so any value set in Elementor (element, page CSS or site kit) wins over the theme default. This is what keeps the editor and the front end identical.
* Footer is fully editable in Elementor — and it works now. The document type is the registered `wp-post` document (the old `wp-footer` type is Elementor Pro's and made the editor fall back to a document it could not save). Verified: `post.php?post=13&action=elementor` → 200 `elementor-editor-active`, a document save through the Elementor API persists, and the front end immediately shows the new copy.
* Every footer element is a named Elementor widget (shown in the Navigator): «نوار دعوت به اقدام», «تیتر دعوت به اقدام», «توضیح دعوت به اقدام», «دکمهٔ تماس مستقیم», «دکمهٔ ثبت سفارش», «ستون درباره و پشتیبانی», «متن معرفی زرین کد», «بلوک پشتیبانی مستقیم», «ستون خدمات», «پیوندهای خدمات», «ستون فروشگاه», «پیوندهای فروشگاه», «ستون قوانین و شبکه‌ها», «پیوندهای قوانین», «شبکه‌های اجتماعی فوتر», «متن کپی‌رایت». Each link row keeps its own text, icon, URL and target.
* New widget «شبکه‌های اجتماعی قابل ویرایش» (`zarincode_social_profiles`): a repeater where each network has an icon, a visible label, a URL and «باز شدن در زبانهٔ تازه». Defaults are read from the theme's social options, then the owner edits them inside Elementor — no code and no theme option needed. Replaces the read-only shortcode widget in the footer.
* The Elementor document is never overwritten: `ensure()` only fills an empty template; a rebuild from the panel is a separate confirmed action that keeps a `_zc_hf_backup_data` copy. A saved edit survives re-imports and updates (verified).
* Self-heal on upgrade: once per theme version the missing header/footer document is rebuilt for administrators, and the theme renders the Elementor document whenever it exists (the coded shell is now a true last resort), so an updated site can no longer end up with a footer that cannot be edited.
* Admin shortcuts: «ویرایش هدر/فوتر با المنتور» in the admin bar, «ویرایش با المنتور» as the first row action plus «جایگاه»/«وضعیت نمایش» columns in هدر و فوترساز, gold edit buttons in the theme panel, and a dashboard notice pointing at the footer document.
* Fix: 4 × `add_submenu_page( null, … )` (theme + core) logged a PHP 8.1+ «Passing null to parameter» deprecation on every admin page load. The parent slug is now an empty string: the pages stay hidden and `debug.log` is empty on `/wp-admin/` (verified: 0 lines on dashboard, templates list, theme panel, Elementor editor).
* Verified: `/`, `/shop/`, `/services/`, `/blog/`, `/contact-us/`, a single post and the legal pages → 200 with an empty `debug.log`; Elementor editor opens for both documents; demo import still builds page 27, product 10, post 10, zc_service 6, zc_project 8 with 0 log lines.

= 4.10.1 =
* Revert: the 4.10.0 header/footer pass changed the size of both parts — the footer grew from 691.84px to 748.14px and the header shrank from 228.19px to 197px. Both are back to their exact 4.9.1 geometry (verified: footer 691.84px, header 228.19px, total page height 9026px, identical to 4.9.1 at 1440px).
* Fix: the footer background colour is the only visual change — a single `--zc-footer-plate` colour on the footer plate, with the text/social/icon colours corrected for contrast (headings white, body `#B9BEC8`, links/socials `#E7E9EE`, glyphs brand gold, filled and outline buttons keep the colours set in the editor).
* Revert: the 4.10.0 footer redesign is gone — no `margin-top`, no `overflow:hidden`, no gold hairline, no rounded dark card around the CTA band, no bullet dots on the link lists, no `translateX` hover.
* Fix: icons are no longer reshaped. `.elementor-icon` keeps the size, box and radius set in Elementor; the theme only sets its colour. The 2.6rem gold box that squeezed the footer support glyph is removed.
* Fix: the typeface rule no longer replaces icon fonts. The old `font-family` override painted tofu boxes instead of glyphs in stock widgets, cards and the footer; Vazirmatn is now applied with `:where()` (zero added specificity) so `.fa-*` / `.eicon-*` always win.
* Fix: theme type metrics (heading line-height, text-editor size, icon-list rhythm, button padding/paint) are declared in a cascade layer, so a size or background set in Elementor wins over the theme default instead of being silently overridden.
* Verified: `/`, `/shop/`, `/services/`, `/blog/`, `/contact-us/`, a single post and the legal pages all return 200 with an empty `debug.log`; the contact form, product cards and blog cards keep their designed look.

= 4.10.0 =
* Security: PHP errors can no longer print server paths. A hardening layer (`Zarincode\Runtime260\Security\Hardening`) turns `display_errors` off and installs an error handler that suppresses the display of non-fatal errors even when the host pins `display_errors On`; every message returned to a browser is passed through `scrub()` (`/home/…/public_html/…` → `header.php:54`).
* Security: admin panel «امنیت سایت» (قالب زرین کد → امنیت و نمایش خطا) reports the state of the shield and scans `debug.log` for absolute paths.
* Security: XML-RPC/pingback disabled, `?author=` enumeration blocked, `/wp/v2/users` closed to guests, security headers added, generator/X-Powered-By removed, `index.php` guards in all directories, capability check added to the WooCommerce product meta saver.
* Performance: the Elementor editor payload of inner pages dropped from 2,457 KB to 95 KB of widget controls by splitting the all-in-one page widget into one scoped widget per page model.
* Fix: legal pages are built from the designed document widget (hero, sticky clause navigation, numbered cards, sidebar, CTA) instead of loose heading/text widgets.
* Fix: Elementor stock widgets (heading, button, icon box, progress, accordion, tabs, star rating, alert, social icons, divider, counter, testimonial) are repainted with the Zarincode palette; Elementor's Google-Fonts requests removed so Vazirmatn is the only typeface.
* Fix: the approved original page design renders without Elementor (no blank pages while Elementor is updating).

= 4.9.1 =
* Icons: the 4.9.0 pass forced every glyph (`display:block; width/height:1em`) inside header buttons, footer links and all `.zc-btn`s; that clipped wide glyphs and detached icons from their labels. Only real icon boxes (circle/tile with a background) are centred now, and text buttons keep natural inline metrics with `flex:0 0 auto` so an icon can never be squashed at the pill edge.
* Homepage services: the meta chip's glyph is a `<span class="fa">`, so `.schips span` painted a second pill over the icon. Chips are pills only for text; the glyph sits in its own perfectly centred 18px badge.
* Homepage CTA arrows: `.smore .ar` / `.continue .ar` / `.link .ar` were re-declared `display:inline-block`, which pinned the arrow to the circle rim. They are grid-centred again (direction:ltr, line-height:1) and the "جزئیات خدمت" hover no longer paints a dark rectangle behind the label.
* Portfolio cards: long `cat c2` tags are clamped (single line + ellipsis, max 66% of the row) so they can never collide with the CTA or wrap the arrow; the card body is a flex column with the CTA row pinned to the bottom, so titles of different lengths still align.
* Blog cards: the featured photograph absorbs the extra card height (no dead white block), side thumbnails share one fixed 168px cover column in equal-height rows, excerpts/titles are clamped, and the meta row is a tidy avatar + author + date with a read-time chip carrying a clock icon.
* Version: theme and core both 4.9.1.

= 4.9.0 =
* Homepage: all sections can be fed by real site content (services, products, reviews, posts, projects) with a safe manual fallback; services and testimonials gained a data-source control; testimonials show the real client photo.
* Pricing: four working tracks (design / development / SEO / content) as an accessible tab list with 12 initial plans.
* Ticker: seamless horizontal marquee with per-item links, pause on hover/focus and a visible pause button.
* Header/footer: current-menu state from the request path with aria-current="page"; the footer shares the header's dark plate, gold hairline and hover language.
* Interaction: 27 hover/focus targets (cards, chips, tags, buttons, menu, ticker, tables) with a single easing/lift language.
* Colour continuity: the page background is now one 180deg gradient owned by the stylesheet (top edge behind the
  header and bottom edge above the footer are identical from left to right, and it no longer shifts while you
  scroll); the colour switcher only pushes the two colours instead of repainting a fixed diagonal on body; the
  homepage no longer paints its own flat cream; the footer plate is full-bleed, rises out of the page through a
  9rem veil with no hairline and no drop shadow, keeps its own hover language and runs to the last pixel.
* Performance: homepage 182 → 152 queries warm (title index, average rating and partner brands cached 12 h and flushed on content, term, review and theme changes).
* Accessibility: axe-core 4.10.2 (WCAG 2.0/2.1 A + AA + best practice) clean on the homepage at three breakpoints and on 404/search; contrast tokens, heading outline, star rating roles, ticker tab order and keyboard-scrollable pricing table fixed.

= 4.8.0 =
* SEO and AI-visibility module (indexing controls, 18 AI crawler groups, IndexNow, llms.txt, AI scan).

= 4.7.1 =
* Front-page document title and single-template refinements.

== Upgrade Notice ==

= 4.9.0 =
Update the companion plugin to 4.9.0 as well. Back up first, then regenerate the Elementor CSS and clear caches. Do not re-import the demo on an existing site.
