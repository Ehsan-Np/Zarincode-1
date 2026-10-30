"""Current stable release consistency; historical RC archives intentionally stay intact."""
from pathlib import Path
import gettext
import json
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]

class FinalVersion(unittest.TestCase):
    def test_release_headers_and_governance_agree(self):
        self.assertRegex((ROOT/'zarincode/style.css').read_text(), r'(?m)^Version: 4\.0\.3$')
        self.assertRegex((ROOT/'zarincode-core/zarincode-core.php').read_text(), r'Version:\s+4\.0\.3\s*\n')
        governance = json.loads((ROOT/'docs/governance/release-gates.json').read_text())
        self.assertEqual(governance['version'], '4.0.3')
        self.assertEqual(governance['release_channel'], 'stable')
        self.assertFalse(governance['commercial_approved'])

    def test_active_runtime_has_no_rc_version(self):
        for tree in ['zarincode', 'zarincode-core']:
            for file in (ROOT/tree).rglob('*'):
                if file.is_file() and file.suffix in {'.php','.css','.js','.json','.po','.pot'}:
                    self.assertIsNone(re.search(r'4\.0\.0-rc\.\d+', file.read_text()), str(file))

    def test_compiled_translations_have_final_metadata(self):
        for tree in ['zarincode', 'zarincode-core']:
            with (ROOT/tree/'languages/fa_IR.mo').open('rb') as stream:
                self.assertEqual(gettext.GNUTranslations(stream).info()['project-id-version'], 'ZarinCode 4.0.3')

    def test_installer_points_to_stable_archive(self):
        self.assertIn('https://github.com/Ehsan-Np/Zarincode-1/raw/refs/tags/v4.0.3/packages/releases/4.0.3/zarincode-core-4.0.3.zip', (ROOT/'zarincode/inc/Plugins/Plugin_Manager.php').read_text())
        self.assertTrue((ROOT/'docs/releases/4.0.3-FA.md').is_file())

if __name__ == '__main__':
    unittest.main()
