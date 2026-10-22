/* Actual PHP-rendered admin rows; idempotent JS add/reorder/remove, no mocked handlers. */
const fs=require('fs'),{chromium}=require('playwright');
const root=process.env.ZC_QA_ROOT;
if(!root||!root.startsWith('/tmp/zc-'))throw Error('Disposable fixture only');
(async()=>{const browser=await chromium.launch();const checks={};try{
 const page=await browser.newPage();await page.setContent(fs.readFileSync(root+'/admin-editor-fixture.html','utf8'));
 for(const script of ['zarincode-core/assets/admin/policy-manager.js','zarincode/assets/js/social-manager.js'])for(let n=0;n<2;n++)await page.addScriptTag({content:fs.readFileSync(script,'utf8')});
 await page.locator('.zc-policy-add').click();checks.policy_single_add=await page.locator('.zc-policy-list .zc-policy-row').count()===2;
 await page.locator('.zc-policy-list .zc-policy-row').last().locator('.zc-policy-up').click();checks.policy_reordered=await page.locator('.zc-policy-list .zc-policy-row').last().locator('input[type="hidden"]').first().inputValue()==='stable-policy';
 await page.locator('.zc-policy-list .zc-policy-row').first().locator('.zc-policy-remove').click();checks.policy_id_preserved=await page.locator('.zc-policy-list .zc-policy-row input[type="hidden"]').inputValue()==='stable-policy';
 await page.locator('#zc-social-add').click();checks.social_single_add=await page.locator('#zc-social-admin-list .zc-social-admin-row').count()===2;
 await page.locator('#zc-social-admin-list .zc-social-admin-row').last().locator('.zc-social-up').click();checks.social_reordered=await page.locator('#zc-social-admin-list .zc-social-admin-row').last().locator('input[type="hidden"]').first().inputValue()==='stable-social';
 await page.locator('#zc-social-admin-list .zc-social-admin-row').first().locator('.zc-social-remove').click();checks.social_id_preserved=await page.locator('#zc-social-admin-list .zc-social-admin-row input[type="hidden"]').first().inputValue()==='stable-social';
 await page.locator('.zc-policy-list .zc-policy-remove').click();checks.policy_empty_submission=await page.locator('input[name="zc_policy[faqs]"]').inputValue()==='' && await page.locator('.zc-policy-list .zc-policy-row').count()===0;
 await page.locator('#zc-social-admin-list .zc-social-remove').click();checks.social_empty_submission=await page.evaluate(()=>new FormData(document.querySelector('#zc-social-add').closest('form')).get('zc_socials')==='');
 const result={scope:'Real server-rendered fields and twice-evaluated production scripts. Media picker not covered.',checks,passed:Object.values(checks).every(Boolean)};
 fs.writeFileSync(root+'/admin-editors.json',JSON.stringify(result,null,2));console.log(JSON.stringify(result,null,2));if(!result.passed)process.exitCode=1;
}finally{await browser.close()}})().catch(e=>{console.error(e);process.exit(1)});
