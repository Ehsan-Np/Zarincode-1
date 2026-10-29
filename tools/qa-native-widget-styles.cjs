/** Browser + CSS/selector AST tests on representative HTML, NOT installed plugin integration. */
const fs=require('node:fs'),path=require('node:path'),http=require('node:http'),assert=require('node:assert/strict');
const postcss=require('postcss'),selector=require('postcss-selector-parser');
const {chromium:pw}=require('playwright'),chromium=require('@sparticuz/chromium');
const ROOT=path.resolve(__dirname,'..');let checks=0;
const ok=(v,msg)=>{assert(v,msg);checks++};
const sheets=['widgets/wordpress.css','widgets/woocommerce.css','elementor/official.css'];
for(const file of sheets){
 const css=fs.readFileSync(path.join(ROOT,'zarincode/assets/css',file),'utf8');
 postcss.parse(css).walkRules(rule=>{
  selector(ast=>ast.each(s=>ok(s.toString().includes(file.includes('wordpress')?'zc-wp-widget-skin':file.includes('woocommerce')?'zc-wc-widget-skin':'zc-elementor-widget-skin'),`unscoped ${file}: ${s}`))).processSync(rule.selector);
 });
 postcss.parse(css).walkDecls(d=>ok(!d.important,`No important overrides: ${file}`));
 ok(!/url\(\s*['"]?https?:/.test(css),'No external assets');
}
function serve(req,res){
 const name=decodeURIComponent(new URL(req.url,'http://localhost').pathname);
 const relative=name==='/'?'tools/fixtures/native-widgets.html':name.slice(1);
 const file=path.resolve(ROOT,relative);
 if(!(file.startsWith(path.join(ROOT,'zarincode/assets/'))||file===path.join(ROOT,'tools/fixtures/native-widgets.html'))||!fs.existsSync(file)||!fs.statSync(file).isFile()){res.writeHead(404);res.end();return}
 res.setHeader('Content-Type',file.endsWith('.css')?'text/css':file.endsWith('.html')?'text/html; charset=utf-8':file.endsWith('.webp')?'image/webp':file.endsWith('.woff2')?'font/woff2':'application/octet-stream');if(file.endsWith('native-widgets.html')&&process.env.ZC_NATIVE_WP_MARKUP){const html=fs.readFileSync(file,'utf8').replace(/(<section class="lab-grid" aria-label="ابزارک‌های وردپرس">)[\s\S]*?<\/section>/,'$1'+fs.readFileSync(process.env.ZC_NATIVE_WP_MARKUP,'utf8')+'</section>');res.end(html)}else res.end(fs.readFileSync(file));
}
if(process.argv.includes('--serve')){http.createServer(serve).listen(4173,'0.0.0.0',()=>console.log('Native widget design preview on 4173'));}
else (async()=>{
 const server=http.createServer(serve);await new Promise(r=>server.listen(0,'127.0.0.1',r));let browser;
 try{
  browser=await pw.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:true});const page=await browser.newPage();
  const errors=[];page.on('pageerror',e=>errors.push(String(e)));
  for(const dir of ['rtl','ltr'])for(const dark of [false,true])for(const width of [320,375,430,768,1024,1440]){
   await page.setViewportSize({width,height:1000});await page.goto(`http://127.0.0.1:${server.address().port}/`,{waitUntil:'networkidle'});
   await page.evaluate(({dir,dark})=>{document.documentElement.dir=dir;document.documentElement.classList.toggle('dark',dark)},{dir,dark});
   ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),`No overflow ${width}/${dir}/${dark}`);
   // Move generated CSS before the skin; winning must be specificity-based, not source order.
   await page.evaluate(()=>{const s=document.createElement('style');s.textContent='.elementor-999 .elementor-element.elementor-element-author .elementor-button{background:rgb(32,76,139);color:white;border-radius:4px}';document.querySelector('style').textContent=document.querySelector('style').textContent.replace(/\.elementor-999[^}]+}/,'');document.head.prepend(s)});
   const state=await page.evaluate(()=>{const css=s=>getComputedStyle(document.querySelector(s));return {button:css('.elementor-widget-button .elementor-button').backgroundColor,field:css('.search-field').color,ink:getComputedStyle(document.body).getPropertyValue('--zc-widget-ink'),surface:css('.widget_search').backgroundColor,radius:css('.widget_search').borderRadius,author:css('#author-button').backgroundColor,custom:css('#custom-widget').borderRadius,columns:css('.woocommerce ul.products').gridTemplateColumns.split(' ').length}});
   ok(state.button==='rgb(253, 208, 91)','Brand yellow button');ok(state.field===(dark?'rgb(243, 244, 246)':'rgb(31, 41, 55)'),'Readable field follows light/dark');
   ok(state.surface===(dark?'rgb(36, 38, 44)':'rgb(255, 255, 255)'),'Native widget light/dark surface');ok(parseFloat(state.radius)>=16&&parseFloat(state.radius)<=26,'Bento card radius: '+state.radius);
   ok(state.author==='rgb(32, 76, 139)','Explicit author control wins');ok(state.custom==='4px','Custom widget untouched');
   ok(state.columns===(width<=480?1:width<=1024?2:3),'Responsive Woo columns');
   const contrast=await page.evaluate(()=>{
    const rgb=s=>(s.match(/[\d.]+/g)||[]).slice(0,3).map(Number);
    const lum=c=>c.map(x=>x/255).map(x=>x<=.04045?x/12.92:((x+.055)/1.055)**2.4).reduce((n,v,i)=>n+v*[.2126,.7152,.0722][i],0);
    const ratio=(a,b)=>{a=lum(rgb(a));b=lum(rgb(b));return (Math.max(a,b)+.05)/(Math.min(a,b)+.05)};
    const button=getComputedStyle(document.querySelector('.elementor-widget-button .elementor-button')),field=getComputedStyle(document.querySelector('.search-field')),error=getComputedStyle(document.querySelector('.wc-block-components-validation-error')),surface=getComputedStyle(document.querySelector('.wc-block-checkout'));
    return [ratio(button.color,button.backgroundColor),ratio(field.color,field.backgroundColor),ratio(error.color,surface.backgroundColor)];
   });
   ok(contrast.every(x=>x>=4.5),'Representative button/field/error text contrast >=4.5');
   await page.locator('.elementor-widget-button .elementor-button').first().focus();
   ok(await page.locator('.elementor-widget-button .elementor-button').first().evaluate(e=>getComputedStyle(e).outlineStyle==='solid'),'Visible keyboard focus');
   await page.locator('.e-n-accordion-item').last().locator('summary').click();ok(await page.locator('.e-n-accordion-item').last().evaluate(e=>e.open),'Native disclosure remains operable');
   if(dir==='rtl'&&width===1440&&!dark)await page.screenshot({path:process.env.ZC_WIDGET_SCREENSHOT||'/tmp/zc-native-widgets-desktop.png',fullPage:true});
   if(dir==='rtl'&&width===375&&dark)await page.screenshot({path:'/tmp/zc-native-widgets-mobile-dark.png',fullPage:true});
  }
  await page.emulateMedia({reducedMotion:'reduce'});await page.locator('.elementor-widget-button .elementor-button').first().hover();
  ok(await page.locator('.elementor-widget-button .elementor-button').first().evaluate(e=>getComputedStyle(e).transform==='none'),'Reduced motion includes hover');
  await page.evaluate(()=>document.body.style.setProperty('--color-accent','#f2c54b'));
  ok(await page.locator('.elementor-widget-button .elementor-button').first().evaluate(e=>getComputedStyle(e).backgroundColor==='rgb(242, 197, 75)'),'Configured accent propagates');
  await page.evaluate(()=>document.body.classList.remove('zc-elementor-widget-skin','zc-wp-widget-skin','zc-wc-widget-skin'));
  ok(await page.locator('.widget_search').evaluate(e=>getComputedStyle(e).borderRadius==='0px'),'Skin opt-out actually removes widget style');
  ok(await page.locator('.woocommerce ul.products').evaluate(e=>getComputedStyle(e).display==='block'),'Woo scope opt-out');
  await page.locator('.elementor-widget-button .elementor-button').first().evaluate(e=>e.style.setProperty('transition','none'));
  ok(await page.locator('.elementor-widget-button .elementor-button').first().evaluate(e=>getComputedStyle(e).backgroundColor!=='rgb(242, 197, 75)'),'Elementor scope opt-out');
  ok(!errors.length,'No browser script errors');
  console.log(`PASS: ${checks} selector/declaration/browser assertions; 24 viewport/direction/theme cases. HTML fixtures, not real Elementor/WooCommerce lifecycle/payment integration.`);
 }finally{if(browser)await browser.close();await new Promise(r=>server.close(r));}
})().catch(e=>{console.error(e);process.exitCode=1});
