"""Regression tests for clean-source, secret and archive delivery guards."""
from pathlib import Path
import json
import shutil
import subprocess
import tempfile
import unittest
import zipfile

BUILDER = Path(__file__).with_name('build-release.py')

class ReleaseBuilderTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='zc-build-test-')
        self.base = Path(self.temp.name)
        self.repo = self.base / 'repo'
        self.repo.mkdir()
        self.output = self.base / 'delivery'
        for name in ['tools', 'docs/governance', 'zarincode', 'zarincode-core', '.github']:
            (self.repo / name).mkdir(parents=True)
        shutil.copyfile(BUILDER, self.repo / 'tools/build-release.py')
        self.gates = {'version':'1.0.0', 'commercial_approved':False, 'gates':{'security':'not_verified'}}
        self.write_gates()
        (self.repo / 'zarincode/style.css').write_text('/* Theme */')
        (self.repo / 'zarincode-core/plugin.php').write_text('<?php // Companion')
        self.git('init', '-q')
        self.commit()

    def tearDown(self):
        self.temp.cleanup()

    def git(self,*args):
        return subprocess.run(['git',*args],cwd=self.repo,check=True,capture_output=True,text=True)

    def commit(self):
        self.git('add','-A')
        self.git('-c','user.name=Quality','-c','user.email=qa@example.test','commit','-qm','Fixture')

    def write_gates(self):
        (self.repo/'docs/governance/release-gates.json').write_text(json.dumps(self.gates))

    def run_builder(self,*extra):
        return subprocess.run(['python3',str(self.repo/'tools/build-release.py'),'--output',str(self.output),*extra],capture_output=True,text=True)

    def test_clean_source_and_crc_parity(self):
        result=self.run_builder();self.assertEqual(result.returncode,0,result.stderr)
        manifest=json.loads((self.output/'BUILD-MANIFEST.json').read_text())
        self.assertTrue(manifest['source_tree_clean']);self.assertFalse(manifest['commercial_approved'])
        self.assertEqual(manifest['source_commit'],self.git('rev-parse','HEAD').stdout.strip())
        for file in self.output.glob('*.zip'):
            with zipfile.ZipFile(file) as archive:self.assertIsNone(archive.testzip())

    def test_dirty_tracked_and_untracked_source_rejected(self):
        (self.repo/'zarincode/style.css').write_text('Changed')
        self.assertNotEqual(self.run_builder().returncode,0)
        self.git('checkout','--','zarincode/style.css')
        (self.repo/'tools/untracked.py').write_text('pass')
        self.assertNotEqual(self.run_builder().returncode,0);self.assertFalse(self.output.exists())

    def test_secrets_and_symlinks_rejected_before_publication(self):
        for name in ['zarincode/private.key','docs/.env.production','zarincode-core/backup.sql']:
            with self.subTest(name=name):
                path=self.repo/name;path.write_text('Never ship');self.commit()
                self.assertNotEqual(self.run_builder().returncode,0);self.assertFalse(self.output.exists())
                path.unlink();self.commit()
        (self.repo/'zarincode/linked.css').symlink_to('/etc/hostname');self.commit()
        self.assertNotEqual(self.run_builder().returncode,0);self.assertFalse(self.output.exists())

    def test_generated_development_dependencies_are_not_bundled(self):
        path=self.repo/'tools/node_modules/pkg';path.mkdir(parents=True)
        (path/'private.key').write_text('ignored dev fixture')
        (path/'linked').symlink_to('/etc/hostname');self.commit()
        result=self.run_builder();self.assertEqual(result.returncode,0,result.stderr)
        with zipfile.ZipFile(self.output/'ZarinCode-1.0.0-project.zip') as archive:
            self.assertFalse(any('node_modules' in n for n in archive.namelist()))

    def test_wpml_exception_never_waives_other_gates(self):
        self.gates.update(commercial_approved=True,approved_exceptions=[{'id':'WPML-OUT-OF-SCOPE','approved_by':'user'}])
        self.gates['gates']={'WPML':'out_of_scope_by_explicit_user_instruction','security':'not_verified'}
        self.write_gates();self.commit();self.assertNotEqual(self.run_builder('--commercial').returncode,0)
        self.gates['gates']['security']='passed';self.write_gates();self.commit()
        result=self.run_builder('--commercial');self.assertEqual(result.returncode,0,result.stderr)
        self.assertTrue(json.loads((self.output/'BUILD-MANIFEST.json').read_text())['commercial_approved'])

    def test_unsafe_version_and_source_output_rejected(self):
        self.gates['version']='../../escape';self.write_gates();self.commit()
        self.assertNotEqual(self.run_builder().returncode,0)
        self.gates['version']='1.0.0';self.write_gates();self.commit()
        result=subprocess.run(['python3',str(self.repo/'tools/build-release.py'),'--output',str(self.repo/'zarincode')],capture_output=True)
        self.assertNotEqual(result.returncode,0)

if __name__=='__main__':unittest.main()
