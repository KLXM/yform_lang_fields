# Changelog

## 1.3.0 - 2026-09-10
### Behoben
- **Kritisch – stiller Datenverlust:** `enterObject()` verarbeitete den POST-Wert bislang nach der Formularausgabe und griff dabei direkt (und redundant) auf `$_POST` zu. Bei einem Validierungsfehler eines *anderen* Feldes im selben Formular wurde das Sprachfeld beim Neu-Rendern auf leer zurückgesetzt – bereits eingegebene Übersetzungen gingen verloren. Die Normalisierung erfolgt jetzt in `preValidateAction()`, bevor Validatoren und die Formularausgabe laufen (#6).
- **Kritisch – stiller Datenverlust:** Beim Entfernen einer Übersetzung im Backend hat das JS beim Reindizieren der verbleibenden Felder am ersten `][` im `name`-Attribut geschnitten (statt am Zeilenindex). Dadurch landeten die Werte unter einem falschen POST-Key und wurden beim Speichern verworfen (#5).
- `validate|empty` hat auf Sprachfeldern invertiert reagiert (warnte bei leerem, nicht bei fehlendem Feld), weil Validatoren vor `enterObject()` liefen und noch das rohe POST-Array statt des JSON-Strings sahen. Behoben durch die `preValidateAction()`-Normalisierung aus #6 (#7).
- `LangQuery`: `whereTranslationLike()` nutzte pro Query-Instanz einen fest verdrahteten Bind-Platzhalter (`:lang_like`); ein zweiter Aufruf überschrieb den ersten und verwandelte `A AND B` faktisch in `B AND B` (#12).
- `LangQuery::orderByTranslation()` hängte die Sortierrichtung sowohl in den SQL-Ausdruck als auch (durch `orderByRaw()`) ein zweites Mal an und erzeugte damit immer einen SQL-Syntaxfehler (`… DESC ASC`). Die Methode war in beiden Richtungen unbenutzbar (#11).
- `LangQuery::whereTranslationNotEmpty()` verglich ein JSON-Array mit einem Skalar und lieferte dadurch garantiert keine Treffer (#10).
- `LangQuery`: Der Sprachfilter (`whereTranslationExists()`, `whereFullyTranslated()`, `whereIncompleteTranslations()`, `groupByTranslationStatus()`, `selectWithFallback()`) verglich den clang-Filter per `LIKE '%<id>%'` gegen den serialisierten JSON-String – ab clang-ID 10 lieferte das falsche Treffer (z. B. matchte clang 1 auch 11, 21). Alle Methoden nutzen jetzt `JSON_TABLE()`-basierte `EXISTS`-Prüfungen (#9).
- `getDescription()` der drei Feldtypen war seit 1.2.0 (Einführung von `use_writeassist`) nicht mehr mit der tatsächlichen Pipe-Parameter-Reihenfolge synchron und zeigte im Backend falsche Positionen an (#8).
- WriteAssist-Übersetzung: Die Zielsprache wurde bisher aus den ersten beiden Zeichen des REDAXO-Sprachcodes abgeleitet (z. B. `en_gb` → `EN`). Jetzt wird, sofern verfügbar, `AutoTranslateService::getDeeplCode()` von WriteAssist verwendet, was u. a. `en_gb` korrekt auf `EN-GB` und `pt_br` auf `PT-BR` abbildet (#13).
- Alle Bedienelemente (Buttons, Tooltips, Meldungen) waren unabhängig von der Backend-Sprache fest auf Deutsch verdrahtet, obwohl die Sprachdateien passende Schlüssel enthielten. Text kommt jetzt per `rex_i18n::msg()` (Template) bzw. `data-i18n-*`-Attributen (JavaScript) (#14).
- `LangHelper::getActiveLanguages()` lieferte trotz des Namens auch offline geschaltete Sprachen und ergab dadurch einen anderen Übersetzungsstatus als die Listenansicht. Neue Methode `LangHelper::getOnlineLanguages()` für Online-Sprachen; `LangDataset::getTranslationStatus()` nutzt sie jetzt für einen konsistenten Vollständigkeits-Begriff. `getActiveLanguages()` bleibt unverändert (Bestandsverhalten, keine BC-Änderung) (#15).
- Falsche Escaping-Strategie im JS-Kontext: `$mediaParams` landete als JS-String in einem HTML-Attribut, wurde aber mit der HTML- statt der JS-Escaping-Strategie behandelt.
- `LangHelper::resolveClangId()` enthielt zwei zerschossene Zeilenumbrüche mitten im Ausdruck (rein kosmetisch, kein Verhaltensfehler).
- `description`-Element fehlte in den Definitions von `lang_text`/`lang_textarea`, wodurch der zugehörige Hilfetext-Block im Template dort immer leer blieb (nur `lang_media` hatte es). Als neuer, optionaler Pipe-Parameter am Ende ergänzt (BC-sicher).
- Installationsbedingung widersprach dem README: `install.php` brach bei weniger als zwei Sprachen komplett ab, das README nennt „mindestens eine". Die Bedingung ist jetzt ein Hinweis statt eines Installationsabbruchs.
- Ungenutzte i18n-Schlüssel (Settings-/API-Message-Block) entfernt, die auf nie implementierte Features verwiesen.

### Hinzugefügt
- `LangQuery` ist jetzt vollständig im README dokumentiert (war zuvor unbenutzt und undokumentiert im Code vorhanden).
- `LangHelper::getOnlineLanguages()`.

## 1.2.1 - 2026-06-05
### Behoben
- **Backend-CSS-Leak in 1.2.0:** Fehlerhafte globale CSS-Regeln in `assets/lang-fields.css` konnten das REDAXO-Backend-Layout beeinflussen (u.a. kleinere Darstellung und begrenzte Breite). Die Styles sind jetzt wieder korrekt auf `.yform-lang-field`/`ylf-*` gescoped.

## 1.2.0 - 2024-06-04
### Hinzugefügt
- **WriteAssist KI-Integration:** YForm Lang Fields und das WriteAssist-AddOn sind nun ein Team! In den YForm-Feldeinstellungen lässt sich die KI-Übersetzung aktivieren. Anschließend erscheint in den Zielsprachen ein Button, mit dem der Text der Primärsprache via DeepL/OpenAI mit nur einem Klick automatisch übersetzt und eingefügt wird.
- **Rich-Text Support:** Die Übersetzung übernimmt HTML-Formatierungen fehlerfrei, egal ob das Ziel ein nacktes Textfeld, TinyMCE oder CKE5 (Editor.js) ist.
- **Collapsible Language Panels:** Sprach-Blöcke lassen sich per Klick auf deren Header zusammenklappen (Akkordeon-Modus).
- **Zusammenklappen/Ausklappen:** Über einen globalen "minimieren / maximieren" Button pro Feld lassen sich nun alle Sprach-Editoren blitzschnell auf einen 1-Zeiler reduzieren, um Platz im Backend zu schaffen.


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
