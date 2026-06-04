# Changelog

## 1.1.1 - 2024-xx-xx (Aktuelles Release)
### Hinzugefügt
- **Übersetzungs-Check:** Im Sprach-Dropdown gibt es nun die Option "Fehlende hervorheben". Ist diese aktiv, werden Einträge, die nicht für alle im System *auf online geschalteten* Sprachen übersetzt sind, in der Listenansicht mit einem dezenten roten Indikator-Punkt versehen.

## 1.1.0 - 2024-xx-xx (Aktuelles Release)
### Hinzugefügt
- **Sprachumschalter in der YForm-Listenansicht:** Über ein neues Dropdown in der Toolbar kann die angezeigte Sprache für alle Sprachfelder in der Liste live umgeschaltet werden.
- **Dezentes Sprach-Label:** Sprachkürzel (z.B. IT, DE) in der Listenansicht werden nun in Großbuchstaben und einem dezenten, Dark-Mode-kompatiblen Badge angezeigt.

### Geändert
- **Obsolete Einstellung entfernt:** Das Feld `list_lang` (Anzeigesprache in Liste) wurde aus den Value-Settings von `lang_text`, `lang_textarea` und `lang_media` entfernt, da die Sprache nun global über den neuen Umschalter in der Toolbar gesteuert wird.

### Behoben
- **Code-Qualität:** Rexstan (Level 9) Analyse durchgeführt und gemeldete Typfehler bereinigt.

## 1.0.4 - Unreleased
### Geändert
- lang_textarea: Legacy-Flag editor entfernt. Die Editor-Auswahl erfolgt jetzt ausschließlich über attributes.
- Dynamisch hinzugefügte Sprachfelder initialisieren nur noch den per attributes konfigurierten Editor.

### Migrationshinweis
- Alt: 'editor' => true
- Neu: attributes mit class, z. B. cke5-editor oder tiny-editor.
- Optional können profile und lang als Alias verwendet werden; sie werden intern auf data-profile und data-lang gemappt.
