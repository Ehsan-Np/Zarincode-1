=== Zarincode ===
Contributors: zarincode
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 4.10.0
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
