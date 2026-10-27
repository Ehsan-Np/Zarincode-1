'use strict';
const {test}=require('node:test'),assert=require('node:assert/strict');
const {caseFailures,resumableCases,evaluate}=require('./widget-audit-contract.cjs');
const job={widget:'zarincode_hero',width:360,locale:'en_US',custom:true};
const good={...job,http:200,direction:'ltr',overflow:0,errors:[],violations:[],widget_present:true};
test('complete recorded baseline passes without claiming full conformance',()=>assert.equal(evaluate([job],[good]).passed,true));
test('zero cases cannot pass',()=>assert.equal(evaluate([],[]).passed,false));
test('missing, duplicate, unexpected and fixture-failed cases fail',()=>{
 assert.equal(evaluate([job],[]).passed,false);assert.equal(evaluate([job],[good,good]).passed,false);
 assert.equal(evaluate([job],[good,{...good,widget:'other'}]).passed,false);assert.equal(evaluate([job],[good],['fixture failed']).passed,false);
 assert.equal(evaluate([job,job],[good]).passed,false);
});
test('each captured failure and absent metric fails closed',()=>{
 for(const change of [{http:500},{error:'timeout'},{errors:['ReferenceError']},{errors:undefined},{overflow:2},{overflow:undefined},{direction:'rtl'},{locale:'unknown'},{violations:[{id:'label'}]},{violations:undefined},{widget_present:false}])assert.ok(caseFailures({...good,...change}).length,JSON.stringify(change));
});
test('resume retries JS, direction and custom-output failures and discards foreign cases',()=>{
 assert.deepEqual(resumableCases([job],[{...good,errors:['ReferenceError']}]),[]);
 assert.deepEqual(resumableCases([job],[{...good,direction:'rtl'}]),[]);
 assert.deepEqual(resumableCases([job],[{...good,widget_present:false}]),[]);
 assert.deepEqual(resumableCases([job],[{...good,widget:'other'}]),[]);
 assert.deepEqual(resumableCases([job],[good,good]),[good]);
});
test('RTL and measurement tolerance are explicit',()=>{
 assert.equal(caseFailures({...good,locale:'fa_IR',direction:'rtl',overflow:1}).length,0);
});
