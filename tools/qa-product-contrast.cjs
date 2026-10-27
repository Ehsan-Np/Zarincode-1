'use strict';
const fs=require('fs'),path=require('path'),{chromium}=require('playwright'),{AxeBuilder}=require('@axe-core/playwright');
(async()=>{const browser=await chromium.launch();const results=[];try{
 for(const width of [320,1440])for(const locale of ['en_US','fa_IR']){
 const context=await browser.newContext({viewport:{width,height:1000}});const page=await context.newPage();
 await page.setContent(`<html lang="${locale==='fa_IR'?'fa':'en'}" dir="${locale==='fa_IR'?'rtl':'ltr'}"><head><title>Product review contrast regression</title></head><body><main><section class="zc-product-discussion"><div class="zc-review-score"><span>0 نقد ثبت‌شده</span></div><div id="reviews"><div id="review_form_wrapper"><p class="zc-review-note">تجربه واقعی خود را بنویسید؛ ایمیل شما منتشر نمی‌شود. دیدگاه‌ها طبق سیاست سایت بررسی می‌شوند.</p></div></div></section></main></body></html>`);
 await page.addStyleTag({content:fs.readFileSync(path.join(__dirname,'../zarincode/assets/css/single-experience.css'),'utf8')});
 const scan=await new AxeBuilder({page}).include('.zc-review-score').include('.zc-review-note').withRules(['color-contrast']).analyze();
 const colors=await page.locator('.zc-review-score>span,.zc-review-note').evaluateAll(nodes=>nodes.map(n=>({color:getComputedStyle(n).color,background:getComputedStyle(n.parentElement).backgroundColor})));
 results.push({width,locale,colors,violations:scan.violations,incomplete:scan.incomplete.map(x=>x.id),passed:scan.violations.length===0&&scan.incomplete.length===0&&colors.every(c=>c.color==='rgb(100, 104, 115)')});await context.close();
 }
}finally{await browser.close();}
const report={scope:'Isolated regression of two previously failing product-review text colors using production CSS. Not a full product-page, visual-regression or WCAG certification.',results,passed:results.every(r=>r.passed)};
if(process.env.ZC_QA_REPORT)fs.writeFileSync(process.env.ZC_QA_REPORT,JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));if(!report.passed)process.exitCode=1;
})().catch(e=>{console.error(e);process.exit(1)});
