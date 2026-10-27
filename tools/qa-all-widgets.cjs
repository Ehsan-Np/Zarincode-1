/* Real installed-version fixtures; automated accessibility is not full WCAG certification. */
const fs=require('fs'),path=require('path');
const {caseKey,resumableCases,evaluate}=require('./widget-audit-contract.cjs');
const {chromium}=require('playwright');const {AxeBuilder}=require('@axe-core/playwright');
const root=process.env.ZC_QA_ROOT;
if(!root||!root.startsWith('/tmp/zc-'))throw Error('Disposable fixture root required');
const inventory=JSON.parse(fs.readFileSync(root+'/inventory.json'));
const base=new URL(inventory.home).origin;
const out=root+'/'+(process.env.ZC_QA_OUTPUT||'all-widgets')+'.json';
const customOnly=process.env.ZC_QA_CUSTOM_ONLY==='1';
(async()=>{
 const browser=await chromium.launch({headless:true});let cursor=0;
 const previous=process.env.ZC_QA_RESUME==='1'&&fs.existsSync(out)?JSON.parse(fs.readFileSync(out)).results:[];
 let jobs=inventory.widgets.filter(x=>x.status==='fixture_created'&&(!customOnly||x.custom)).flatMap(x=>[
  {...x,width:360,locale:'en_US'}, {...x,width:1440,locale:'fa_IR'}
 ]);
 const extras=[['home',inventory.home],['product',inventory.product],['blocks',inventory.blocks]];
 if(!customOnly)for(const [name,url]of extras)if(url)for(const [width,locale]of [[360,'en_US'],[1440,'fa_IR']])jobs.push({widget:name,url,width,locale,custom:false,extras:true});
 const expected=jobs.slice();const results=resumableCases(expected,previous);
 const completed=new Set(results.map(caseKey));jobs=jobs.filter(r=>!completed.has(caseKey(r)));
 const fixtureFailures=inventory.widgets.filter(w=>!['fixture_created','internal_control_provider'].includes(w.status)).map(w=>({widget:w.widget,status:w.status,error:w.error}));
 try {await Promise.all(Array.from({length:Number(process.env.ZC_QA_WORKERS||1)},async()=>{
  while(cursor<jobs.length){
   const job=jobs[cursor++];if(!job)break; // Claim synchronously before any await so workers cannot race the final jobs.
   const context=await browser.newContext({reducedMotion:'reduce'});const page=await context.newPage();
   await page.route('**/*',route=>{const u=route.request().url();return u.startsWith(base)||u.startsWith('data:')||u.startsWith('https://cdnjs.cloudflare.com/')?route.continue():route.abort();});
const errors=[];const listener=e=>errors.push(e.message);page.on('pageerror',listener);
   let record={widget:job.widget,width:job.width,locale:job.locale,custom:job.custom,requires_context:job.requires_context||false};
   try{
    await page.setViewportSize({width:job.width,height:1000});const url=new URL(job.url);url.searchParams.set('qa_locale',job.locale);url.searchParams.set('qa_run',process.env.ZC_QA_OUTPUT||'initial');
    const response=await page.goto(url.toString(),{waitUntil:'load',timeout:45000});await page.waitForTimeout(200);
    const metrics=await page.evaluate(name=>{const w=document.querySelector('[data-widget_type^="'+name+'."]');return{direction:document.documentElement.dir,overflow:document.documentElement.scrollWidth-innerWidth,widget_present:!!w,widget_text:w?.innerText?.slice(0,120)||'',widget_html_length:w?.innerHTML?.length||0,dom_nodes:document.querySelectorAll('*').length,body_text:document.body.innerText.slice(0,200),assets:performance.getEntriesByType('resource').filter(r=>/\.(css|js)(\?|$)/.test(r.name)).map(r=>({url:r.name,bytes:r.transferSize,duration:r.duration}))}},job.widget);
    const axe=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
    record={...record,http:response.status(),...metrics,errors,violations:axe.violations.map(v=>({id:v.id,impact:v.impact,help:v.help,helpUrl:v.helpUrl,nodes:v.nodes.map(n=>({target:n.target,html:n.html.slice(0,600),summary:n.failureSummary}))})),incomplete:axe.incomplete.map(v=>({id:v.id,nodes:v.nodes.length})),passes:axe.passes.length};
    if(job.custom&&process.env.ZC_QA_SCREENSHOTS==='1'){fs.mkdirSync(root+'/screenshots',{recursive:true});await page.screenshot({path:root+'/screenshots/'+job.widget+'-'+job.width+'.png',fullPage:true});}
   }catch(e){record.error=String(e);}
   page.off('pageerror',listener);results.push(record);fs.writeFileSync(out,JSON.stringify({environment:inventory.environment,scope:'Automated Chromium/axe checks, not complete manual WCAG or editor parity certification',results},null,2));console.log(results.length,job.widget,job.locale,record.http,record.violations?.length??record.error);await context.close();
  }
 }));}finally{await browser.close();}
 const summary=evaluate(expected,results,fixtureFailures);
 fs.writeFileSync(out,JSON.stringify({environment:inventory.environment,scope:summary.scope,results,summary},null,2));
 console.log(JSON.stringify(summary));if(!summary.passed)process.exitCode=1;
})().catch(e=>{console.error(e);process.exit(1)});
