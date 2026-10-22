"""Idempotent native card structural upgrade; preserve every Elementor field and repeater ID."""
import json,re
from pathlib import Path
root=Path(__file__).resolve().parents[1]/'zarincode-core/includes/Widgets/models'
def walk(n):
 if isinstance(n,dict):
  yield n
  for c in n.get('children',[]):yield from walk(c)
def cls(n):return (n.get('attrs') or {}).get('class','')
def add(n,c):
 n.setdefault('attrs',{})['class']=' '.join(dict.fromkeys((cls(n)+' '+c).split()))
for kind in ['portfolio','blog','page_section_projects_page']:
 p=root/(kind+'.json');m=json.loads(p.read_text())
 for g,r in m['repeaters'].items():
  if 'grid_items' not in g:continue
  for card in r['variants'].values():
   visual=next(n for n in walk(card) if 'zc-project-visual' in cls(n).split())
   image=next(n for n in walk(card) if n.get('image')=='featured_image')
   for n in list(walk(card)):
    if image in n.get('children',[]):n['children'].remove(image)
   visual['children'].insert(0,image)
   visual['attrs']['style']=re.sub(r'(?:^|;)min-height\s*:[^;]*;?', ';', visual['attrs'].get('style',''))
   add(card,'zc-cover-card');add(visual,'zc-card-cover');add(image,'zc-card-cover-image')
   meta=next(n for n in visual['children'] if any('zc-prod-badge' in cls(c).split() for c in n.get('children',[]) if isinstance(c,dict)))
   add(meta,'zc-card-cover-meta')
   # The decorative icon tile is not part of the approved overlay. Keep its editable controls, but hide the tile.
   for n in walk(visual):
    if n.get('tag') in ['h2','h3','h4']:add(n,'zc-card-cover-title')
    if n.get('part','').endswith('_div_4'):add(n,'zc-card-cover-icon')
 p.write_text(json.dumps(m,ensure_ascii=False,indent=4)+'\n')
# Extend the same approved presentation to shop/service cards without changing row identities.
for kind in ['products','services','page_section_services_page']:
 p=root/(kind+'.json');m=json.loads(p.read_text())
 for g,r in m['repeaters'].items():
  if not any('zc-product-card' in cls(n).split() or 'zc-service-card' in cls(n).split() for n in r['variants'].values()):continue
  for card in r['variants'].values():
   if 'zc-cover-card' in cls(card).split():continue
   add(card,'zc-cover-card')
   image=next(n for n in walk(card) if n.get('image')=='featured_image')
   heading=next(n for n in walk(card) if n.get('tag')=='h3')
   if kind=='products':
    media=next(n for n in card['children'] if 'zc-product-media-link' in cls(n).split())
    card['children'].remove(media)
    cover=next(n for n in walk(card) if 'zc-product-thumb' in cls(n).split())
    meta=next(n for n in walk(cover) if 'zc-product-badges' in cls(n).split())
    for parent in list(walk(card)):
     if heading in parent.get('children',[]):parent['children'].remove(heading)
    cover['tag']='div';cover.pop('bindings',None);cover.pop('fallback_image_for',None)
    legacy=[n for n in cover['children'] if n is not meta]
    for n in legacy:add(n,'zc-card-cover-icon')
    cover['attrs']['style']='';cover['children']=[media,meta,*legacy,heading]
   else:
    card['children'].remove(image)
    meta=next(n for n in walk(card) if 'zc-service-top' in cls(n).split())
    for parent in list(walk(card)):
     for n in [heading,meta]:
      if n in parent.get('children',[]):parent['children'].remove(n)
    part=g+'_cover';cover={'tag':'div','attrs':{'class':'zc-card-cover'},'part':part,'children':[image,meta,heading]}
    card['children'].insert(0,cover);m['parts'][part]={'label':'بخش تصویری خدمت','type':'div'}
   add(cover,'zc-card-cover');add(image,'zc-card-cover-image');add(meta,'zc-card-cover-meta');add(heading,'zc-card-cover-title')
   # Default legacy margins/colors must not push a title out of its image. Elementor style overrides remain available.
   heading['attrs']['style']=re.sub(r'(?:^|;)(?:margin(?:-top)?|color)\s*:[^;]*;?', ';', heading.get('attrs',{}).get('style',''))
 p.write_text(json.dumps(m,ensure_ascii=False,indent=4)+'\n')
