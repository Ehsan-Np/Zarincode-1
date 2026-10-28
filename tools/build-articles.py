"""Generate zarincode/demo-data/zarincode-articles.xml — the second OCDI demo package.

Content policy (project standing rule): articles are written from international
English-language knowledge sources only (WordPress.org documentation, Google
Search Central, web.dev, MDN, WCAG and comparable primary documentation).
No text is copied from Persian-language websites; every article is an original
Persian rendering of that foreign technical knowledge, professional and
friendly in tone, with internal links to the Zarincode site.

Run:  python3 tools/build-articles.py
Kept as active source — regenerate the WXR after editing build-articles-data.py.
"""
import re
import sys
from datetime import datetime, timedelta, timezone
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from build_articles_data import ARTICLES, CATEGORIES  # noqa: E402

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "zarincode" / "demo-data" / "zarincode-articles.xml"
SITE = "https://zarincode.com"
POST_ID_BASE = 91000
START_DATE = datetime(2026, 6, 1, 9, 30, tzinfo=timezone.utc)

QUOTA = {
    "web-design": 10,
    "seo": 10,
    "content-production": 5,
    "wordpress-tutorials": 12,
    "website-security": 22,
    "site-performance": 12,
}
SECURITY_MARK = "امنیت تا سقف ۹۹٪"


def cdata(text: str) -> str:
    # WXR carries markup inside CDATA; abort loudly if a body ever breaks out of it.
    assert "]]>" not in text, "CDATA terminator inside content"
    return "<![CDATA[" + text + "]]>"


def rfc822(dt: datetime) -> str:
    return dt.strftime("%a, %d %b %Y %H:%M:%S +0000")


def wp_date(dt: datetime) -> str:
    return dt.strftime("%Y-%m-%d %H:%M:%S")


def xml_text(text: str) -> str:
    return re.sub(r"\s+", " ", text).strip().replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


def validate() -> None:
    counts = {}
    seen_slugs = set()
    for a in ARTICLES:
        cat = a["category"]
        counts[cat] = counts.get(cat, 0) + 1
        assert cat in QUOTA, f"unknown category {cat}"
        assert a["slug"] not in seen_slugs, f"duplicate slug {a['slug']}"
        seen_slugs.add(a["slug"])
        assert re.fullmatch(r"[a-z0-9-]+", a["slug"]), f"slug must be ascii: {a['slug']}"
        assert len(a["title"]) >= 15, a["slug"]
        assert len(a["excerpt"]) >= 80, f"excerpt too short: {a['slug']}"
        assert a["body"].count("<h2") >= 2, f"needs at least 2 H2: {a['slug']}"
        assert a["body"].count("<h1") == 0, f"no H1 in body (post title is H1): {a['slug']}"
        assert "<a href=" in a["body"], f"needs internal link: {a['slug']}"
        if cat == "website-security":
            assert SECURITY_MARK in a["body"], f"security guarantee missing: {a['slug']}"
            assert a["body"].count(SECURITY_MARK) >= 1, a["slug"]
    for cat, want in QUOTA.items():
        got = counts.get(cat, 0)
        floor = 20 if cat == "website-security" else (10 if cat == "site-performance" else want)
        assert got >= floor, f"quota {cat}: {got} < {floor}"
        if cat not in ("website-security", "site-performance"):
            assert got == want, f"quota {cat}: {got} != {want}"
    assert sum(counts.values()) >= 67, counts


def build() -> str:
    validate()
    cat_by_slug = {c["slug"]: c for c in CATEGORIES}
    items = []
    for i, a in enumerate(ARTICLES):
        dt = START_DATE + timedelta(days=i * 2, hours=i % 5)
        pid = POST_ID_BASE + i + 1
        link = f"{SITE}/blog/{a['slug']}/"
        body = a["body"]
        # guarantee every article links somewhere on the brand site
        assert SITE in body, a["slug"]
        item = f"""<item>
<title>{xml_text(a['title'])}</title>
<link>{link}</link>
<pubDate>{rfc822(dt)}</pubDate>
<dc:creator>{cdata('zarincode')}</dc:creator>
<guid isPermaLink="false">{SITE}?p={pid}</guid>
<description></description>
<content:encoded>{cdata(body)}</content:encoded>
<excerpt:encoded>{cdata(a['excerpt'])}</excerpt:encoded>
<wp:post_id>{pid}</wp:post_id>
<wp:post_date>{cdata(wp_date(dt))}</wp:post_date>
<wp:post_date_gmt>{cdata(wp_date(dt))}</wp:post_date_gmt>
<wp:comment_status>{cdata('open')}</wp:comment_status>
<wp:ping_status>{cdata('closed')}</wp:ping_status>
<wp:post_name>{cdata(a['slug'])}</wp:post_name>
<wp:status>{cdata('publish')}</wp:status>
<wp:post_parent>0</wp:post_parent>
<wp:menu_order>0</wp:menu_order>
<wp:post_type>{cdata('post')}</wp:post_type>
<wp:post_password>{cdata('')}</wp:post_password>
<wp:is_sticky>0</wp:is_sticky>
<category domain="category" nicename="{a['category']}">{cdata(cat_by_slug[a['category']]['name'])}</category>
<wp:postmeta>
<wp:meta_key>_zc_demo_article</wp:meta_key>
<wp:meta_value>{cdata('1')}</wp:meta_value>
</wp:postmeta>
</item>"""
        items.append(item)

    channels = []
    for c in CATEGORIES:
        channels.append(
            f"""<wp:category>
<wp:term_id>{c['term_id']}</wp:term_id>
<wp:category_nicename>{cdata(c['slug'])}</wp:category_nicename>
<wp:category_parent></wp:category_parent>
<wp:cat_name>{cdata(c['name'])}</wp:cat_name>
</wp:category>"""
        )

    header = f"""<?xml version='1.0' encoding='UTF-8'?>
<!-- Zarincode magazine demo package: {len(ARTICLES)} articles generated by tools/build-articles.py from international primary documentation (WordPress.org, Google Search Central, web.dev); original Persian text. -->
<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">
<channel>
<title>Zarincode Magazine</title>
<link>{SITE}</link>
<description>مقالات فارسی زرین کد</description>
<pubDate>{rfc822(START_DATE)}</pubDate>
<language>fa-IR</language>
<wp:wxr_version>1.2</wp:wxr_version>
<wp:base_site_url>{SITE}</wp:base_site_url>
<wp:base_blog_url>{SITE}</wp:base_blog_url>
<wp:author>
<wp:author_id>1</wp:author_id>
<wp:author_login>{cdata('zarincode')}</wp:author_login>
<wp:author_email>{cdata('info@zarincode.com')}</wp:author_email>
<wp:author_display_name>{cdata('احسان نادری پناه')}</wp:author_display_name>
<wp:author_first_name>{cdata('احسان')}</wp:author_first_name>
<wp:author_last_name>{cdata('نادری پناه')}</wp:author_last_name>
</wp:author>
{chr(10).join(channels)}
</channel>
</rss>
"""
    # assemble with items inside channel (header above closes channel — split it)
    head, sep, tail = header.rpartition("</channel>")
    assert sep
    doc = head + "\n".join(items) + "\n" + sep + tail
    return doc


def main() -> None:
    doc = build()
    OUT.write_text(doc, encoding="utf-8")
    import xml.dom.minidom
    xml.dom.minidom.parse(str(OUT))  # well-formedness gate
    counts = {}
    for a in ARTICLES:
        counts[a["category"]] = counts.get(a["category"], 0) + 1
    print(f"WROTE {OUT} bytes={OUT.stat().st_size} articles={len(ARTICLES)}")
    for c in CATEGORIES:
        print(f"  {c['slug']}: {counts.get(c['slug'],0)}")
    # importer-style sanity: item count and security mark
    raw = OUT.read_text(encoding="utf-8")
    assert raw.count("<item>") == len(ARTICLES)
    assert raw.count(SECURITY_MARK) >= 20
    print("VALIDATION_OK")


if __name__ == "__main__":
    main()
