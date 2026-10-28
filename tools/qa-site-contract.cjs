/** Isolated CSS/JS/font browser contracts, not live WordPress or PageSpeed scores.
 * NODE_PATH=tools/browser/node_modules node tools/qa-site-contract.cjs
 */
const {chromium}=require('playwright');
const fs=require('node:fs'),path=require('node:path'),http=require('node:http'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..');
(async()=>{
 let browser,origin,checks=0;
 const server=http.createServer((req,res)=>{
  const u=new URL(req.url,origin);
  const routes={'/main.css':'zarincode/assets/css/zarincode-main.css','/contract.css':'zarincode/assets/css/site-contract.css','/responsive.css':'zarincode/assets/css/responsive.css','/native.css':'zarincode-core/includes/Widgets/assets/native.css','/widgets.css':'zarincode-core/includes/Widgets/assets/widgets.css','/pro.css':'zarincode-core/includes/Widgets/assets/professional.css','/admin.css':'zarincode/assets/css/admin-typography.css','/main.js':'zarincode/assets/js/zarincode-main.js','/notice.js':'zarincode-core/assets/admin/demo-notice.js'};
  if(routes[u.pathname]){res.setHeader('Content-Type',u.pathname.endsWith('.js')?'application/javascript; charset=utf-8':'text/css; charset=utf-8');res.end(fs.readFileSync(path.join(root,routes[u.pathname])));return;}
  if(u.pathname.startsWith('/assets/')){
   const file=path.resolve(root,'zarincode','.'+u.pathname);
   if(!file.startsWith(path.join(root,'zarincode','assets')+path.sep)){res.writeHead(403);res.end();return;}
   try{res.setHeader('Content-Type',file.endsWith('.woff2')?'font/woff2':'text/css');res.end(fs.readFileSync(file));}catch(_){res.writeHead(404);res.end();}return;
  }
  if(u.pathname==='/dismiss-fail'){res.writeHead(500,{'Content-Type':'application/json'});res.end('{"success":false}');return;}
  if(u.pathname==='/dismiss'){res.setHeader('Content-Type','application/json');res.end('{"success":true}');return;}
  const admin=u.searchParams.has('admin');
  res.setHeader('Content-Type','text/html; charset=utf-8');
  res.end(`<!doctype html><html dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/vazirmatn.css">${['main','native','widgets','pro','responsive','contract',...(admin?['admin']:[])].map(s=>`<link rel="stylesheet" href="/${s}.css">`).join('')}<style>
  .elementor-section-boxed>.elementor-container{max-width:900px;margin:auto}.elementor-widget-wrap{padding:10px}.elementor-column{width:100%}.e-con-boxed,.e-con-boxed>.e-con-inner{padding-inline:10px}.e-con-boxed>.e-con-inner{max-width:var(--content-width,900px);margin:auto;width:100%}
  :root{--zc-container-width:1500px;--zc-site-gutter:32px}@media(max-width:1199px){:root{--zc-container-width:1000px;--zc-site-gutter:24px}}@media(max-width:767px){:root{--zc-container-width:600px;--zc-site-gutter:16px}}
  </style></head><body class="${admin?'wp-admin':'zarincode-theme'}">
  ${admin?`<div id="wpbody-content"><div class="zc-options-fullwidth-wrap" style="font-family:Tahoma"><h1>تنظیمات زرین کد</h1><p id="admin-copy" style="font-family:Tahoma">متن فارسی استاندارد</p><input value="فونت ورودی"><span class="dashicons" style="font-family:dashicons">x</span><code>const x=1;</code></div><div id="zc-demo-install-notice"><button class="notice-dismiss">بستن</button></div></div><script>window.ZarincodeDemoNotice={url:'${u.searchParams.has('fail')?'/dismiss-fail':'/dismiss'}',nonce:'test',retry:'دوباره تلاش کنید'};document.addEventListener('click',e=>{if(!e.target.closest('.notice-dismiss'))return;const original=e.target.closest('#zc-demo-install-notice');setTimeout(()=>{original?.remove();window.nativeRemoved=true},100)});</script><script src="/notice.js"></script>`:`<header><div class="zc-container" data-shell="header"><p>هدر</p></div></header>
  <main><div class="elementor-section elementor-section-boxed"><div class="elementor-container"><div class="elementor-column"><div class="elementor-widget-wrap"><div class="elementor-widget"><div class="elementor-widget-container"><div class="zc-native-layout"><div class="zc-container" data-shell="native"><p>محتوای بومی</p></div></div></div></div></div></div></div></div>
  <div class="zc-edit" data-shell="editable"><p>محتوا قابل ویرایش</p></div><div class="zc-pro" data-shell="pro"><p>محتوا حرفه‌ای</p></div>
  <div class="e-con zc-hero-composition" data-shell="hero"><div class="elementor-widget"><div class="zc-container"><p>هیرو</p></div></div><div></div></div>
  <div class="e-con-boxed"><div class="e-con-inner"><div class="elementor-widget"><div class="zc-native-layout"><div class="zc-container" data-shell="flex"><p>فلکس المنتور</p></div></div></div></div></div>
  </main><footer><div class="zc-container" data-shell="footer"><p>فوتر</p></div></footer><h2 style="font-family:Arial">عنوان</h2><code>const x=1;</code><i class="fa-solid" style="font-family:'Font Awesome 7 Pro'">x</i>
  <script>window.ZarincodeData={colorSwitcher:false,defaultMode:'dark',sitePalette:{id:'site',label:'سایت',accent:'#112233',accentText:'#ffffff',bgFrom:'#eeeeee',bgTo:'#ffffff',darkFrom:'#101010',darkTo:'#202020'}};</script><script src="/main.js"></script>`}</body></html>`);
 });
 await new Promise(r=>server.listen(0,'127.0.0.1',r));origin='http://127.0.0.1:'+server.address().port;
 try{
  let sandbox;try{const mod=require('@sparticuz/chromium');sandbox=mod.default||mod;}catch(_){}
  browser=await chromium.launch({executablePath:process.env.CHROMIUM_EXECUTABLE_PATH||(sandbox?await sandbox.executablePath():undefined),args:sandbox?sandbox.args:['--no-sandbox'],headless:true});
  const context=await browser.newContext();const page=await context.newPage();
  for(const width of [320,390,600,768,1024,1440,1920])for(const dir of ['rtl','ltr']){
   await page.setViewportSize({width,height:900});await page.goto(origin);await page.evaluate(dir=>document.documentElement.dir=dir,dir);
   const shells=await page.locator('[data-shell]').evaluateAll(nodes=>nodes.map(el=>{const r=el.getBoundingClientRect(),s=getComputedStyle(el);return {name:el.dataset.shell,left:r.left,right:r.right,innerLeft:r.left+parseFloat(s.paddingLeft),innerRight:r.right-parseFloat(s.paddingRight)};}));
   for(const shell of shells.slice(1)){for(const key of ['left','right','innerLeft','innerRight'])assert(Math.abs(shell[key]-shells[0][key])<1,`${width} ${dir} ${shell.name} ${key}: ${JSON.stringify(shells)}`);checks++;}
   const heroChild=await page.locator('.zc-hero-composition .zc-container').evaluate(el=>parseFloat(getComputedStyle(el).paddingLeft));assert.equal(heroChild,0);checks++;
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));checks++;
  }
  await page.evaluate(()=>document.fonts.ready);assert(await page.evaluate(()=>document.fonts.check('16px Vazirmatn')));checks++;
  assert(await page.evaluate(()=>[...document.fonts].some(font=>font.family==='Vazirmatn'&&font.status==='loaded')));checks++;
  for(const selector of ['p','h2']){assert((await page.locator(selector).first().evaluate(el=>getComputedStyle(el).fontFamily)).includes('Vazirmatn'));checks++;}
  assert((await page.locator('code').evaluate(el=>getComputedStyle(el).fontFamily)).includes('monospace'));checks++;
  assert((await page.locator('i').evaluate(el=>getComputedStyle(el).fontFamily)).includes('Font Awesome'));checks++;
  assert.equal(await page.locator('#zarincode-crextio-switcher').count(),0);assert(await page.locator('html').evaluate(el=>el.classList.contains('dark')));checks+=2;
  assert.equal(await page.locator('html').evaluate(el=>el.style.getPropertyValue('--color-accent')),'#112233');checks++;
  await page.goto(origin+'/?admin=1');await page.evaluate(()=>document.fonts.ready);
  for(const selector of ['h1','#admin-copy','input']){assert((await page.locator(selector).evaluate(el=>getComputedStyle(el).fontFamily)).includes('Vazirmatn'));checks++;}
  assert((await page.locator('.dashicons').evaluate(el=>getComputedStyle(el).fontFamily)).includes('dashicons'));checks++;
  const save=page.waitForRequest(req=>req.url().endsWith('/dismiss')&&req.postData()?.includes('action=zc_dismiss_demo_notice'));await page.locator('.notice-dismiss').click();await save;checks++;
  await page.goto(origin+'/?admin=1&fail=1');await page.locator('.notice-dismiss').click();
  await page.waitForFunction(()=>window.nativeRemoved===true&&document.querySelector('#zc-demo-install-notice')?.textContent.includes('دوباره تلاش کنید'));
  assert(await page.locator('#zc-demo-install-notice').isVisible());checks++;
  console.log(`PASS: ${checks} isolated browser assertions: shared inner/outer edges, 7 widths x RTL/LTR, local fonts, icon/code preservation, settings-driven palette/mode and dismissal request.`);
  await context.close();
 }finally{if(browser)await browser.close();await new Promise(r=>server.close(r));}
})().catch(e=>{console.error(e);process.exitCode=1;});
