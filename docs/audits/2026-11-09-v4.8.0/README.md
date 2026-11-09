# Audit — v4.8.0 (2026-11-09)

Evidence for the new **«سئو و هوش مصنوعی»** module (`zarincode-core/includes/Seo/`):
search-engine indexing settings + an AI-visibility scanner, built to add **zero**
cost to the front end.

| file | what it shows |
| --- | --- |
| `settings-index-desktop.png` | «ایندکس و سئو» screen: status cards (robots.txt / sitemap / llms.txt / score), base switches, verification tokens for Google + Bing + Yandex + Baidu + Pinterest, snippet controls, noindex switches, IndexNow block with key file URL + submission history, advanced head/robots code, sticky save bar, quick guide. |
| `settings-scan-desktop.png` | «اسکن هوش مصنوعی» screen: ring score **96/100 (A)**, five group dials (ایندکس‌پذیری 95 · کیفیت محتوا 100 · داده‌ساختاریافته 95 · دیده‌شدن در AI 100 · سرعت و محافظت 100), the "how AI sees the site" preview, per-bot access table (18 crawlers), and every check with a Persian pass/warn/fail line and a fix hint. |
| `home-desktop-1440.png` / `home-mobile-390.png` | home page after the change (8,683 px / 18,263 px tall) — no design regression. |
| `robots.txt.txt` | live robots.txt: core rules, `Sitemap:`, 18 AI crawler user-agents, custom rules, no fake crawl-delay. |
| `llms.txt.txt` | generated curated document (58 lines, 29 links) — pages, services, projects, products, articles, usage policy. |
| `head-tags-home.html.txt` | the head tags this feature actually emits: verification metas, `max-image-preview` / `max-snippet`, canonical, OG/Twitter, JSON-LD. |

Environment: WordPress 7.1.4 · Elementor 4.3.6 · fa_IR · `WP_DEBUG` + `WP_DEBUG_LOG`
· PHP 8.4. `debug.log` after the full pass contains **only** Elementor's own PHP 8.4
deprecation (`Atomic_Global_Styles::get_cache_root_key()`); nothing from
`zarincode-core` (grep of `includes/Seo` → 0 hits).

## Performance proof (the acceptance criterion)

Same homepage, consecutive requests on the same server:

| | queries | peak memory | styles/scripts | HTML |
| --- | --- | --- | --- | --- |
| feature ON | 76–78 | 9.3 MB | 17 / 4 | 118,237 B |
| feature hooks removed | 76 | 9.3 MB | 17 / 4 | 117,970 B |

* Query count is identical apart from the **first** request after a settings change
  (a one-off bookkeeping write); the steady state is equal.
* Screenshot of the homepage with the feature ON vs its hooks removed:
  **616 pixels differ at all (max channel delta 2/255), 16 px above 1, 0 px above 4**
  over 12.49 M pixels — i.e. visually identical; the difference is the per-request
  nonce/text in the floating contact widget.
* Design: `settings-updated` toast, toggle states, snippet selects, IndexNow key
  and history were exercised through the real Settings-API form (`options.php`),
  including un-checking a switch (hidden `no` + checkbox `yes`).

## Functional proof (checked, not asserted)

| check | result |
| --- | --- |
| `/llms.txt`, `/llms-full.txt` | 200 · `text/plain` · 8,256 B / 19,295 B |
| `/robots.txt` | 200 · core rules + `Sitemap:` + 18 AI agents + owner rules |
| `/{key}.txt` (IndexNow) | 200 · `text/plain` · body = key exactly |
| `/wp-sitemap.xml` | 200 · 10 sub-sitemaps, > 0 URLs |
| single post | `BlogPosting` + `BreadcrumbList` + WebSite/Organization, no duplicate `BlogPosting` |
| 404 / search | `noindex, follow` merged into WordPress' **single** robots tag |
| noindex switches | unchecking «آرشیو برچسب» persisted as `no` (verified in the option) |
| IndexNow publish flow | `transition_post_status` → event scheduled 60 s later → mocked endpoint received `host`, `key`, `keyLocation`, 2 URLs → log row «۲۰۰ پذیرفته شد» |
| SEO-plugin ownership | with `WPSEO_VERSION` simulated: 0 verification tags, 0 feature JSON-LD, theme meta suppressed; robots.txt groups still served |
| bot policy `nofeed` | retrieval group `Allow: /`, training group `Disallow: /` |
| `User-agent: *` parsed group-by-group | healthy robots.txt → open; `Disallow: /` → blocked (regression test after a false positive was found) |
| scanner accuracy | Persian word count 214 → **2,093** after Unicode-aware counting; sitemap index handled; `[zarincode_widget …]` no longer leaks into meta description / llms.txt; score 84 (B) → 96 (A) |

## Deliberate decisions

* IndexNow / llms.txt are **virtual routes** — no file is written to the web root,
  no `.htaccess` edit, and rewrite rules are flushed only when their inputs change.
* Submissions are queued with `wp_schedule_single_event()`, never inline on publish.
* Settings live in one autoloaded option (`zarincode_seo`): zero extra queries.
* Nothing is emitted when Yoast/RankMath/AIOSEO owns metadata (except robots.txt
  groups, which are additive and cannot collide).
* `Google-Extended` is documented in the UI as *not* affecting AI Overviews — it
  only governs Gemini training/grounding.
