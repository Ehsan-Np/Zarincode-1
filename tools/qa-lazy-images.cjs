// Browser integration for real Lazy_Images::html output, not a parallel implementation.
// NODE_PATH=/tmp/qa/node_modules node tools/qa-lazy-images.cjs /tmp/zc-media-lazy.html
const http = require('node:http');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium: playwright } = require('playwright-core');
const chromium = require('@sparticuz/chromium');
(async () => {
  const html = fs.readFileSync(process.argv[2], 'utf8');
  assert.match(html, /loading="lazy"/);
  const image = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aENsAAAAASUVORK5CYII=', 'base64');
  const hits = [];
  const server = http.createServer((req, res) => {
    hits.push(req.url);
    res.setHeader('Content-Type', req.url.endsWith('.png') ? 'image/png' : 'text/html; charset=utf-8');
    res.end(req.url.endsWith('.png') ? image : html);
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  let browser;
  try {
    browser = await playwright.launch({executablePath: await chromium.executablePath(), args: chromium.args, headless: true});
    const page = await browser.newPage({viewport: {width: 800, height: 600}});
    await page.goto(`http://127.0.0.1:${server.address().port}/`, {waitUntil: 'networkidle'});
    assert(hits.includes('/hero.png'), 'critical image requested at initial load');
    assert(!hits.includes('/deep.png'), 'offscreen image NOT requested initially');
    await page.locator('#deep').scrollIntoViewIfNeeded();
    await page.waitForFunction(() => document.querySelector('#deep').complete && document.querySelector('#deep').naturalWidth > 0);
    assert(hits.includes('/deep.png'), 'image requested after scrolling near viewport');
    assert.equal(await page.locator('#deep').getAttribute('loading'), 'lazy');
    console.log('PASS: 5 Chromium assertions; critical image immediate, below-fold network request deferred until scroll.');
  } finally {
    if (browser) await browser.close();
    await new Promise(resolve => server.close(resolve));
  }
})().catch(error => {console.error(error); process.exitCode = 1;});
