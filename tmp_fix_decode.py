from pathlib import Path
import json

p = Path('src/locales/ar/student.json')
obj = json.loads(p.read_text('utf-8'))

def fix(v):
    try:
        return v.encode('cp1252').decode('utf-8').encode('cp1252').decode('utf-8')
    except Exception as e:
        return f'ERROR:{e}'

for k in list(obj.keys())[:40]:
    print(k, '=>', fix(obj[k]))
