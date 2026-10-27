const fs=require('fs'),{execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=process.env.ZC_QA_ROOT;if(!root||!root.startsWith('/tmp/zc-'))throw Error('Disposable fixture only');
const data=JSON.parse(fs.readFileSync(root+'/commerce-pages.json'));const base='http://127.0.0.1:8093';
const wp=(...args)=>execFileSync('php',['-d','xdebug.mode=off',root+'/wp.phar','--allow-root','--path='+root+'/wordpress',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']});
(async()=>{const browser=await chromium.launch({headless:true});const results=[];
try{for(const mode of ['classic','block']){
 wp('option','update','woocommerce_cart_page_id',String(data[mode+'_cart']));wp('option','update','woocommerce_checkout_page_id',String(data[mode+'_checkout']));
 const context=await browser.newContext();const page=await context.newPage();await page.route('**/*',r=>r.request().url().startsWith(base)?r.continue():r.abort());
 const r={mode};try{
 await page.goto(base+'/?add-to-cart='+data.product,{waitUntil:'networkidle'});
 await page.goto(base+'/?page_id='+data[mode+'_cart'],{waitUntil:'networkidle'});await page.getByText('QA checkout product',{exact:false}).first().waitFor({state:'visible',timeout:15000}).catch(()=>{});r.cartText=await page.locator('body').innerText();r.cartContainsProduct=r.cartText.includes('QA checkout product');delete r.cartText;
 await page.goto(base+'/?page_id='+data[mode+'_checkout'],{waitUntil:'networkidle'});
 if(mode==='classic'){
  for(const [id,v]of Object.entries({billing_first_name:'QA',billing_last_name:'Buyer',billing_address_1:'1 Market Street',billing_city:'San Francisco',billing_postcode:'94107',billing_phone:'4155550100',billing_email:'qa@example.test'})){const field=page.locator('#'+id);if(await field.count())await field.fill(v);}
  if(await page.locator('#billing_country').count())await page.locator('#billing_country').selectOption('US');
  if(await page.locator('#billing_state').count())await page.locator('#billing_state').selectOption('CA');
  await page.waitForTimeout(1000);if(await page.locator('#payment_method_cod').count())await page.locator('#payment_method_cod').check();
  if(await page.locator('#terms').count())await page.locator('#terms').check();
  await page.locator('#place_order').click();
 }else{
  await page.locator('#email').waitFor({state:'visible',timeout:20000}); // Block checkout hydrates fields through the Store API.
  const fields={'#email':'qa@example.test','#billing-first_name':'QA','#billing-last_name':'Buyer','#billing-address_1':'1 Market Street','#billing-city':'San Francisco','#billing-postcode':'94107','#billing-phone':'4155550100'};
  for(const [selector,value]of Object.entries(fields)){const f=page.locator(selector);if(await f.count())await f.fill(value);}
  const country=page.locator('#billing-country');if(await country.count())await country.selectOption('US');const state=page.locator('#billing-state');if(await state.count())await state.selectOption('CA');
  const cod=page.locator('input[value="cod"]');if(await cod.count())await cod.check();
  const zip=page.getByLabel('ZIP Code',{exact:true});if(await zip.count())await zip.fill('94107');
  r.formValues=await page.locator('input').evaluateAll(list=>list.map(e=>({id:e.id,type:e.type,value:e.value})));
  await page.locator('.wc-block-components-checkout-place-order-button').click();
 }
 await page.waitForFunction(()=>/order-received/.test(location.href)||!!document.querySelector('.woocommerce-order-received'),{timeout:30000});
 r.orderReceived=/order-received/.test(page.url())||await page.locator('.woocommerce-order-received').count()>0;r.passed=r.cartContainsProduct&&r.orderReceived;
 }catch(e){r.error=String(e);r.visibleErrors=await page.locator('.woocommerce-error,.wc-block-components-notice-banner,[role=alert]').allTextContents().catch(()=>[]);r.passed=false;await page.screenshot({path:root+'/checkout-'+mode+'.png',fullPage:true});}
 results.push(r);await context.close();fs.writeFileSync(root+'/commerce-browser.json',JSON.stringify({scope:'Real browser guest checkout with offline COD only. No money moved or live gateway integration.',results},null,2));console.log(r);
}}finally{await browser.close();}if(results.some(r=>!r.passed))process.exitCode=1;
})().catch(e=>{console.error(e);process.exit(1)});
