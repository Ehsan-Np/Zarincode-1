const fs=require('fs'),{chromium}=require('playwright');const root=process.env.ZC_QA_ROOT;
if(!root||!root.startsWith('/tmp/zc-'))throw Error('Disposable fixture only');
(async()=>{const browser=await chromium.launch();const results=[];const page=await browser.newPage({reducedMotion:'reduce'});try{
for(const [width,locale]of [[320,'fa_IR'],[768,'en_US'],[1440,'fa_IR']]){
 await page.setViewportSize({width,height:900});await page.goto('http://127.0.0.1:8093/?qa_locale='+locale,{waitUntil:'networkidle'});
 const checks={};await page.keyboard.press('Tab');checks.skip_link_focused=await page.locator('.zc-skip-link').evaluate(e=>e===document.activeElement);await page.keyboard.press('Enter');checks.skip_targets_main=await page.evaluate(()=>location.hash==='#primary'||document.activeElement?.id==='primary');
 await page.keyboard.press('Control+k');await page.waitForTimeout(150);
 const dialog=page.locator('[role="dialog"]:visible').first();checks.search_open=await dialog.count()>0;
 if(checks.search_open){checks.focus_inside=await dialog.evaluate(e=>e.contains(document.activeElement));await page.keyboard.press('Shift+Tab');checks.reverse_trap=await dialog.evaluate(e=>e.contains(document.activeElement));for(let i=0;i<12;i++)await page.keyboard.press('Tab');checks.forward_trap=await dialog.evaluate(e=>e.contains(document.activeElement));await page.keyboard.press('Escape');checks.escape_closes=await page.locator('[role="dialog"]:visible').count()===0;checks.no_inert_leak=await page.locator('[inert]').count()===0;}
 checks.no_x_overflow=await page.evaluate(()=>document.documentElement.scrollWidth-innerWidth<=1);
 results.push({width,locale,checks,passed:Object.values(checks).every(Boolean)});
}
}finally{await browser.close();}fs.writeFileSync(root+'/keyboard.json',JSON.stringify({scope:'Chromium keyboard/skip-link/modal/reflow checks only. Not assistive-technology or complete WCAG conformance testing.',results},null,2));console.log(results);if(results.some(r=>!r.passed))process.exitCode=1;
})().catch(e=>{console.error(e);process.exit(1)});
