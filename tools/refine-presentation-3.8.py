"""Idempotent native model upgrade: keep existing field/part/repeater identities."""
import json,pathlib,re
ROOT=pathlib.Path(__file__).resolve().parents[1]/'zarincode-core/includes/Widgets/models'
def classes(n):
 a=n.get('attrs',{});return a.get('class','') if isinstance(a,dict) else ''
def walk(n):
 yield n
 for c in n.get('children',[]):
  if isinstance(c,dict):yield from walk(c)
def take(n,test):
 for i,c in enumerate(n.get('children',[])):
  if not isinstance(c,dict):continue
  if test(c):return n['children'].pop(i)
  found=take(c,test)
  if found:return found
 return None
for kind in ['products','services','portfolio','blog','page_section_services_page','page_section_projects_page']:
 p=ROOT/(kind+'.json');m=json.loads(p.read_text())
 for group,rep in m['repeaters'].items():
  if 'grid_items' not in group:continue
  for card in rep['variants'].values():
   if 'zc-story-card' in classes(card).split():continue
   image=take(card,lambda n:n.get('image')=='featured_image' or 'zc-product-media-link' in classes(n).split())
   title=take(card,lambda n:n.get('tag') in ['h2','h3','h4'])
   desc=take(card,lambda n:bool(n.get('rich')))
   footer=take(card,lambda n:bool(set(classes(n).split())&{'zc-service-footer','zc-product-price-row'}))
   if not title:raise RuntimeError('Incomplete editable card '+kind)
   link=''
   for n in walk(footer or {}):
    if n.get('bindings',{}).get('href'):link=n['bindings']['href'];break
   if not link:
    for n in walk(card):
     if n.get('bindings',{}).get('href'):link=n['bindings']['href'];break
   for n in walk(title):
    if isinstance(n.get('attrs'),dict):n['attrs'].pop('style',None)
   if desc:
    desc.setdefault('attrs',{}).pop('style',None)
    desc['attrs']['class']=(classes(desc)+' zc-story-description').strip()
   remainder=[n for n in card.get('children',[]) if isinstance(n,dict) and 'literal' not in n]
   for item in remainder:
    for n in walk(item):
     if 'zc-project-visual' in classes(n).split():
      n['attrs']['class']=classes(n).replace('zc-project-visual','zc-card-meta');n['attrs'].pop('style',None)
   attrs=card.setdefault('attrs',{});attrs['class']=classes(card)+' zc-story-card';attrs.pop('style',None)
   titleNode={'tag':'a' if link and not any(n.get('tag')=='a' for n in walk(title)) else 'div','attrs':{'class':'zc-story-title'},'part':group+'_story_title','children':[title]}
   if titleNode['tag']=='a':titleNode['bindings']={'href':link}
   if 'visible' in title:titleNode['visible']=title['visible']
   scene={'tag':'div','attrs':{'class':'zc-card-scene'},'part':group+'_story_scene','children':([image] if image else [])+[{'tag':'div','attrs':{'class':'zc-card-copy'},'part':group+'_story_copy','children':remainder+([desc] if desc else [])}]}
   card['children']=[titleNode,scene]+([footer] if footer else [])
  for suffix,label in [('story_title','عنوان بالای تصویر'),('story_scene','قاب یکپارچه تصویر'),('story_copy','پوشش و توضیحات روی تصویر')]:m['parts'][group+'_'+suffix]={'label':label,'type':'box'}
 p.write_text(json.dumps(m,ensure_ascii=False,indent=4)+'\n')
# Retire geometric scaffolding inside the old abstract visual; field/part IDs remain editable.
for kind in ['products','services','portfolio','blog','page_section_services_page','page_section_projects_page']:
 p=ROOT/(kind+'.json');m=json.loads(p.read_text())
 for rep in m['repeaters'].values():
  for card in rep.get('variants',{}).values():
   for node in walk(card):
    if 'zc-card-meta' not in classes(node).split():continue
    for child in walk(node):
     attrs=child.get('attrs',{})
     if isinstance(attrs,dict) and attrs.get('style'):
      attrs['style']=';'.join(x for x in attrs['style'].split(';') if x.split(':')[0].strip().lower() not in {'min-height','height','padding','padding-top','padding-bottom','margin','margin-top','margin-bottom','position','font-size','width','background','background-color','border-radius','color'})
      if not attrs['style']:attrs.pop('style')
 p.write_text(json.dumps(m,ensure_ascii=False,indent=4)+'\n')
