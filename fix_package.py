import re

with open('package.yml', 'r') as f:
    c = f.read()

# Replace specifically the php version
c = re.sub(r"php:\n\s*version:\s*'1\.1\.0'", "php:\n        version: '^8.1'", c)

with open('package.yml', 'w') as f:
    f.write(c)
print("package.yml fixed")

