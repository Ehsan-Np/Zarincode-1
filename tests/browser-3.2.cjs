/** Disposable fixture-3.2.php page: edits and PUBLISHES. Requires Playwright/Chromium.
 * ZC_TEST_BASE_URL, ZC_TEST_PAGE_ID, ZC_TEST_ADMIN_USER, ZC_TEST_ADMIN_PASSWORD.
 */
const {chromium}=require('playwright');const assert=require('node:assert/strict');
const base=process.env.ZC_TEST_BASE_URL||'http://localhost:8080',id=process.env.ZC_TEST_PAGE_ID;
if(!id||!process.env.ZC_TEST_ADMIN_USER||!process.env.ZC_TEST_ADMIN_PASSWORD)throw Error('Set required ZC_TEST_* variables');
(async()=>{const b=await chromium.launch({args:['--no-sandbox']});try{
 const p=await b.newPage({viewport:{width:1440,height:1100}});const errors=[];p.on('pageerror',e=>{errors.push(e.message);console.error('PAGEERROR_STACK',e.stack)});
 await p.goto(base+'/?page_id='+id,{waitUntil:'networkidle'});
 assert.equal(await p.locator('[data-widget_type^="zarincode_hero_"]').count(),3);
 const grid=await p.locator('.zc-hero-composition').evaluate(e=>getComputedStyle(e).gridTemplateColumns.split(' ').length);assert.equal(grid,2);
 const products=p.locator('.elementor-element-products32');const all=await products.locator('[data-category]').count();assert(all>0);
 assert.equal(await products.locator('[data-filter].is-active').count(),1);assert.equal(await products.locator('[data-filter][aria-pressed="true"]').count(),1);
 const buttons=products.locator('[data-filter]:not([data-filter="all"])');assert(await buttons.count()>=3);
 await buttons.first().click();const value=await buttons.first().getAttribute('data-filter');const visible=products.locator('[data-category]:visible');assert(await visible.count()>0&&await visible.count()<all);
 assert((await visible.evaluateAll(es=>es.map(e=>e.dataset.category))).every(s=>s.split(/\s+/).includes(value)));
 await products.locator('[data-filter="all"]').click();assert.equal(await products.locator('[data-category]:visible').count(),all);
 console.log('PASS real published-product category filtering and reset');
 for(const kind of ['products','services','portfolio','blog']){const images=p.locator('.elementor-element-'+kind+'32 .zc-record-image');assert(await images.count()>0);await images.first().scrollIntoViewIfNeeded();await images.first().evaluate(e=>e.decode());assert(await images.first().evaluate(e=>e.naturalWidth>0));const ratio=await images.first().evaluate(e=>e.getBoundingClientRect().width/e.getBoundingClientRect().height);assert(Math.abs(ratio-1.6)<.02);}
 console.log('PASS loaded, consistently cropped featured images in four automatic sources');
 for(const width of [768,390]){await p.setViewportSize({width,height:1000});assert.equal(await p.locator('.zc-hero-composition').evaluate(e=>getComputedStyle(e).gridTemplateColumns.split(' ').length),1);assert(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));}
 console.log('PASS desktop/tablet/mobile hero layout, no horizontal overflow');await p.setViewportSize({width:1440,height:1100});
 await p.goto(base+'/wp-login.php');await p.locator('#user_login').fill(process.env.ZC_TEST_ADMIN_USER);await p.locator('#user_pass').fill(process.env.ZC_TEST_ADMIN_PASSWORD);await Promise.all([p.waitForURL('**/wp-admin/**'),p.locator('#wp-submit').click()]);
 await p.goto(base+'/wp-admin/post.php?post='+id+'&action=elementor',{waitUntil:'domcontentloaded'});const f=p.frameLocator('#elementor-preview-iframe');await f.locator('.elementor-element-intro32 h1').waitFor({timeout:90000});await p.locator('#elementor-loading').waitFor({state:'hidden',timeout:90000});await p.evaluate(()=>document.querySelector('#elementor-navigator__close')?.click());
 // Widget and section selection are synthetic to avoid Elementor welcome overlays;
 // actual native sidebar inputs/buttons are then exercised with pointer/keyboard.
 await f.locator('.elementor-element-intro32 .elementor-editor-element-edit').dispatchEvent('click');await p.locator('.elementor-control-n_hero_section_content').dispatchEvent('click');
 const title=p.locator('.elementor-control-n_hero_hero_title_text_1 input');await title.fill('عنوان ذخیره‌شده هیرو مستقل');await title.press('Tab');
 await p.waitForFunction(()=>elementor.getContainer('intro32').settings.get('n_hero_hero_title_text_1')==='عنوان ذخیره‌شده هیرو مستقل');
 await f.locator('.elementor-element-intro32 h1').getByText('عنوان ذخیره‌شده هیرو مستقل',{exact:true}).waitFor();
 await f.locator('.elementor-element-price32 .elementor-editor-element-edit').dispatchEvent('click');await p.locator('.elementor-control-n_pricing_features_section').dispatchEvent('click');
 const features=p.locator('.elementor-control-n_pricing_features');await features.locator('.elementor-repeater-row-item-title').first().click();
 const text=features.locator('.elementor-control-text input').first();await text.fill('امکان ذخیره‌شده با فهرست آیکون');await text.press('Tab');
 await features.locator('.elementor-repeater-add').click();const last=features.locator('.elementor-repeater-fields').last();await last.locator('.elementor-control-plan_key select').selectOption('__custom__');await last.locator('.elementor-control-custom_plan_key input').fill('qa-plan');await last.locator('.elementor-control-text input').fill('امکان جدید بدون محدودیت تعداد');await last.locator('.elementor-control-text input').press('Tab');
 await p.waitForFunction(()=>elementor.getContainer('price32').settings.get('n_pricing_features').length===2);
 await f.locator('.elementor-element-price32').getByText('امکان جدید بدون محدودیت تعداد',{exact:true}).waitFor();
 console.log('PASS independent hero title editing and native icon-list edit/add');
 await f.locator('.elementor-element-reviews32 .elementor-editor-element-edit').dispatchEvent('click');await p.locator('.elementor-control-n_testimonials_section_services_grid_items_1').dispatchEvent('click');
 const reviews=p.locator('.elementor-control-n_testimonials_services_grid_items_1');await reviews.locator('.elementor-repeater-row-item-title').first().click();await reviews.locator('.elementor-control-zc_row_media').first().click();
 await reviews.locator('.elementor-control-customer_photo .elementor-control-media__content').first().click();await p.locator('.media-modal').waitFor();
 const library=p.locator('.media-modal .media-router .media-menu-item').last();if(await library.count())await library.click();
 await p.locator('.media-modal .attachments .attachment').first().waitFor();await p.locator('.media-modal .attachments .attachment').first().click();await p.locator('.media-modal .media-button-select').click();await p.locator('.media-modal').waitFor({state:'hidden'});
 await f.locator('.elementor-element-reviews32 .zc-customer-photo').waitFor();
 console.log('PASS customer photo selected through native WordPress media library');
 await p.evaluate(async()=>await $e.run('document/save/publish'));await p.goto(base+'/?page_id='+id,{waitUntil:'networkidle'});
 for(const text of ['عنوان ذخیره‌شده هیرو مستقل','امکان ذخیره‌شده با فهرست آیکون','امکان جدید بدون محدودیت تعداد'])assert.equal(await p.getByText(text,{exact:true}).count(),1);
 assert.equal(await p.locator('.elementor-element-reviews32 .zc-customer-photo').count(),1);assert.equal(await p.locator('[data-widget_type^="zarincode_hero_"]').count(),3);assert.deepEqual(errors,[]);console.log('PASS real publish, frontend persistence, three widgets retained, no browser exceptions');
 if(process.env.ZC_TEST_MIGRATION_PAGE_ID){
  await p.goto(base+'/wp-admin/themes.php?page=zarincode-split-hero');await p.locator('#zc-page-id').fill(process.env.ZC_TEST_MIGRATION_PAGE_ID);await p.locator('button[name="zc_split_hero"]').click();await p.locator('.notice-success').filter({hasText:'1 هیرو تبدیل شد'}).waitFor();
  await p.goto(base+'/?page_id='+process.env.ZC_TEST_MIGRATION_PAGE_ID,{waitUntil:'networkidle'});assert.equal(await p.locator('[data-widget_type^="zarincode_hero_"]').count(),3);assert.equal(await p.getByText('عنوان محفوظ پس از تفکیک هیرو',{exact:true}).count(),1);console.log('PASS authenticated nonce-protected admin conversion and saved title');
 }

}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
