<?php

namespace KLXM\YformLangFields;

use rex_yform_manager_dataset;

/**
 * Erweiterte Dataset-Klasse mit mehrsprachigen Methoden
 */
class LangDataset extends rex_yform_manager_dataset
{
    /**
     * Wert für bestimmte Sprache abrufen
     */
    public function getValueInLanguage(string $field, int $clangId): string
    {
        $data = $this->getValue($field);
        return LangHelper::getValueForLanguage($data, $clangId);
    }

    /**
     * Wert für aktuelle Sprache abrufen
     */
    public function getValueInCurrentLanguage(string $field): string
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->getValueInLanguage($field, $currentLang->getId());
    }

    /**
     * Alle Übersetzungen für ein Feld abrufen
     */
    public function getAllTranslations(string $field): array
    {
        $data = $this->getValue($field);
        return LangHelper::normalizeLanguageData($data);
    }

    /**
     * Prüfen ob Übersetzung für Sprache existiert
     */
    public function hasTranslationForLanguage(string $field, int $clangId): bool
    {
        $data = $this->getValue($field);
        return LangHelper::hasTranslationForLanguage($data, $clangId);
    }

    /**
     * Übersetzung für Sprache setzen
     */
    public function setValueInLanguage(string $field, int $clangId, string $value): self
    {
        $currentData = $this->getAllTranslations($field);
        
        // Bestehende Übersetzung für diese Sprache entfernen
        $currentData = array_filter($currentData, function($item) use ($clangId) {
            return $item['clang_id'] !== $clangId;
        });
        
        // Neue Übersetzung hinzufügen (nur wenn nicht leer)
        if (!empty(trim($value))) {
            $currentData[] = [
                'clang_id' => $clangId,
                'value' => $value
            ];
        }
        
        // Als JSON speichern
        $jsonData = json_encode(array_values($currentData), JSON_UNESCAPED_UNICODE);
        $this->setValue($field, $jsonData);
        
        return $this;
    }

    /**
     * Verfügbare Sprachen für ein Feld abrufen (die noch keine Übersetzung haben)
     */
    public function getAvailableLanguagesForField(string $field): array
    {
        $data = $this->getValue($field);
        return LangHelper::getAvailableLanguages($data);
    }

    /**
     * Alle Sprachen mit Übersetzungen für ein Feld abrufen
     */
    public function getTranslatedLanguagesForField(string $field): array
    {
        $translations = $this->getAllTranslations($field);
        $languages = [];
        
        foreach ($translations as $translation) {
            $clang = \rex_clang::get($translation['clang_id']);
            if ($clang) {
                $languages[] = $clang;
            }
        }
        
        return $languages;
    }

    /**
     * Mehrsprachige Felder einer Tabelle identifizieren
     */
    public static function getMultilangFields(string $tableName): array
    {
        $table = \rex_yform_manager_table::get($tableName);
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