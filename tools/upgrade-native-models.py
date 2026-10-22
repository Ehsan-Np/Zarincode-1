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
# 3.3: keep the avatar inside the original identity row; replace its icon, not the card structure.
m=load('testimonials');r=m['repeaters']['services_grid_items_1']
for template in r['variants'].values():
 template['children']=[n for n in template['children'] if not (isinstance(n,dict) and n.get('image')=='customer_photo')]
 for parent in walk([template]):
  for i,node in enumerate(parent.get('children',[])):
   if node.get('icon')=='icon_6_icon' or (node.get('image')=='customer_photo' and node.get('fallback_icon')=='icon_6_icon'):
    parent.setdefault('attrs',{})['class']='zc-testimonial-avatar'
    parent['attrs']['style']=re.sub(r'width:2.8rem;','width:var(--zc-avatar-size,2.8rem);',parent['attrs'].get('style',''))
    parent['attrs']['style']=re.sub(r'height:2.8rem;','height:var(--zc-avatar-size,2.8rem);',parent['attrs']['style'])
    image={'tag':'img','attrs':{'class':'zc-customer-photo','loading':'lazy','decoding':'async'},'part':node['part'],'image':'customer_photo','fallback_icon':'icon_6_icon','fallback_visible':'icon_6_visible','image_preset':'zarincode-avatar','alt':'div_text_1'}
    parent['children'][i]=image
save('testimonials',m)
m=load('brands');r=m['repeaters']['marquee_track_items_1'];vs=list(r['variants'])
r['fields']['brand_link']={'type':'url','default':{'url':''},'label':'صفحه اختصاصی فناوری','variants':vs}
r['fields']['brand_description']={'type':'textarea','default':'','label':'معرفی تخصص و تجربه','variants':vs}
r['fields']['brand_logo']={'type':'media','default':{'url':''},'label':'لوگوی برند یا فناوری','variants':vs}
r['fields']['span_text_1']['label']='نام برند یا فناوری'
for template in r['variants'].values():
 template['optional_link']='brand_link'
 for node in walk([template]):
  if node.get('icon')=='icon_1_icon':
   node['fallback_icon']='icon_1_icon';del node['icon'];node['image']='brand_logo';node['image_preset']='thumbnail';node['alt']='span_text_1';node['tag']='img';node['attrs']={'class':'zc-tech-logo','loading':'lazy','decoding':'async'}
 if not any(n.get('part')=='brand_description' for n in walk([template])):template['children'].append({'tag':'p','attrs':{'class':'zc-tech-desc'},'part':'brand_description','children':[{'text':'brand_description','part':'brand_description_text'}]})
m['parts']['brand_description']={'label':'توضیحات فناوری','type':'box'}
m['parts']['brand_description_text']={'label':'متن معرفی فناوری','type':'text'}
save('brands',m)
# 3.7: dynamic global account CTA and linked, non-cropped product artwork.
m=load('header')
for n in walk(m['tree']):
 if 'zc-header-actions' in cls(n).split() and not any(x.get('account_link') for x in n.get('children',[]) if isinstance(x,dict)):
  n.setdefault('children',[]).insert(0,{'tag':'span','attrs':{},'account_link':True})
save('header',m)
m=load('products')
for g,r in m['repeaters'].items():
 if 'grid_items' not in g:continue
 for template in r['variants'].values():
  for parent in walk([template]):
   if 'zc-product-media-link' in cls(parent).split():continue
   for i,node in enumerate(parent.get('children',[])):
    if isinstance(node,dict) and node.get('image')=='featured_image':
     node['image_preset']='woocommerce_single'
     parent['children'][i]={'tag':'a','attrs':{'class':'zc-product-media-link'},'part':g+'_featured_link','bindings':{'href':'product_thumb_1_href'},'image_guard':'featured_image','children':[node]}
 m['parts'][g+'_featured_link']={'label':'قاب تصویر محصول و پیوند','type':'box'}
save('products',m)
# 3.8: preserve unified title/scene/overlay composition on subsequent regeneration.
import runpy
runpy.run_path(str(pathlib.Path(__file__).with_name('refine-presentation-3.8.py')))
