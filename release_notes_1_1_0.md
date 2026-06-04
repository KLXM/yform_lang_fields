⚠️ **Wichtiger Migrationshinweis: Wegfall der "editor" Option**

Die Legacy-Option `editor` (für die direkte Auswahl des Editors bei `lang_textarea`) wurde in diesem Release endgültig entfernt. Die Zuweisung eines Editors erfolgt ab sofort ausschließlich über das `attributes` Feld!

**So aktualisieren Sie bestehende Felder in den YForm Tabellen-Einstellungen:**
1. Rufen Sie die Feld-Definition für das `lang_textarea` Feld auf.
2. Das Feld für den Editor-Typ gibt es nicht mehr.
3. Tragen Sie im Feld `Attributes` die entsprechende CSS-Klasse ein, z.B.:
   `{"class":"cke5-editor"}` oder `{"class":"tiny-editor"}`
4. *Tipp:* Optional können in den Attributen auch `profile` und `lang` als Alias verwendet werden. Diese werden dann intern automatisch als `data-profile` und `data-lang` gemappt. Dynamisch hinzugefügte Sprachfelder (über Tabs) initialisieren jetzt nur noch exakt den Editor, der auf diese Weise konfiguriert ist.

---

### ✨ Neue Funktionen
- **Sprachumschalter in der YForm-Listenansicht:** Über ein neues Dropdown in der Toolbar kann die angezeigte Sprache für alle Sprachfelder innerhalb der Datentabelle ab sofort live umgeschaltet werden.
- **Dezentes Sprach-Label:** Die jeweiligen Sprachkürzel (z.B. `IT`, `DE`) in der Listenansicht werden ab jetzt in Großbuchstaben in einem dezent formatierten, Dark-Mode-kompatiblen Badge vor dem eigentlichen Inhalt dargestellt.

### 🔄 Änderungen
- **Obsolete Einstellung entfernt:** Der Konfigurationswert `list_lang` (Anzeigesprache in YForm-Listen) wurde aus den Value-Settings von `lang_text`, `lang_textarea` und `lang_media` entfernt, da die angezeigte Sprache nun weitaus praktischer, global über den neuen Tabellen-Umschalter gesteuert wird.

### 🐛 Fehlerbehebungen & Wartung
- **Drop-Down Fix:** Es wurde ein Problem im Javascript behoben, das ein wiederholtes Wrapping der Button-Group verhinderte, dass sich das neu eingeführte Sprach-Dropdown öffnet.
- **Typensicherheit:** Es wurde nun eine Rexstan (Level 9) Analyse in das Setup integriert. Daraufhin wurden alle verbliebenen potenziellen Typ-Probleme (`is_scalar`, PHP-Doc Checks etc.) bereinigt.
