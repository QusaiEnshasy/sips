from pathlib import Path
import json
import unicodedata
s = 'Ã™â€žÃ™Ë†Ã˜Â\xadÃ˜Â© Ã˜Â§Ã™â€žÃ˜Â·Ã˜Â§Ã™â€žÃ˜Â¨'
print(s)
for i, ch in enumerate(s):
    print(i, repr(ch), hex(ord(ch)), unicodedata.name(ch, 'UNKNOWN'))
