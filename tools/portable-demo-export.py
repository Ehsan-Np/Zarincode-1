from lxml import etree as E
from pathlib import Path
import json,sys
# Usage: python tools/portable-demo-export.py /path/to/wp-export.xml
source=sys.argv[1] if len(sys.argv)>1 else "zarincode-demo.xml"
project=Path(__file__).resolve().parents[1]
p=E.XMLParser(strip_cdata=False);root=E.parse(source,p)
ns={'wp':'http://wordpress.org/export/1.2/','dc':'http://purl.org/dc/elements/1.1/'}
base='https://raw.githubusercontent.com/Ehsan-Np/Zarincode-1/main/zarincode/assets/images/'
def portable(v):
 if isinstance(v,dict):return {k:portable(x) for k,x in v.items()}
 if isinstance(v,list):return [portable(x) for x in v]
 if isinstance(v,str):
  return v.replace('http://localhost:8080/wp-content/themes/zarincode/assets/images/',base).replace('http://localhost:8080','https://zarincode.com')
 return v
# Export includes every site taxonomy, even with --post__in. Keep only used terms
# and their ancestors so disposable QA taxonomies never enter the release.
channel=root.getroot().find('channel')
used={(c.get('domain'),c.get('nicename')) for item in root.findall('.//item') for c in item.findall('category')}
terms=[]
for node in list(channel):
 local=E.QName(node).localname
 if E.QName(node).namespace!=ns['wp'] or local not in ['category','tag','term']:continue
 tax='category' if local=='category' else 'post_tag' if local=='tag' else node.findtext('wp:term_taxonomy',namespaces=ns)
 slug=node.findtext('wp:'+{'category':'category_nicename','tag':'tag_slug','term':'term_slug'}[local],namespaces=ns)
 parent=node.findtext('wp:'+('category_parent' if local=='category' else 'term_parent'),namespaces=ns) if local!='tag' else ''
 terms.append((node,tax,slug,parent))
changed=True
while changed:
 changed=False
 for node,tax,slug,parent in terms:
  if (tax,slug) in used and parent and (tax,parent) not in used:used.add((tax,parent));changed=True
for node,tax,slug,parent in terms:
 if (tax,slug) not in used:channel.remove(node)
for item in root.findall('.//item'):
 for meta in item.findall('wp:postmeta',ns):
  key=meta.findtext('wp:meta_key',namespaces=ns);value=meta.find('wp:meta_value',ns)
  if (key or '').startswith(('_zc_layout_backup_','_zc_hero_backup_')) or key in ['_elementor_css','_elementor_element_cache','_elementor_page_assets','_edit_lock','_edit_last','_wc_average_rating','_wc_review_count','_wc_rating_count','total_sales']:
   item.remove(meta);continue
  if value is not None and value.text:
   if key=='_elementor_data':value.text=E.CDATA(json.dumps(portable(json.loads(value.text)),ensure_ascii=False,separators=(',',':')))
   else:value.text=E.CDATA(portable(value.text))
 url=item.find('wp:attachment_url',ns)
 if url is not None:
  for asset in ['product','service','project','post']:
   if 'zarincode-demo-'+asset in (url.text or ''):url.text=E.CDATA(base+'demo-'+asset+'-cover.jpg')
 creator=item.find('dc:creator',ns)
 if creator is not None:creator.text=E.CDATA('zarincode')
for tag in ['wp:author_login','wp:author_email','wp:author_display_name','wp:author_first_name','wp:author_last_name']:
 for node in root.findall('.//'+tag,ns):node.text=E.CDATA({'wp:author_login':'zarincode','wp:author_email':'info@zarincode.com','wp:author_display_name':'Ehsan NaderiPanah','wp:author_first_name':'Ehsan','wp:author_last_name':'NaderiPanah'}[tag])
for node in root.iter():
 if node.text and 'localhost:8080' in node.text:node.text=portable(node.text)
raw=E.tostring(root,encoding='UTF-8',xml_declaration=True)
assert b'localhost' not in raw and b'zcadmin' not in raw and b'QA ' not in raw
(project/'zarincode/demo-data/zarincode-demo-content.xml').write_bytes(raw)
(project/'packages/zarincode-demo-content.xml').write_bytes(raw)
print('Portable WXR:',len(root.findall('.//item')),'items; no local host, QA records or test account')
