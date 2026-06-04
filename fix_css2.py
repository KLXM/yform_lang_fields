import re
with open('assets/lang-fields.css', 'r') as f:
    text = f.read()

bad = """body.ylf-show-incomplete .ylf-is-incomplete::before {
    content: '';
    position: absolute;
    left: -12px;
    top: 5px;"""

new = """body.ylf-show-incomplete .ylf-is-incomplete::before {
    content: '';
    position: absolute;
    left: -14px;
    top: calc(50% - 3px);"""

text = text.replace(bad, new)

with open('assets/lang-fields.css', 'w') as f:
    f.write(text)
