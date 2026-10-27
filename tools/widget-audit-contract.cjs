'use strict';
// Pure audit accounting: never infer functionality or WCAG certification from a green baseline.
const caseKey = r => `${r.widget}|${r.width}|${r.locale}`;
function caseFailures(r) {
  const failures=[];
  if(r.error) failures.push('navigation_or_audit_error');
  if(r.http!==200) failures.push('http_failure');
  if(!Array.isArray(r.errors)||r.errors.length) failures.push('javascript_errors_or_missing_capture');
  if(!Number.isFinite(r.overflow)||r.overflow>1) failures.push('overflow_or_missing_measurement');
  if(!['en_US','fa_IR'].includes(r.locale)||r.direction!==(r.locale==='fa_IR'?'rtl':'ltr')) failures.push('wrong_or_missing_direction');
  if(!Array.isArray(r.violations)||r.violations.length) failures.push('axe_violations_or_missing_audit');
  if(r.custom&&r.widget_present!==true) failures.push('custom_widget_missing');
  return failures;
}
function resumableCases(expected,previous) {
  const wanted=new Set(expected.map(caseKey)),unique=new Map();
  for(const r of previous)if(wanted.has(caseKey(r))&&!caseFailures(r).length)unique.set(caseKey(r),r);
  return [...unique.values()];
}
function evaluate(expected,results,fixtureFailures=[]) {
  const wanted=new Set(expected.map(caseKey)),seen=new Set(),duplicates=[],unexpected=[],failed=[];
  for(const r of results){const key=caseKey(r);if(seen.has(key))duplicates.push(key);seen.add(key);if(!wanted.has(key))unexpected.push(key);const reasons=caseFailures(r);if(reasons.length)failed.push({key,reasons});}
  const missing=[...wanted].filter(k=>!seen.has(k));
  return {expected_cases:wanted.size,recorded_cases:results.length,missing,duplicates,unexpected,failed,fixture_failures:fixtureFailures,passed:wanted.size>0&&expected.length===wanted.size&&!missing.length&&!duplicates.length&&!unexpected.length&&!failed.length&&!fixtureFailures.length,scope:'Baseline automated checks only; not full editor, functionality, visual or WCAG certification.'};
}
module.exports={caseKey,caseFailures,resumableCases,evaluate};
