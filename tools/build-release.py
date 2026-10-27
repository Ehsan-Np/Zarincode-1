"""Build verified-source evaluation artifacts; commercial publication remains explicitly gated."""
from pathlib import Path
import argparse
import hashlib
import json
import re
import subprocess
import tempfile
import zipfile

ROOT = Path(__file__).resolve().parent.parent
EXCLUDED = {'.git', 'vendor', 'node_modules', '__pycache__', '.phpstan-cache', '.phpunit.cache', 'screenshots'}
SECRET_SUFFIXES = {'.pem', '.key', '.p12', '.pfx', '.sql', '.sqlite', '.bak'}


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT, text=True).strip()


def gate_satisfied(name, value, gates):
    if value == 'passed':
        return True
    return (name == 'WPML' and value == 'out_of_scope_by_explicit_user_instruction'
            and any(e.get('id') == 'WPML-OUT-OF-SCOPE' and e.get('approved_by') == 'user'
                    for e in gates.get('approved_exceptions', [])))


def checked_files(directory, runtime=False):
    for item in sorted((ROOT / directory).rglob('*')):
        relative = item.relative_to(ROOT)
        if not runtime and any(part in EXCLUDED for part in relative.parts):
            continue
        if item.is_symlink():
            raise ValueError('Symlinks are forbidden in delivery trees: ' + str(item.relative_to(ROOT)))
        if not item.is_file():
            continue
        if item.name.startswith('.env') or item.suffix.lower() in SECRET_SUFFIXES:
            raise ValueError('Potential secret/database/backup file: ' + str(relative))
        generated = (any(part in EXCLUDED for part in relative.parts)
                     or item.name == '.phpunit.result.cache' or item.suffix.lower() in {'.log', '.zip', '.pyc'})
        if generated:
            if runtime:
                raise ValueError('Forbidden runtime package file: ' + str(relative))
            continue
        if runtime and re.search(rb'-----BEGIN (?:RSA |EC |DSA |OPENSSH )?PRIVATE KEY-----', item.read_bytes()):
            raise ValueError('Private key material in runtime: ' + str(relative))
        yield item


def make_archive(path, members):
    with zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for item, name in members:
            if name.startswith('/') or '..' in Path(name).parts:
                raise ValueError('Unsafe archive member: ' + name)
            archive.write(item, name)
    with zipfile.ZipFile(path) as archive:
        if archive.testzip() is not None:
            raise ValueError('CRC verification failed')
        if len(archive.namelist()) != len(set(archive.namelist())):
            raise ValueError('Duplicate archive members')
        for item, name in members:
            if archive.read(name) != item.read_bytes():
                raise ValueError('Source parity failed: ' + name)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--output', required=True)
    parser.add_argument('--commercial', action='store_true')
    args = parser.parse_args()
    gates = json.loads((ROOT / 'docs/governance/release-gates.json').read_text())
    if args.commercial and (not gates['commercial_approved'] or not all(
            gate_satisfied(k, v, gates) for k, v in gates['gates'].items())):
        raise ValueError('Commercial release BLOCKED: unresolved quality, compatibility or approval gates.')
    version = gates['version']
    if not isinstance(version, str) or not re.fullmatch(r'\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?', version):
        raise ValueError('Invalid release version')
    if git('status', '--porcelain', '--untracked-files=all'):
        raise ValueError('Build requires committed, clean source; no uncommitted final-source claim is allowed.')
    commit = git('rev-parse', 'HEAD')
    destination = Path(args.output).resolve()
    trees = ['zarincode', 'zarincode-core', 'docs', 'tools', '.github']
    if any(destination.is_relative_to(ROOT / tree) for tree in trees):
        raise ValueError('Output must not be inside an included source tree')
    # Validate every included tree before creating or replacing any delivery artifact.
    files = {tree: list(checked_files(tree, tree in ['zarincode', 'zarincode-core'])) for tree in trees}
    for package in ['zarincode', 'zarincode-core']:
        if not files[package]:
            raise ValueError('Empty runtime package: ' + package)
        if any('EmbeddedCore' in item.parts for item in files[package]):
            raise ValueError('Duplicated embedded runtime is forbidden')
    with tempfile.TemporaryDirectory(prefix='zc-build-') as temp:
        stage = Path(temp)
        artifacts = []
        for package in ['zarincode', 'zarincode-core']:
            file = stage / f'{package}-{version}.zip'
            make_archive(file, [(p, str(p.relative_to(ROOT))) for p in files[package]])
            artifacts.append({'file': file.name, 'bytes': file.stat().st_size,
                              'members': len(files[package]), 'sha256': hashlib.sha256(file.read_bytes()).hexdigest(),
                              'crc_and_source_parity': 'passed'})
        manifest = {'product': 'ZarinCode', 'version': version, 'source_commit': commit,
                    'source_tree_clean': True, 'commercial_approved': bool(args.commercial),
                    'artifacts': artifacts, 'third_party_plugins_bundled': False,
                    'asset_rights': 'Existing user-provided assets retained; commercial redistribution rights not certified.'}
        manifest_file = stage / 'BUILD-MANIFEST.json'
        manifest_file.write_text(json.dumps(manifest, indent=2) + '\n')
        prefix = f'ZarinCode-{version}/'
        members = [(stage / a['file'], prefix + 'install/' + a['file']) for a in artifacts]
        members.append((manifest_file, prefix + 'BUILD-MANIFEST.json'))
        for directory in trees:
            for item in files[directory]:
                members.append((item, prefix + ('source/' if directory.startswith('zarincode') else '') + str(item.relative_to(ROOT))))
        for name in ['README.md', 'AGENTS.md', 'LICENSE']:
            if (ROOT / name).is_file():
                members.append((ROOT / name, prefix + name))
        outer = stage / f'ZarinCode-{version}-project.zip'
        make_archive(outer, members)
        if git('rev-parse', 'HEAD') != commit or git('status', '--porcelain', '--untracked-files=all'):
            raise ValueError('Source changed during build; delivery not published')
        report = {'build': manifest, 'project_bundle': outer.name,
                  'project_sha256': hashlib.sha256(outer.read_bytes()).hexdigest()}
        destination.mkdir(parents=True, exist_ok=True)
        for file in stage.iterdir():
            (destination / file.name).write_bytes(file.read_bytes())
        print(json.dumps(report, indent=2))


if __name__ == '__main__':
    try:
        main()
    except (ValueError, subprocess.CalledProcessError) as error:
        raise SystemExit(str(error)) from error
