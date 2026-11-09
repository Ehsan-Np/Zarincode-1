=== Zarincode ===
Contributors: zarincode
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 4.9.0
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

= 4.9.0 =
* Homepage: all sections can be fed by real site content (services, products, reviews, posts, projects) with a safe manual fallback; services and testimonials gained a data-source control; testimonials show the real client photo.
* Pricing: four working tracks (design / development / SEO / content) as an accessible tab list with 12 initial plans.
* Ticker: seamless horizontal marquee with per-item links, pause on hover/focus and a visible pause button.
* Header/footer: current-menu state from the request path with aria-current="page"; the footer shares the header's dark plate, gold hairline and hover language.
* Interaction: 27 hover/focus targets (cards, chips, tags, buttons, menu, ticker, tables) with a single easing/lift language.
* Performance: homepage 182 → 152 queries warm (title index, average rating and partner brands cached 12 h and flushed on content, term, review and theme changes).
* Accessibility: axe-core 4.10.2 (WCAG 2.0/2.1 A + AA + best practice) clean on the homepage at three breakpoints and on 404/search; contrast tokens, heading outline, star rating roles, ticker tab order and keyboard-scrollable pricing table fixed.

= 4.8.0 =
* SEO and AI-visibility module (indexing controls, 18 AI crawler groups, IndexNow, llms.txt, AI scan).

= 4.7.1 =
* Front-page document title and single-template refinements.

== Upgrade Notice ==

= 4.9.0 =
Update the companion plugin to 4.9.0 as well. Back up first, then regenerate the Elementor CSS and clear caches. Do not re-import the demo on an existing site.
