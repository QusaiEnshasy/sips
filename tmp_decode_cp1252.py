import json
from pathlib import Path

cp1252_map = {
    '€': 0x80, '‚': 0x82, 'ƒ': 0x83, '„': 0x84, '…': 0x85, '†': 0x86, '‡': 0x87,
    'ˆ': 0x88, '‰': 0x89, 'Š': 0x8A, '‹': 0x8B, 'Œ': 0x8C, 'Ž': 0x8E,
    '‘': 0x91, '’': 0x92, '“': 0x93, '”': 0x94, '•': 0x95, '–': 0x96,
    '—': 0x97, '˜': 0x98, '™': 0x99, 'š': 0x9A, '›': 0x9B, 'œ': 0x9C,
    'ž': 0x9E, 'Ÿ': 0x9F
}

p = Path('src/locales/ar/student.json')
obj = json.loads(p.read_text('utf-8'))

def cp1252_bytes(text):
    out = bytearray()
    for ch in text:
        code = ord(ch)
        if code <= 0xFF:
            out.append(code)
        elif ch in cp1252_map:
            out.append(cp1252_map[ch])
        else:
            raise ValueError(f"Cannot map char: {repr(ch)}")
    return bytes(out)

for key in ['training_progress', 'explore_workspace', 'track_progress', 'explore_skills', 'filters']:
    v = obj[key]
    b = cp1252_bytes(v)
    print('KEY:', key)
    print('BYTES:', b)
    print('DECODED:', b.decode('utf-8', errors='replace'))
    print('---')
