"""Inventory all declared controls. References are not proof of correct behavior.
PHP_BINARY=php python3 tools/audit-settings.py > docs/audits/settings-inventory.md
"""
import json
import os
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parent.parent
schema = json.loads(subprocess.check_output(
    [os.environ.get('PHP_BINARY', 'php'), str(ROOT / 'tools/qa-site-contract.php'), '--inventory'],
    cwd=ROOT, text=True))
sources = {str(p.relative_to(ROOT)): p.read_text() for tree in ('zarincode', 'zarincode-core')
           for p in (ROOT / tree).rglob('*.php') if p.name != 'OptionsConfig.php'}
options = (ROOT / 'zarincode/inc/Redux/OptionsConfig.php').read_text()
sources['zarincode/inc/Redux/OptionsConfig.php (runtime)'] = options[options.index('    public function ensure_default_options'):]
fields = [(section, f) for section, spec in schema.items() for f in spec['fields']]
assert len({f['id'] for _, f in fields}) == len(fields), 'Duplicate control IDs'
print('# فهرست کنترل‌های پنل — ممیزی ایستای قابل بازتولید\n')
print(f'{len(fields)} کنترل در {len(schema)} بخش. این جدول صرفاً یافتن مصرف‌کننده در سورس است، نه تأیید کارکرد همه گزینه‌ها در وردپرس.\n')
print('گزینه‌های فاقد مرجع، نیازمند اتصال/بررسی جداگانه‌اند. مسیرهای پویا دستی مشخص شده‌اند؛ ممکن است ارجاع متنی در شرط یا سازنده کلید باشد، نه خروجی فعال.\n')
print('| بخش | شناسه | نوع | شواهد مصرف‌کننده |\n|---|---|---|---|')
missing = []
for section, field in fields:
    key = field['id']
    refs = [name for name, text in sources.items() if key in text]
    if not refs and key.startswith('social_'):
        refs = ['پویا: Core/Social_Links.php با social_ + نام شبکه']
    if not refs and key in ('header_source', 'footer_source'):
        refs = ['پویا: Bootstrap/Theme.php با type + _source']
    if not refs and key.startswith('feature_'):
        refs = ['پویا: Features/Manager.php و Registry.php؛ وابسته به قابلیت/افزونه']
    if not refs:
        missing.append(key)
    evidence = '<br>'.join('`' + ref + '`' for ref in refs) if refs else '**مصرف‌کننده مستقیم یافت نشد — تأیید نشده**'
    print(f"| {section} | `{key}` | {field['type']} | {evidence} |")
print('\n## کنترل‌های نیازمند بررسی/اتصال بیشتر\n')
print('\n'.join('- `' + key + '`' for key in missing))
print('\nاین موارد با این ممیزی کامل‌شده اعلام نمی‌شوند. مخصوصاً گزینه‌های هیرو/مقالات/تعرفه/فرم و صفحات حقوقی باید با کنترل مستقل ویجت‌ها و داده‌های ذخیره‌شده هماهنگ و در staging آزموده شوند.')
