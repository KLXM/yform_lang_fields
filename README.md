# YForm Lang Fields

Mehrsprachige Felder für REDAXO YForm - Unterstützt einfache Text-, Textarea- und Media-Felder mit Editor-Integration (CKEditor 5, TinyMCE und weitere per Attributen).

## Update-Hinweis

Ab sofort wird die Editor-Auswahl bei lang_textarea ausschließlich über attributes gesteuert.
Das Legacy-Flag editor wird nicht mehr ausgewertet.

Migration:
- Alt: 'editor' => true
- Neu: attributes mit class, z. B. cke5-editor oder tiny-editor

## ✨ Features

- **3 Feldtypen**: Text, Textarea und Media mit vollständiger Mehrsprachigkeit
- **Editor-Unterstützung für Textareas**: CKEditor 5, TinyMCE und weitere Editoren über Attributübergabe
- **REDAXO Mediapool**: Nahtlose Integration mit dem REDAXO Media-Widget
- **Optionale Textfelder**: Zusätzliche Textfelder für Media (Alt-Text, Captions, etc.)
- **Kompaktes MBlock-Design**: Moderne Panel-basierte UI mit Delete-Buttons im Header
- **Dynamisches Hinzufügen/Entfernen**: Sprachen können jederzeit hinzugefügt oder entfernt werden
- **Beschreibungstexte**: Optionale Hilfstexte für alle Feldtypen
- **Responsive Design**: Optimiert für Desktop und Mobile

## 📦 Installation

1. Addon in `/redaxo/src/addons/yform_lang_fields/` entpacken
2. Im REDAXO-Backend unter "Addons" installieren und aktivieren
3. Sicherstellen, dass YForm (>= 4.0) installiert ist
4. Mindestens eine Sprache in REDAXO konfiguriert haben

## 🔧 Anforderungen

- REDAXO >= 5.15
- YForm >= 4.0
- Mediapool Addon (für Media-Felder)
- Font Awesome 6 (für Icons)
- Mindestens eine konfigurierte Sprache

## 📝 Feldtypen

### 1. Lang Text (`lang_text`)

Mehrsprachiges einzeiliges Textfeld.

```php
$yform->setValueField('lang_text', [
    'name' => 'title',
    'label' => 'Titel',
    'description' => 'Der Haupttitel der Seite'
]);
```

### 2. Lang Textarea (`lang_textarea`)

Mehrsprachiges mehrzeiliges Textfeld mit Attribut-basierter Editor-Integration.

**Ohne Editor:**
```php
$yform->setValueField('lang_textarea', [
    'name' => 'description',
    'label' => 'Beschreibung',
    'rows' => 5,
    'description' => 'Kurze Beschreibung'
]);
```

**Mit CKEditor 5:**
```php
$yform->setValueField('lang_textarea', [
    'name' => 'content',
    'label' => 'Inhalt',
    'rows' => 10,
    'attributes' => json_encode([
        'class' => 'cke5-editor',
        'profile' => 'default',   // Alias für data-profile
        'lang' => 'de'            // Alias für data-lang (optional)
    ])
]);
```

**Mit TinyMCE:**
```php
$yform->setValueField('lang_textarea', [
    'name' => 'content',
    'label' => 'Inhalt',
    'rows' => 10,
    'attributes' => json_encode([
        'class' => 'tiny-editor', // alternativ: tinyMCEEditor
        'profile' => 'default'
    ])
]);
```

Hinweis zu Attributen:
- `profile` wird automatisch auf `data-profile` gemappt.
- `lang` wird automatisch auf `data-lang` gemappt.
- Eigene Attribute werden beim Rendern und beim dynamischen Hinzufügen weiterer Sprachen übernommen.

### 3. Lang Media (`lang_media`)

Mehrsprachiges Media-Feld mit REDAXO Mediapool-Integration.

**Einfaches Media-Feld:**
```php
$yform->setValueField('lang_media', [
    'name' => 'image',
    'label' => 'Bild',
    'preview' => true,
    'types' => 'jpg,png,gif',
    'category' => 1
]);
```

**Media mit Textfeld (Alt-Text, Caption, etc.):**
```php
$yform->setValueField('lang_media', [
    'name' => 'hero_image',
    'label' => 'Hero Bild',
    'preview' => true,
    'with_text' => true,
    'text_label' => 'Bildunterschrift',
    'description' => 'Hauptbild für die Startseite'
]);
```

## 🎯 Verwendung

### Im Tablemanager

1. Neue Spalte erstellen
2. Feldtyp wählen: `lang_text`, `lang_textarea` oder `lang_media`
3. Parameter konfigurieren (siehe unten)
4. Speichern

### In YForm-Formularen

```php
$yform = new rex_yform();
$yform->setObjectparams('form_name', 'my_form');

// Text-Feld
$yform->setValueField('lang_text', [
    'name' => 'title',
    'label' => 'Titel',
    'description' => 'Der Seitentitel'
]);

// Textarea mit CKEditor 5 (Attribut-basiert)
$yform->setValueField('lang_textarea', [
    'name' => 'content',
    'label' => 'Inhalt',
    'attributes' => json_encode([
        'class' => 'cke5-editor',
        'profile' => 'default'
    ])
]);

// Media mit Textfeld
$yform->setValueField('lang_media', [
    'name' => 'image',
    'label' => 'Bild',
    'with_text' => true,
    'text_label' => 'Alt-Text'
]);
```

## ⚙️ Parameter

### Allgemeine Parameter (alle Feldtypen)

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `name` | string | - | Feldname (Pflicht) |
| `label` | string | - | Feldbezeichnung (Pflicht) |
| `description` | string | '' | Optionaler Hilfstext |
| `notice` | string | '' | Hinweistext |
| `required` | bool | false | Pflichtfeld |

### Lang Text Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `attributes` | json | '' | Zusätzliche HTML-Attribute |
| `list_lang` | int | '' | Sprach-ID für Listenspalte (leer = erste gespeicherte Übersetzung) |

### Lang Textarea Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `rows` | int | 5 | Anzahl der Zeilen |
| `attributes` | json | '' | Zusätzliche HTML-Attribute inkl. Editorsteuerung (`class`, optional `profile`, optional `lang`) |
| `list_lang` | int | '' | Sprach-ID für Listenspalte (leer = erste gespeicherte Übersetzung) |

### Lang Media Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `types` | string | '' | Erlaubte Dateitypen (z.B. 'jpg,png,gif') |
| `category` | int | '' | Mediapool-Kategorie ID |
| `preview` | bool | true | Bildvorschau anzeigen |
| `with_text` | bool | false | Zusätzliches Textfeld aktivieren |
| `text_label` | string | 'Beschreibung' | Label für Textfeld |
| `list_lang` | int | '' | Sprach-ID für Listenspalte (leer = erste gespeicherte Übersetzung) |

## 🗂️ Listenansicht

In der YForm-Datensätzeliste wird je Sprachfeld der erste (oder konfigurierte) Sprachenwert gekürzt angezeigt.
Zusätzliche Übersetzungen erscheinen als kleiner Badge (z. B. `+2`), der beim Hovern/Fokussieren ein Popover ohne Layout-Sprung einblendet.

**Anzeigesprache fest setzen** (z. B. immer Deutsch zuerst):
```php
$yform->setValueField('lang_text', [
    'name'      => 'title',
    'label'     => 'Titel',
    'list_lang' => 1   // Sprach-ID aus REDAXO System > Sprachen
]);
```

Ohne `list_lang` wird die erste im Datensatz gespeicherte Übersetzung angezeigt.

## 🔗 Anbindung an URL-Addon und YRewrite

Die eigentliche URL-Erzeugung bleibt im URL-Addon bzw. in YRewrite. Das YForm-Lang-Fields-Addon liefert nur den mehrsprachigen Inhalt, zum Beispiel den Titel, den man dann im URL-Addon pro Sprache in einen Slug übersetzen kann.

Ein typisches Muster im `URL_PRE_SAVE`-Hook ist:

```php
rex_extension::register('URL_PRE_SAVE', static function (rex_extension_point $ep) {
    $url = $ep->getSubject();
    $params = $ep->getParams();

    if (!$url instanceof \Url\Url) {
        return $url;
    }

    $clangId = (int) ($params['clang_id'] ?? 0);
    $dataId = (int) ($params['data_id'] ?? 0);

    if ($clangId < 1 || $dataId < 1) {
        return $url;
    }

    $dataset = \Article::get($dataId); // Beispiel: eigene Modelklasse
    if (!$dataset) {
        return $url;
    }

    $title = (string) $dataset->getLangValue('title', $clangId);
    if ('' === trim($title)) {
        return $url;
    }

    $slug = rex_string::normalize($title);
    $path = trim($url->getPath(), '/');
    $parts = '' === $path ? [] : explode('/', $path);

    if (!empty($parts)) {
        $parts[count($parts) - 1] = $slug;
        return new \Url\Url('/' . implode('/', $parts) . '/');
    }

    return $url;
});
```

Wenn ihr keine Modelklasse verwendet, könnt ihr die Übersetzung auch direkt aus dem JSON-Feld laden und den passenden `clang_id`-Eintrag herausfiltern.

## 📚 Beispiele

### Vollständiges Beispiel: Mehrsprachiger Blog-Artikel

```php
$yform = new rex_yform();
$yform->setObjectparams('form_name', 'blog_article');

// Titel
$yform->setValueField('lang_text', [
    'name' => 'title',
    'label' => 'Titel',
    'description' => 'Der Artikeltitel',
    'required' => true
]);

// Teaser
$yform->setValueField('lang_textarea', [
    'name' => 'teaser',
    'label' => 'Teaser',
    'rows' => 3,
    'description' => 'Kurze Zusammenfassung'
]);

// Hauptinhalt mit TinyMCE (Attribut-basiert)
$yform->setValueField('lang_textarea', [
    'name' => 'content',
    'label' => 'Inhalt',
    'rows' => 15,
    'attributes' => json_encode([
        'class' => 'tiny-editor',
        'profile' => 'default'
    ])
]);

// Titelbild mit Alt-Text
$yform->setValueField('lang_media', [
    'name' => 'featured_image',
    'label' => 'Beitragsbild',
    'preview' => true,
    'with_text' => true,
    'text_label' => 'Alt-Text',
    'types' => 'jpg,png,webp',
    'description' => 'Hauptbild des Artikels'
]);
```

### Daten auslesen

#### Mit Standard YOrm Dataset

```php
// Rohdaten (JSON-String)
$titleJson = $dataset->getValue('title');
// Gibt zurück: '[{"clang_id":1,"value":"Deutscher Titel"},{"clang_id":2,"value":"English Title"}]'

// Als Array mit LangHelper
$titleArray = \KLXM\YformLangFields\LangHelper::normalizeLanguageData($titleJson);
// Gibt zurück: [
//     ['clang_id' => 1, 'value' => 'Deutscher Titel'],
//     ['clang_id' => 2, 'value' => 'English Title']
// ]

// Wert für spezifische Sprache
$germanTitle = \KLXM\YformLangFields\LangHelper::getValueForLanguage($titleJson, 1);
// Gibt zurück: 'Deutscher Titel'
```

#### Mit LangDataset (Automatische Array-Konvertierung) ⭐

```php
use KLXM\YformLangFields\LangDataset;

class BlogArticle extends LangDataset
{
    public static function tableName()
    {
        return 'rex_blog_article'; // Deine YForm-Tabelle
    }
}

// Jetzt automatisch als Array!
$article = BlogArticle::get(1);
$titleArray = $article->getValue('title');
// Gibt zurück: [
//     ['clang_id' => 1, 'value' => 'Deutscher Titel'],
//     ['clang_id' => 2, 'value' => 'English Title']
// ]

// Convenience-Methoden
$currentTitle = $article->getLang('title'); // Aktuelle Sprache
$germanTitle = $article->getLangValue('title', 1); // Spezifische Sprache
$allTitles = $article->getAllLangValues('title'); // [1 => 'Deutscher Titel', 2 => 'English Title']

// Wert setzen
$article->setLangValue('title', 1, 'Neuer Titel');
$article->save();

// Raw JSON wenn nötig
$titleJson = $article->getRawValue('title');
```

## 🗄️ Datenstruktur

### Einfache Felder (Text, Textarea)

Daten werden als JSON-Array gespeichert:

```json
[
    {"clang_id": 1, "value": "Deutscher Text"},
    {"clang_id": 2, "value": "English text"}
]
```

### Media-Felder ohne Textfeld

```json
[
    {"clang_id": 1, "value": "bild.jpg"},
    {"clang_id": 2, "value": "image.jpg"}
]
```

### Media-Felder mit Textfeld

```json
[
    {
        "clang_id": 1,
        "value": {
            "media": "bild.jpg",
            "text": "Bildbeschreibung"
        }
    },
    {
        "clang_id": 2,
        "value": {
            "media": "image.jpg",
            "text": "Image description"
        }
    }
]
```

## 🎨 Styling & UI

### Kompaktes Panel-Design

Das Addon verwendet ein modernes, kompaktes Panel-Layout im MBlock-Stil:

- **Panel-Header**: Flaggen-Symbol, Sprachname und Delete-Button (oben rechts)
- **Panel-Body**: Feld-Content ohne zusätzliche Wrapper
- **Halbtransparenter Hintergrund**: 60% weißer Hintergrund für bessere Lesbarkeit
- **Responsive**: Optimiert für alle Bildschirmgrößen

### Anpassungen

CSS-Anpassungen können in `/assets/lang-fields.css` vorgenommen werden:

```css
/* Wrapper-Hintergrund ändern */
.yform-lang-field {
    background-color: rgba(255, 255, 255, 0.8); /* 80% statt 60% */
}

/* Panel-Farben anpassen */
.lang-field-item.panel {
    border-color: #0066cc;
}
```

## 🔧 Entwicklung

### Dateistruktur

```
yform_lang_fields/
├── assets/
│   ├── lang-fields.css       # Styling
│   └── lang-fields.js         # JavaScript-Funktionalität
├── lang/
│   ├── de_de.lang            # Deutsche Übersetzungen
│   └── en_gb.lang            # Englische Übersetzungen
├── lib/
│   ├── LangHelper.php        # Helper-Klasse
│   ├── rex_yform_value_lang_text.php
│   ├── rex_yform_value_lang_textarea.php
│   └── rex_yform_value_lang_media.php
├── ytemplates/
│   └── bootstrap/
│       └── value.lang_field.tpl.php  # Haupt-Template
├── boot.php                  # Addon-Bootstrap
├── package.yml              # Addon-Konfiguration
└── README.md               # Diese Datei
```

### CKEditor 5 Integration

Das Addon verwendet die GPL-Version von CKEditor 5 mit dem "ck"-Präfix für IDs:

```javascript
// Automatische Initialisierung
initCKE5ForNewField: function($field) {
    var $textarea = $field.find('.cke5-editor');
    if ($textarea.length && typeof ClassicEditor !== 'undefined') {
        var editorId = 'ck' + $textarea.attr('id'); // GPL-konform
        ClassicEditor.create(document.getElementById(editorId))
            .catch(error => console.error(error));
    }
}
```

### Media Widget Integration

Das Addon nutzt die REDAXO Media-Widget-Funktionen:

```javascript
// Numerische IDs für Media-Widgets
static $widgetCounter = 0;
$widgetCounter++;
$widgetId = $widgetCounter;

// Widget öffnen
openREXMedia(<?= $widgetId ?>, '<?= $mediaParams ?>');

// Widget löschen
deleteREXMedia(<?= $widgetId ?>);
```

### Event-System

```javascript
// Sprache hinzufügen
$(document).on('click', '.btn-add-lang-field', function(e) {
    e.preventDefault();
    YformLangFields.addLanguageField($(this));
});

// Sprache entfernen
$(document).on('click', '.btn-remove-lang-field', function(e) {
    e.preventDefault();
    YformLangFields.removeLanguageField($(this));
});
```

## 🐛 Troubleshooting

### CKEditor wird nicht geladen

- Stelle sicher, dass CKEditor 5 im Backend geladen wird
- Prüfe die Browser-Konsole auf JavaScript-Fehler
- Verwende das "ck"-Präfix für Editor-IDs (GPL-konform)

### Media-Widget funktioniert nicht

- Prüfe, ob das Mediapool-Addon installiert ist
- Stelle sicher, dass numerische IDs verwendet werden (keine Strings)
- Überprüfe die Browser-Konsole auf Fehler

### Sprachen werden nicht angezeigt

- Mindestens eine Sprache muss in REDAXO konfiguriert sein
- Prüfe die Sprachkonfiguration unter System > Sprachen

### Delete-Button reagiert nicht

- Stelle sicher, dass JavaScript geladen wird
- Prüfe auf CSS z-index Konflikte
- Überprüfe die Browser-Konsole auf Fehler

## 📄 Lizenz

MIT License

## 👤 Author

**KLXM Crossmedia / Thomas Skerbis**  
Website: [https://klxm.de](https://klxm.de)

## 🤝 Credits

Mit Unterstützung von **GitHub Copilot** 

- [REDAXO](https://redaxo.org/)
- [YForm](https://github.com/yakamara/redaxo_yform)
- [CKEditor 5](https://ckeditor.com/)

---

**Version**: 1.0.0  
**Letztes Update**: Oktober 2025
