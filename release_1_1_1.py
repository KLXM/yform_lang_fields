import re

# package.yml
with open('package.yml', 'r') as f:
    c = f.read()
c = re.sub(r"version:\s*'1\.1\.0'", "version: '1.1.1'", c)
with open('package.yml', 'w') as f:
    f.write(c)

# CHANGELOG.md
with open('CHANGELOG.md', 'r') as f:
    chl = f.read()

new_log = """# Changelog

## 1.1.1 - 2024-xx-xx (Aktuelles Release)
### Hinzugefügt
- **Übersetzungs-Check:** Im Sprach-Dropdown gibt es nun die Option "Fehlende hervorheben". Ist diese aktiv, werden Einträge, die nicht für alle im System *auf online geschalteten* Sprachen übersetzt sind, in der Listenansicht mit einem dezenten roten Indikator-Punkt versehen.

## 1.1.0"""

chl = chl.replace("# Changelog\n\n## 1.1.0", new_log)
with open('CHANGELOG.md', 'w') as f:
    f.write(chl)

print("package and changelog updated")
