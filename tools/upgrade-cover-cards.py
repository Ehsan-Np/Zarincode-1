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
