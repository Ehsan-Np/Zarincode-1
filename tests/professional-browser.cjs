/** Disposable WordPress only: edits and PUBLISHES the professional-fixture test page.
 * Requires Playwright + Chromium. Never put credentials in source control.
 * ZC_TEST_BASE_URL, ZC_TEST_PAGE_ID, ZC_TEST_ADMIN_USER, ZC_TEST_ADMIN_PASSWORD.
 */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const base = (process.env.ZC_TEST_BASE_URL || 'http://127.0.0.1:8080').replace(/\/$/, '');
const id = process.env.ZC_TEST_PAGE_ID;
const user = process.env.ZC_TEST_ADMIN_USER;
const password = process.env.ZC_TEST_ADMIN_PASSWORD;
if (!id || !user || !password) throw new Error('Set the required ZC_TEST_* environment variables.');
(async function () {
    const browser = await chromium.launch({ args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        page.on('pageerror', e => errors.push(e.message));
        await page.goto(base + '/wp-login.php');
        await page.locator('#user_login').fill(user);
        await page.locator('#user_pass').fill(password);
        await Promise.all([page.waitForURL('**/wp-admin/**'), page.locator('#wp-submit').click()]);
        await page.goto(base + '/wp-admin/post.php?post=' + id + '&action=elementor', { waitUntil: 'domcontentloaded', timeout: 60000 });
        const frame = page.frameLocator('#elementor-preview-iframe');
        await frame.locator('.zc-hero-main-card').first().waitFor({ timeout: 60000 });
        await page.locator('#elementor-loading').waitFor({ state: 'hidden', timeout: 90000 });
        // Playwright's iframe locator cannot detect an overlay in the parent document.
        await page.evaluate(() => document.querySelector('#elementor-navigator__close')?.click());
        async function typeNative(locator, text) {
            await locator.click();
            await locator.evaluate(e => new Promise((resolve, reject) => {
                const timer = setInterval(() => { if (e.isContentEditable) { clearInterval(timer); resolve(); } }, 20);
                setTimeout(() => { clearInterval(timer); reject(new Error('Native inline editor did not activate')); }, 10000);
            }));
            await locator.press('ControlOrMeta+A');
            await locator.pressSequentially(text, { delay: 25 });
            await locator.press('Tab');
        }
        await typeNative(frame.locator('.elementor-element-originalhero h1 .elementor-inline-editing').first(), 'ORIGINAL CLICK EDIT SAVED');
        await page.waitForFunction(() => JSON.stringify(elementor.getContainer('originalhero').settings.toJSON()).includes('ORIGINAL CLICK EDIT SAVED'));
        console.log('PASS original native model edit');
        await frame.locator('.elementor-element-servicespotlight .elementor-editor-element-edit').dispatchEvent('click');
        await page.locator('.elementor-control-title input').waitFor();
        await page.waitForFunction(() => !elementor.getContainer('servicespotlight').view.model.isRemoteRequestActive());
        await typeNative(frame.locator('.elementor-element-servicespotlight .zc-pro-title.elementor-inline-editing').first(), 'SERVICE INLINE SAVED');
        await page.waitForFunction(() => JSON.stringify(elementor.getContainer('servicespotlight').settings.toJSON()).includes('SERVICE INLINE SAVED'));
        console.log('PASS professional repeater native model edit');
        await page.evaluate(async () => { await $e.run('document/save/publish'); });
        await page.goto(base + '/?page_id=' + id, { waitUntil: 'networkidle' });
        assert.equal(await page.locator('h1').filter({ hasText: 'ORIGINAL CLICK EDIT SAVED' }).count(), 1);
        assert.equal(await page.getByText('SERVICE INLINE SAVED', { exact: true }).count(), 1);
        assert.equal(await page.locator('.elementor-element-originalhero .zc-hero-inline-pill').count(), 1);
        assert.equal(await page.locator('[data-elementor-setting-key]').count(), 0);
        assert.equal(await page.locator('[data-zc-pro]').count(), 6);
        console.log('PASS published frontend persistence, original pill and clean public HTML');
        const range = page.locator('[data-zc-compare]');
        await range.fill('75');
        await range.dispatchEvent('input');
        assert.equal(await page.locator('.zc-pro-compare').evaluate(e => e.style.getPropertyValue('--zc-split')), '75%');
        await range.focus();
        await range.press('ArrowRight');
        assert.equal(await range.inputValue(), '76');
        await page.locator('.zc-pro-compare').scrollIntoViewIfNeeded();
        const box = await page.locator('.zc-pro-compare').boundingBox();
        await page.mouse.move(box.x + box.width * .25, box.y + box.height * .45);
        await page.mouse.down();
        await page.mouse.move(box.x + box.width * .6, box.y + box.height * .45);
        await page.mouse.up();
        assert.equal(await range.inputValue(), '60');
        console.log('PASS comparison input, keyboard and pointer drag');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload({ waitUntil: 'networkidle' });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
        assert.deepEqual(errors, []);
        console.log('PASS mobile overflow and browser exceptions');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
