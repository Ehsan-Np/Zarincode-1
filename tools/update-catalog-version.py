"""Update only GNU MO project-version metadata, preserving all translated entries."""
import argparse
import gettext
import io
import re
import struct
from pathlib import Path


def update(path, version):
    raw = path.read_bytes()
    before = gettext.GNUTranslations(io.BytesIO(raw))
    endian = '<' if raw[:4] == b'\xde\x12\x04\x95' else '>'
    magic, revision, count, originals, translations, _, _ = struct.unpack_from(endian+'7I', raw)
    if magic != 0x950412de or revision != 0:
        raise ValueError('Unsupported GNU MO format')
    entries = []
    for index in range(count):
        length, offset = struct.unpack_from(endian+'2I', raw, originals+index*8)
        key = raw[offset:offset+length]
        length, offset = struct.unpack_from(endian+'2I', raw, translations+index*8)
        value = raw[offset:offset+length]
        if not key:
            value, changed = re.subn(rb'(?m)^Project-Id-Version:[^\n]*', b'Project-Id-Version: ZarinCode '+version.encode('ascii'), value)
            if changed != 1:
                raise ValueError('Catalog lacks exactly one project version header')
        entries.append((key, value))
    entries.sort()
    original_data = b''.join(key+b'\0' for key, _ in entries)
    translated_data = b''.join(value+b'\0' for _, value in entries)
    data_start = 28+16*count
    first = second = b''
    a, b = data_start, data_start+len(original_data)
    for key, value in entries:
        first += struct.pack('<2I', len(key), a)
        second += struct.pack('<2I', len(value), b)
        a += len(key)+1
        b += len(value)+1
    result = struct.pack('<7I', magic, 0, count, 28, 28+8*count, 0, 0)+first+second+original_data+translated_data
    after = gettext.GNUTranslations(io.BytesIO(result))
    assert {k:v for k,v in before._catalog.items() if k != ''} == {k:v for k,v in after._catalog.items() if k != ''}
    assert after.info()['project-id-version'] == 'ZarinCode '+version
    path.write_bytes(result)
    print('Updated catalog header; translations preserved:', path)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--version', required=True)
    parser.add_argument('files', type=Path, nargs='+')
    args = parser.parse_args()
    if not re.fullmatch(r'\d+\.\d+\.\d+', args.version):
        parser.error('A final numeric version is required')
    for file in args.files:
        update(file, args.version)
