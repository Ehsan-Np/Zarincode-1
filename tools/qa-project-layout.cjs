// Responsive browser contract over the PHP-rendered template fixture (WP doubles).
const fs=require('node:fs'),http=require('node:http'),assert=require('node:assert/strict');
const {chromium:pw}=require('playwright-core'),chromium=require('@sparticuz/chromium');
(async()=>{
 const html=fs.readFileSync(process.argv[2],'utf8');let checks=0;
 const server=http.createServer((req,res)=>{res.setHeader('Content-Type','text/html; charset=utf-8');res.end(html)});
 await new Promise(r=>server.listen(0,'127.0.0.1',r));let browser;
 try{
  browser=await pw.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:true});
  const page=await browser.newPage();
  for(const width of [320,359,390,430,768,1024,1440]){
   await page.setViewportSize({width,height:900});
   await page.goto(`http://127.0.0.1:${server.address().port}/`);
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),`no page overflow at ${width}`);checks++;
   assert.equal(await page.locator('h1').count(),1);checks++;
   const bounds=await page.locator('.zc-project-cover img').boundingBox();assert(bounds.width<=width);checks++;
   const layout=await page.locator('.zc-project-sidebar').evaluate(e=>getComputedStyle(e).position);
   assert.equal(layout,width<768?'static':'sticky');checks++;
   if(width<768){
    const rail=await page.locator('.zc-filter-tabs').evaluate(e=>({height:e.getBoundingClientRect().height,scroll:e.scrollWidth,client:e.clientWidth,wrap:getComputedStyle(e).flexWrap}));
    assert(rail.height<=70,`compact one-line rail ${width}`);checks++;
    assert.equal(rail.wrap,'nowrap');checks++;
    assert(rail.scroll>rail.client);checks++;
    const ys=await page.locator('.zc-filter-tab').evaluateAll(xs=>xs.map(e=>Math.round(e.getBoundingClientRect().y)));
    assert.equal(new Set(ys).size,1);checks++;
    assert(await page.locator('.zc-filter-tab').first().evaluate(e=>e.getBoundingClientRect().height>=44));checks++;
   }
   await page.locator('[data-filter="c2"]').click();
   assert(await page.locator('[data-filter="c2"]').evaluate(e=>e.classList.contains('is-active')));checks++;
   assert.equal(await page.locator('[data-category="c1"]').isVisible(),false);checks++;
   await page.locator('[data-filter="all"]').click();assert(await page.locator('[data-category="c1"]').isVisible());checks++;
   if(width===390)await page.screenshot({path:'/tmp/zc-portfolio-mobile.png',fullPage:true});

  }
  console.log(`PASS: ${checks} responsive project/filter assertions at 7 viewport widths; template APIs are doubles, browser and CSS/legacy filtering are real.`);
 }finally{if(browser)await browser.close();await new Promise(r=>server.close(r));}
})().catch(e=>{console.error(e);process.exitCode=1});
