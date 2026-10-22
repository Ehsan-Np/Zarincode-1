"""Apply 3.2 model extensions after build-native-models.php. Idempotent; no vendor code."""
import json,copy,pathlib,re
root=pathlib.Path(__file__).resolve().parents[1]/'zarincode-core/includes/Widgets/models'
def load(k):return json.loads((root/(k+'.json')).read_text())
def save(k,m):(root/(k+'.json')).write_text(json.dumps(m,ensure_ascii=False,indent=4)+'\n')
def walk(nodes):
 for n in nodes:
  if isinstance(n,dict):
   yield n
   yield from walk(n.get('children',[]))
def cls(n):return n.get('attrs',{}).get('class','') if isinstance(n.get('attrs'),dict) else ''
hero=load('hero')
for kind,target in [('hero_intro','zc-hero-main-card'),('hero_profile','zc-dark-card'),('hero_cta','zc-yellow-card')]:
 m=copy.deepcopy(hero);m['kind']=kind
 m['tree']=[copy.deepcopy(next(n for n in walk(hero['tree']) if target in cls(n).split()))]
 refs=set()
 for n in walk(m['tree']):
  for v in n.values():
   if isinstance(v,str):refs.add(v)
  refs.update(n.get('bindings',{}).values())
 reps={g for g in m['repeaters'] if g in refs};m['repeaters']={g:r for g,r in m['repeaters'].items() if g in reps}
 m['fields']={f:d for f,d in m['fields'].items() if f in refs}
 parts={n.get('part') for n in walk(m['tree'])}
 for r in m['repeaters'].values():
  for n in walk(list(r['variants'].values())):parts.add(n.get('part'))
 m['parts']={p:d for p,d in m['parts'].items() if p in parts}
 m['legacy']={p:d for p,d in m['legacy'].items() if (d['target'][0] in m['repeaters'] if d['target'][0] else d['target'][1] in m['fields'])}
 save(kind,m)
for kind in ['products','services','portfolio','blog','testimonials','page_section_services_page','page_section_projects_page']:
 m=load(kind)
 for g,r in m['repeaters'].items():
  if 'grid_items' not in g:continue
  avatar=kind=='testimonials';key='customer_photo' if avatar else 'featured_image';part=g+'_'+key
  r['fields'][key]={'type':'media','default':{'url':'','id':0},'label':'تصویر مشتری' if avatar else 'تصویر شاخص','variants':list(r['variants'])}
  for n in r['variants'].values():
   if not any(x.get('image')==key for x in walk([n])):
    n['children'].insert(0,{'tag':'img','attrs':{'class':'zc-customer-photo' if avatar else 'zc-record-image','loading':'lazy','decoding':'async'},'part':part,'image':key,'image_preset':'zarincode-avatar' if avatar else 'zarincode-card'})
  for n in walk(list(r['variants'].values())):
   if kind=='products':
    if 'zc-product-thumb' in cls(n).split():n['fallback_image_for']='featured_image'
    purpose=r['fields'].get(n.get('text'),{}).get('purpose')
    if purpose=='price':n['commerce_price']=True
    if purpose=='currency':n['commerce_currency']=True
  m['parts'][part]={'label':'تصویر مشتری' if avatar else 'تصویر شاخص کارت','type':'img'}
 save(kind,m)
# Stable explicit association avoids unsupported nested Repeaters and positional coupling.
m=load('pricing')
for g,r in m['repeaters'].items():
 r['fields']['plan_key']={'type':'text','default':'','label':'شناسه یکتای پلن','description':'برای پلن جدید شناسه یکتا بنویسید؛ برای حفظ ارتباط امکانات، شناسه پلن موجود را بدون اصلاح مقصد امکانات تغییر ندهید.','variants':list(r['variants'])}
 r['fields']['features_source']={'type':'select','default':'legacy','label':'مدیریت امکانات','options':{'legacy':'امکانات قبلی (سازگاری)','icon_list':'فهرست آیکون قابل افزایش'},'variants':list(r['variants'])}
 for row in r['default']:row.setdefault('plan_key',row['_id'])
 for n in walk(list(r['variants'].values())):
  if 'zc-pricing-features' in cls(n).split():
   n['feature_list']=True
   for child in walk(n.get('children',[])):
    for value in child.values():
     if isinstance(value,str) and value in r['fields']:r['fields'][value]['legacy_feature']=True
save('pricing',m)
