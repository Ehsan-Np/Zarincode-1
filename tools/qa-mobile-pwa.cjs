/** Isolated browser regression harness, NOT a real WordPress/WooCommerce E2E test.
 * NODE_PATH=/path/to/dev/node_modules node tools/qa-mobile-pwa.cjs
 * Set CHROMIUM_EXECUTABLE_PATH, or install @sparticuz/chromium for a sandbox.
 */
const { chromium } = require('playwright');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file));
const fixture = `<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/main.css"><link rel="stylesheet" href="/responsive.css"></head><body>
<main id="primary"><div class="zc-container"><div class="zc-header-pill"><a class="zc-brand" href="/site/"><span class="zc-brand-logo-box">ZC</span><span class="zc-brand-text">زرین کد</span></a><div class="zc-header-actions">${['جستجو','حساب','سبد','تم'].map(text=>`<button class="zc-icon-btn">${text}</button>`).join('')}<button class="zc-icon-btn zc-mobile-menu-toggle" data-zc-drawer-open="zc-mobile-drawer">منو</button></div></div>
<div class="zc-services-grid"><article class="zc-cover-card"><h2>عنوان فارسی طولانی برای بررسی واکنش‌گرایی موبایل</h2></article><article>دوم</article><article>سوم</article></div><input placeholder="اطلاعات خصوصی" value="PRIVATE_SENTINEL"></div></main>
<div id="zc-mobile-drawer" class="zc-mobile-drawer-backdrop" aria-hidden="true" inert><aside class="zc-mobile-drawer" aria-label="منو"><div class="zc-mobile-drawer-head"><a href="/site/">خانه</a><button data-zc-drawer-close>بستن</button></div><nav class="zc-mobile-nav-wrap"><ul class="zc-mobile-nav-list"><li class="zc-mobile-has-sub"><button data-zc-mobile-sub-toggle>خدمات</button><ul class="zc-mobile-sub-list"><li><a href="/site/services/">طراحی</a></li></ul></li><li><a href="/site/account/">حساب</a></li></ul></nav></aside></div>
<script src="/mobile.js"></script></body></html>`;
(async () => {
    let origin;
    let enabled = true;
    const requests = [];
    const server = http.createServer((req, res) => {
        requests.push(req.url);
        const url = new URL(req.url, origin);
        const files = {
            '/main.css': ['zarincode/assets/css/zarincode-main.css','text/css'],
            '/responsive.css': ['zarincode/assets/css/responsive.css','text/css'],
            '/mobile.js': ['zarincode/assets/js/mobile-navigation.js','application/javascript'],
        };
        if (files[url.pathname]) { const [file,type]=files[url.pathname]; res.setHeader('Content-Type',type+'; charset=utf-8'); res.end(read(file)); return; }
        if (url.searchParams.get('zc_pwa') === 'worker') {
            res.setHeader('Cache-Control','no-store'); res.setHeader('Content-Type','application/javascript'); res.setHeader('Service-Worker-Allowed','/site/');
            res.end('const CONFIG='+JSON.stringify({enabled,prefix:'zarincode-pwa-test-',version:'1',offline:origin+'/site/?zc_pwa=offline',assets:[origin+'/icon.png']})+';\n'+read('zarincode-core/assets/pwa/worker.js')); return;
        }
        if (url.searchParams.get('zc_pwa') === 'offline') {res.setHeader('Content-Type','text/html');res.end(read('zarincode-core/assets/pwa/offline.html'));return;}
        if (url.pathname==='/icon.png') {res.setHeader('Content-Type','image/png');res.end(read('zarincode-core/assets/pwa/icon-192.png'));return;}
        res.setHeader('Content-Type','text/html');res.end(fixture);
    });
    await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
    origin = 'http://127.0.0.1:'+server.address().port;
    let browser;
    let checks = 0;
    try {
        let sandbox;
        if (!process.env.CHROMIUM_EXECUTABLE_PATH) {
            try { const bundle = require('@sparticuz/chromium'); sandbox = bundle.default || bundle; }
            catch (_) { /* Use Playwright's installed browser in CI. */ }
        }
        const executablePath = process.env.CHROMIUM_EXECUTABLE_PATH || (sandbox ? await sandbox.executablePath() : undefined);
        browser = await chromium.launch({executablePath,args:sandbox ? sandbox.args : ['--no-sandbox'],headless:true});
        const context=await browser.newContext();
        const page=await context.newPage();
        for (const width of [320,360,390,430,768,1024,1440]) {
            await page.setViewportSize({width,height:850});await page.goto(origin+'/site/');
            assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1), 'overflow '+width); checks++;
            if (width>1024) continue;
            const trigger=page.locator('[data-zc-drawer-open]');await trigger.click();
            await page.waitForFunction(()=>document.querySelector('.zc-mobile-drawer').contains(document.activeElement), null, {timeout:3000}).catch(async error=>{console.error('drawer diagnostics',width,await page.evaluate(()=>({active:document.activeElement.outerHTML,drawer:document.querySelector('.zc-mobile-drawer-backdrop').outerHTML,overflow:document.body.style.overflow})));throw error;});
            assert.equal(await trigger.getAttribute('aria-expanded'),'true');
            assert(await page.locator('main').evaluate(el=>el.inert));checks+=2;
            const sub=page.locator('[data-zc-mobile-sub-toggle]');await sub.click();
            assert.equal(await sub.getAttribute('aria-expanded'),'true');assert(await page.locator('.zc-mobile-sub-list').isVisible());checks+=2;
            // Last link -> Tab wraps to first link, Shift+Tab goes to last.
            await page.locator('.zc-mobile-nav-list>li>a').focus();await page.keyboard.press('Tab');
            assert.equal(await page.evaluate(()=>document.activeElement.textContent),'خانه');checks++;
            await page.keyboard.press('Shift+Tab');assert.equal(await page.evaluate(()=>document.activeElement.textContent),'حساب');checks++;
            await page.keyboard.press('Escape');assert.equal(await trigger.getAttribute('aria-expanded'),'false');assert(await trigger.evaluate(el=>el===document.activeElement));assert.equal(await page.locator('main').evaluate(el=>el.inert),false);checks+=3;
        }
        await page.setViewportSize({width:320,height:700});await page.goto(origin+'/site/');
        await page.locator('.zc-header-actions button').nth(1).evaluate(el=>{el.classList.add('zc-header-account','zc-account-label-always');el.innerHTML='<span>حساب کاربری من</span>';});
        assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));checks++;
        await page.evaluate(()=>document.documentElement.dir='ltr');
        await page.locator('[data-zc-drawer-open]').click();await page.locator('[data-zc-drawer-close]').waitFor({state:'visible'});
        await page.waitForFunction(()=>Math.abs(document.querySelector('.zc-mobile-drawer').getBoundingClientRect().left)<1);
        assert(await page.locator('.zc-mobile-drawer').evaluate(el=>el.getBoundingClientRect().right<=innerWidth));checks++;
        await page.keyboard.press('Escape');
        await page.setViewportSize({width:390,height:360});await page.goto(origin+'/site/');
        await page.locator('[data-zc-drawer-open]').click();
        await page.locator('[data-zc-drawer-close]').waitFor({state:'visible'});
        assert(await page.locator('[data-zc-drawer-close]').isVisible());await page.locator('[data-zc-drawer-close]').click();checks++;
        await page.evaluate(async()=>{
            await caches.open('unrelated-plugin').then(c=>c.put('/other',new Response('keep')));
            await caches.open('zarincode-pwa-test-old');
            await navigator.serviceWorker.register('/site/?zc_pwa=worker',{scope:'/site/'});await navigator.serviceWorker.ready;
        });
        await page.waitForFunction(()=>!!navigator.serviceWorker.controller);
        const keys=await page.evaluate(()=>caches.keys());assert(keys.includes('unrelated-plugin'));assert(!keys.includes('zarincode-pwa-test-old'));checks+=2;
        for (const target of ['account/','cart/','checkout/','?add-to-cart=4','wp-json/private/']) await page.goto(origin+'/site/'+target);
        await page.evaluate(()=>fetch('/site/?wc-ajax=checkout',{method:'POST',body:'private=SECRET'}));
        const cached=await page.evaluate(async()=>{
            const cache=await caches.open('zarincode-pwa-test-1');const keys=await cache.keys();return keys.map(r=>r.url);
        });
        assert.equal(cached.length,2);assert(cached.every(url=>url.endsWith('zc_pwa=offline')||url.endsWith('/icon.png')));checks+=2;
        await context.setOffline(true);await page.goto(origin+'/site/checkout/');
        assert(await page.getByRole('heading',{name:'فعلاً آفلاین هستید'}).isVisible());assert(!(await page.content()).includes('PRIVATE_SENTINEL'));checks+=2;
        await context.setOffline(false);await page.getByRole('button',{name:'تلاش دوباره'}).click();await page.waitForSelector('#primary');checks++;
        // The opt-out worker retires only its own cache and registration.
        enabled = false;
        await page.evaluate(async()=>{const reg=await navigator.serviceWorker.getRegistration('/site/');await reg.update();});
        await page.waitForFunction(async()=>!(await caches.keys()).some(key=>key.startsWith('zarincode-pwa-test-')));
        assert((await page.evaluate(()=>caches.keys())).includes('unrelated-plugin'));checks++;
        console.log(`PASS: ${checks} isolated browser assertions (7 widths, drawer focus/ARIA, landscape, subdirectory SW, offline recovery, private-route cache exclusion).`);
        await context.close();
    } finally { if(browser) await browser.close();await new Promise(resolve=>server.close(resolve)); }
})().catch(error=>{console.error(error);process.exitCode=1;});
