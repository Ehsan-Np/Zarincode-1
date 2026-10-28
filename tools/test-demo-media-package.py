"""Static delivery contracts; integration lives in qa-demo-media.php."""
import json
import re
import unittest
import xml.etree.ElementTree as ET
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
WP = {'wp': 'http://wordpress.org/export/1.2/', 'content': 'http://purl.org/rss/1.0/modules/content/'}

class DemoMediaPackage(unittest.TestCase):
    def test_wxr_has_no_external_media_fetches_or_stale_thumbnail_ids(self):
        path = ROOT/'zarincode/demo-data/zarincode-demo-content.xml'
        tree = ET.parse(path)
        self.assertNotIn('raw.githubusercontent.com', path.read_text())
        for item in tree.findall('./channel/item'):
            self.assertNotEqual(item.findtext('wp:post_type', namespaces=WP), 'attachment')
        for meta in tree.findall('.//wp:postmeta', WP):
            self.assertNotEqual(meta.findtext('wp:meta_key', namespaces=WP), '_thumbnail_id')

    def test_magazine_source_parity_and_quotas(self):
        articles = json.loads((ROOT/'zarincode/demo-data/zarincode-articles.json').read_text())['articles']
        items = ET.parse(ROOT/'zarincode/demo-data/zarincode-articles.xml').findall('./channel/item')
        self.assertEqual(len(items), 71)
        self.assertEqual(len(articles), 71)
        self.assertEqual(Counter(a['category'] for a in articles), {'web-design':10,'seo':10,'content-production':5,'wordpress-tutorials':12,'website-security':22,'site-performance':12})
        for article, item in zip(articles, items):
            self.assertEqual(article['slug'], item.findtext('wp:post_name', namespaces=WP))
            self.assertEqual(article['content'], item.findtext('content:encoded', namespaces=WP))

    def test_referenced_bundled_rasters_exist(self):
        sources = [ROOT/'zarincode-core/includes/Demo/Demo_Seeder.php', ROOT/'zarincode-core/includes/Demo/service-demo.json', ROOT/'zarincode/demo-data/zarincode-demo-content.xml']
        sources += list((ROOT/'zarincode-core/includes/Widgets/models').glob('*.json'))
        names = set()
        for source in sources:
            names.update(re.findall(r'/assets/images/([A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp|gif))', source.read_text()))
        self.assertTrue(names)
        for name in names:
            self.assertTrue((ROOT/'zarincode/assets/images'/name).is_file(), name)

    def test_original_importer_design_remains(self):
        source = (ROOT/'zarincode-core/includes/Demo/Demo_Seeder.php').read_text()
        for marker in ['zc-demo-main-grid', 'grid-template-columns: 1.55fr 1fr', 'zc-demo-fullwidth-wrap', 'border:2px solid #FDD05B', 'value="articles"', 'value="images"']:
            self.assertIn(marker, source)

if __name__ == '__main__':
    unittest.main()
