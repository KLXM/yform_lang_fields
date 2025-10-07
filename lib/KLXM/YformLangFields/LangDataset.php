<?php

namespace KLXM\YformLangFields;

use rex_yform_manager_dataset;
use rex_yform_manager_table;

/**
 * Erweiterte YOrm Dataset-Klasse mit automatischer Array-Konvertierung für Lang-Felder
 * 
 * Verwendung:
 * class Article extends \KLXM\YformLangFields\LangDataset
 * {
 *     // Automatische Array-Konvertierung für alle lang_* Felder
 *     // $article->getValue('title') gibt direkt ein Array zurück
 * }
 */
class LangDataset extends rex_yform_manager_dataset
{
    /**
     * Überschreibt getValue() um Lang-Felder automatisch als Array zurückzugeben
     */
    public function getValue($key, $default = null)
    {
        $value = parent::getValue($key, $default);
        
        // Prüfen ob es ein Lang-Feld ist
        if ($this->isLangField($key)) {
            // JSON automatisch als Array zurückgeben
            return LangHelper::normalizeLanguageData($value);
        }
        
        return $value;
    }

    /**
     * Raw-Wert (JSON-String) abrufen ohne Konvertierung
     */
    public function getRawValue($key, $default = null)
    {
        return parent::getValue($key, $default);
    }

    /**
     * Prüft ob ein Feld ein Lang-Feld ist
     */
    protected function isLangField(string $fieldName): bool
    {
        static $langFields = null;
        
        if ($langFields === null) {
            $langFields = [];
            $table = rex_yform_manager_table::get(static::getTableName());
            
            if ($table) {
                $fields = $table->getValueFields();
                foreach ($fields as $field) {
                    $typeName = $field->getTypeName();
                    if (in_array($typeName, ['lang_text', 'lang_textarea', 'lang_media'])) {
                        $langFields[] = $field->getName();
                    }
                }
            }
        }
        
        return in_array($fieldName, $langFields);
    }

    /**
     * Convenience-Methode: Wert für aktuelle Sprache
     */
    public function getLang($key)
    {
        $data = $this->getValue($key); // Bereits als Array
        $currentLang = LangHelper::getCurrentLanguage();
        
        foreach ($data as $item) {
            if ($item['clang_id'] === $currentLang->getId()) {
                return $item['value'];
            }
        }
        
        return '';
    }

    /**
     * Convenience-Methode: Wert für spezifische Sprache
     */
    public function getLangValue($key, int $clangId)
    {
        $data = $this->getValue($key); // Bereits als Array
        
        foreach ($data as $item) {
            if ($item['clang_id'] === $clangId) {
                return $item['value'];
            }
        }
        
        return '';
    }

    /**
     * Alle verfügbaren Übersetzungen als assoziatives Array [clang_id => value]
     */
    public function getAllLangValues($key): array
    {
        $data = $this->getValue($key); // Bereits als Array
        $result = [];
        
        foreach ($data as $item) {
            $result[$item['clang_id']] = $item['value'];
        }
        
        return $result;
    }

    /**
     * Setzt Wert für spezifische Sprache
     */
    public function setLangValue($key, int $clangId, $value): self
    {
        $currentData = $this->getValue($key); // Bereits als Array
        
        // Bestehende Übersetzung entfernen
        $currentData = array_filter($currentData, function($item) use ($clangId) {
            return $item['clang_id'] !== $clangId;
        });
        
        // Neue Übersetzung hinzufügen
        if (!empty($value) || $value === 0 || $value === '0') {
            $currentData[] = [
                'clang_id' => $clangId,
                'value' => $value
            ];
        }
        
        // Als JSON speichern (Raw-Wert)
        $jsonData = json_encode(array_values($currentData), JSON_UNESCAPED_UNICODE);
        parent::setValue($key, $jsonData);
        
        return $this;
    }

    /**
     * Prüfen ob Übersetzung für Sprache existiert
     */
    public function hasTranslationForLanguage(string $field, int $clangId): bool
    {
        $data = $this->getValue($field);
        
        foreach ($data as $item) {
            if ($item['clang_id'] === $clangId && !empty($item['value'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mehrsprachige Felder einer Tabelle identifizieren
     */
    public static function getMultilangFields(string $tableName): array
    {
        $table = rex_yform_manager_table::get($tableName);
        if (!$table) {
            return [];
        }

        $multilangFields = [];
        $fields = $table->getValueFields();
        
        foreach ($fields as $field) {
            $typeName = $field->getTypeName();
            if (in_array($typeName, ['lang_text', 'lang_textarea', 'lang_media'])) {
                $multilangFields[] = $field->getName();
            }
        }
        
        return $multilangFields;
    }

    /**
     * Prüfen ob ein Dataset vollständig übersetzt ist
     */
    public function isFullyTranslatedFor(array $requiredLanguages): bool
    {
        $multilangFields = self::getMultilangFields($this->getTableName());
        
        foreach ($multilangFields as $fieldName) {
            foreach ($requiredLanguages as $clangId) {
                if (!$this->hasTranslationForLanguage($fieldName, $clangId)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Übersetzungsstatus für alle Sprachen abrufen
     */
    public function getTranslationStatus(): array
    {
        $multilangFields = self::getMultilangFields($this->getTableName());
        $languages = LangHelper::getActiveLanguages();
        $status = [];
        
        foreach ($languages as $lang) {
            $clangId = $lang->getId();
            $status[$clangId] = [
                'language' => $lang,
                'translated_fields' => 0,
                'total_fields' => count($multilangFields),
                'is_complete' => true
            ];
            
            foreach ($multilangFields as $fieldName) {
                if ($this->hasTranslationForLanguage($fieldName, $clangId)) {
                    $status[$clangId]['translated_fields']++;
                } else {
                    $status[$clangId]['is_complete'] = false;
                }
            }
            
            $status[$clangId]['percentage'] = $status[$clangId]['total_fields'] > 0 
                ? round(($status[$clangId]['translated_fields'] / $status[$clangId]['total_fields']) * 100)
                : 100;
        }
        
        return $status;
    }
}