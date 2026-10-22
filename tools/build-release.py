"""Build allowlisted evaluation artifacts. Commercial publication is gated, never implied."""
from pathlib import Path
import argparse,hashlib,json,subprocess,zipfile
root=Path(__file__).resolve().parent.parent
p=argparse.ArgumentParser();p.add_argument('--output',required=True);p.add_argument('--commercial',action='store_true');args=p.parse_args()
gates=json.loads((root/'docs/governance/release-gates.json').read_text())
if args.commercial and (not gates['commercial_approved'] or not all(v=='passed' for v in gates['gates'].values())):
 raise SystemExit('Commercial release BLOCKED: unresolved quality, compatibility or licensing gates.')
version=gates['version'];dest=Path(args.output).resolve();dest.mkdir(parents=True,exist_ok=True)
artifacts=[]
for package in ['zarincode','zarincode-core']:
 file=dest/f'{package}-{version}.zip'
 with zipfile.ZipFile(file,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
  for item in sorted((root/package).rglob('*')):
   if item.is_file():
    if item.suffix in ['.log','.zip'] or any(x in item.parts for x in ['node_modules','.git','uploads']):raise SystemExit('Forbidden package file: '+str(item))
    z.write(item,str(item.relative_to(root)))
 with zipfile.ZipFile(file) as z:
  assert z.testzip() is None
  names=z.namelist();assert names and all(n.startswith(package+'/') and '..' not in Path(n).parts for n in names)
  assert not any('EmbeddedCore/' in n for n in names)
  for item in sorted((root/package).rglob('*')):
   if item.is_file():assert z.read(str(item.relative_to(root)))==item.read_bytes()
 artifacts.append({'file':file.name,'bytes':file.stat().st_size,'members':len(names),'sha256':hashlib.sha256(file.read_bytes()).hexdigest(),'crc_and_source_parity':'passed'})
commit=subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip()
manifest={'product':'ZarinCode','version':version,'source_commit':commit,'commercial_approved':False,'artifacts':artifacts,'third_party_plugins_bundled':False,'asset_rights':'Existing user-provided assets retained; commercial redistribution rights not certified.'}
(dest/'BUILD-MANIFEST.json').write_text(json.dumps(manifest,indent=2)+'\n')
outer=dest/f'ZarinCode-{version}-project.zip';prefix=f'ZarinCode-{version}/'
with zipfile.ZipFile(outer,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for record in artifacts:z.write(dest/record['file'],prefix+'install/'+record['file'])
 z.write(dest/'BUILD-MANIFEST.json',prefix+'BUILD-MANIFEST.json')
 for directory in ['zarincode','zarincode-core','docs','tools']:
  for item in sorted((root/directory).rglob('*')):
   if item.is_file() and item.suffix not in ['.log','.zip','.pyc']:z.write(item,prefix+('source/' if directory.startswith('zarincode') else '')+str(item.relative_to(root)))
 for item in sorted((root/'.github').rglob('*')):
  if item.is_file():z.write(item,prefix+str(item.relative_to(root)))
 for name in ['README.md','AGENTS.md','LICENSE']:
  if (root/name).is_file():z.write(root/name,prefix+name)
with zipfile.ZipFile(outer) as z:assert z.testzip() is None
print(json.dumps({'build':manifest,'project_bundle':outer.name,'project_sha256':hashlib.sha256(outer.read_bytes()).hexdigest()},indent=2))
