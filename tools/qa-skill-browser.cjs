/* Requires playwright and a disposable local QA installation. No production option writes. */
const {chromium}=require('playwright');
const fs=require('fs');const {execFileSync}=require('child_process');
const root=process.env.ZC_QA_ROOT;if(!root||!fs.existsSync(root+'/block-id'))throw Error('Explicit disposable fixture root required');
const wp=(...args)=>execFileSync('php',[root+'/wp.phar',...args,'--path='+root+'/wordpress','--allow-root'],{encoding:'utf8',stdio:['ignore','pipe','pipe']});
if(!wp('eval',"echo defined('ZARINCODE_DISPOSABLE_QA') && ZARINCODE_DISPOSABLE_QA && wp_get_environment_type()==='local' ? 'ZC_SAFE_FIXTURE' : 'refuse';").includes('ZC_SAFE_FIXTURE'))throw Error('Refusing non-disposable installation');
const base='http://localhost:8092';const posts=JSON.parse(fs.readFileSync(root+'/posts.json','utf8').split('PHP:')[0].trim());
const paths=[['home','/'],['blocks','/?page_id='+fs.readFileSync(root+'/block-id','utf8')],['product','/?post_type=product&p='+posts.find(p=>p.post_type==='product').ID],['service','/?post_type=zc_service&p='+posts.find(p=>p.post_type==='zc_service').ID]];
(async()=>{const browser=await chromium.launch({headless:true});const page=await browser.newPage();const results=[];await page.route('**/*',route=>route.request().url().startsWith(base)?route.continue():route.abort());
try{const css=await page.request.get(base+'/wp-content/themes/zarincode/assets/css/responsive.css');if(!css.ok())throw Error('QA stylesheet URL invalid');for(const locale of ['fa_IR','en_US']){wp('option','update','WPLANG',locale==='en_US'?'':locale);
 for(const width of [320,768,1440])for(const [name,path]of paths){await page.setViewportSize({width,height:1000});const response=await page.goto(base+path+(path.includes('?')?'&':'?')+'qa='+locale+width,{waitUntil:'networkidle'});
 await page.waitForTimeout(300); const data=await page.evaluate(()=>({direction:document.documentElement.dir,overflow:document.documentElement.scrollWidth-innerWidth,main:!!document.querySelector('#primary'),skip:!!document.querySelector('.zc-skip-link'),blockStyle:!!document.querySelector('#zarincode-blocks-css'),title:document.title}));
 results.push({locale,width,page:name,http:response.status(),...data,passed:response.status()===200&&data.direction===(locale==='fa_IR'?'rtl':'ltr')&&data.overflow<=1&&data.main&&data.skip&&(name!=='blocks'||data.blockStyle)});console.log(locale,width,name,results[results.length-1].passed);fs.writeFileSync(root+'/browser-progress.json',JSON.stringify(results,null,2));
 }
}
wp('eval',"$o=get_option('zarincode_opt');$o['feature_native_widgets']=false;update_option('zarincode_opt',$o);");await page.goto(base+'/?qa=disabled',{waitUntil:'networkidle'});
const disabled=await page.evaluate(()=>({widgets:document.querySelectorAll('[data-widget_type^="zarincode_"]').length,assets:[...document.querySelectorAll('link[id],script[id]')].map(x=>x.id).filter(x=>/^zarincode-(native|studio|professional|editable-widgets)-(css|js)$/.test(x))}));
fs.writeFileSync(root+'/browser.json',JSON.stringify({results,disabled,limitations:['Chromium only; not exhaustive visual, WCAG or official-widget tests.']},null,2));console.log(JSON.stringify({pages:results.length,failed:results.filter(r=>!r.passed),disabled},null,2));
}finally{wp('eval',"$o=get_option('zarincode_opt');$o['feature_native_widgets']=true;update_option('zarincode_opt',$o);");await browser.close();}})().catch(e=>{console.error(e);process.exit(1)});
