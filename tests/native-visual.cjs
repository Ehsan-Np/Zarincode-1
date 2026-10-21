/** Requires tests/native-design.php to emit /tmp/zc300-compare.json on a disposable fixture. */
const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const base = (process.env.ZC_TEST_BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
(async () => {
    const rows = JSON.parse(fs.readFileSync(process.env.ZC_TEST_COMPARE_JSON || '/tmp/zc300-compare.json', 'utf8'));
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        await page.goto(base);
        const sheets = ['themes/zarincode/assets/css/vazirmatn.css', 'themes/zarincode/assets/fontawesome/css/all.min.css', 'themes/zarincode/assets/css/zarincode-main.css', 'plugins/zarincode-core/includes/Widgets/assets/native.css'];
        await page.setContent('<html dir="rtl"><meta charset="utf-8">' + sheets.map(src => '<link rel="stylesheet" href="' + base + '/wp-content/' + src + '">').join('') + '<style>body{padding:20px}</style><body>' + rows.map((r, i) => '<div class="zc-container" data-compare="' + i + '"><div data-old>' + r.old + '</div><div data-new>' + r.new + '</div></div>').join('') + '</body></html>', { waitUntil: 'networkidle' });
        await page.evaluate(() => document.fonts.ready);
        const results = await page.locator('[data-compare]').evaluateAll(els => els.map(e => {
            const a = e.querySelector('[data-old]'), b = e.querySelector('[data-new]');
            const clean = x => x.innerText.replace(/\s+/g, ' ').trim();
            return { oldHeight: a.getBoundingClientRect().height, newHeight: b.getBoundingClientRect().height, text: clean(a) === clean(b) };
        }));
        results.forEach((r, i) => { assert.equal(r.text, true, rows[i].kind + ' text'); assert(Math.abs(r.oldHeight - r.newHeight) <= 1, rows[i].kind + '/' + rows[i].section + ' height ' + JSON.stringify(r)); });
        console.log('PASS: all 23 default layouts retain normalized text and outer height within 1px at 1440px. This is not a pixel-perfect image comparison.');
    } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exitCode = 1; });
