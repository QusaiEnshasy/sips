from pathlib import Path
import json

p = Path('src/locales/ar/student.json')
obj = json.loads(p.read_text('utf-8'))

for key in ['training_progress', 'explore_workspace', 'track_progress', 'explore_skills', 'filters']:
    v = obj[key]
    print('KEY:', key)
    print('RAW:', repr(v))
    try:
        step1 = v.encode('cp1252').decode('utf-8')
        print('STEP1:', repr(step1))
    except Exception as e:
        print('STEP1 ERROR', e)
    try:
        step2 = v.encode('cp1252').decode('utf-8').encode('cp1252').decode('utf-8')
        print('STEP2:', repr(step2))
    except Exception as e:
        print('STEP2 ERROR', e)
    print('---')
