<?php

namespace KLXM\YformLangFields;

use rex_yform_manager_dataset;
use rex_clang;

/**
 * YOrm-Integration für mehrsprachige Felder
 * Diese Klasse erweitert YOrm-Models um mehrsprachige Funktionalität
 */
trait YormLangTrait
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
     * Wert für bestimmte Sprache setzen
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
            $clang = rex_clang::get($translation['clang_id']);
            if ($clang) {
                $languages[] = $clang;
            }
        }
        
        return $languages;
    }

    /**
     * Übersetzungsstatus für alle mehrsprachigen Felder abrufen
     */
    public function getTranslationStatus(): array
    {
        $multilangFields = $this->getMultilangFields();
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

    /**
     * Mehrsprachige Felder der aktuellen Tabelle identifizieren
     */
    public function getMultilangFields(): array
    {
        $table = \rex_yform_manager_table::get($this->getTableName());
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
     * Prüfen ob Dataset vollständig übersetzt ist
     */
    public function isFullyTranslatedFor(array $requiredLanguages): bool
    {
        $multilangFields = $this->getMultilangFields();
        
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
     * Fallback-Wert mit Sprachpriorität abrufen
     * Versucht zuerst die gewünschte Sprache, dann Fallback-Sprachen
     */
    public function getValueWithFallback(string $field, int $preferredClangId, array $fallbackClangIds = []): string
    {
        // Zuerst gewünschte Sprache versuchen
        $value = $this->getValueInLanguage($field, $preferredClangId);
        if (!empty($value)) {
            return $value;
        }

        // Fallback-Sprachen durchgehen
        foreach ($fallbackClangIds as $clangId) {
            $value = $this->getValueInLanguage($field, $clangId);
            if (!empty($value)) {
                return $value;
            }
        }

        // Erste verfügbare Übersetzung nehmen
        $translations = $this->getAllTranslations($field);
        if (!empty($translations)) {
            return $translations[0]['value'];
        }

        return '';
    }

    /**
     * Magic Method für sprachspezifische Getter
     * Beispiel: $model->getTitleInDe() -> getValueInLanguage('title', 1)
     */
    public function __call($method, $args)
    {
        // Pattern: getTitleInDe, getTitleInEn, etc.
        if (preg_match('/^get([A-Z][a-zA-Z]+)In([A-Z][a-z]+)$/', $method, $matches)) {
            $fieldName = lcfirst($matches[1]);
            $langCode = strtolower($matches[2]);
            
            // Sprache anhand Code finden
            $languages = LangHelper::getActiveLanguages();
            foreach ($languages as $lang) {
                if (strtolower($lang->getCode()) === $langCode) {
                    return $this->getValueInLanguage($fieldName, $lang->getId());
                }
            }
        }

        // Pattern: setTitleInDe, setTitleInEn, etc.
        if (preg_match('/^set([A-Z][a-zA-Z]+)In([A-Z][a-z]+)$/', $method, $matches)) {
            $fieldName = lcfirst($matches[1]);
            $langCode = strtolower($matches[2]);
            $value = $args[0] ?? '';
            
            // Sprache anhand Code finden
            $languages = LangHelper::getActiveLanguages();
            foreach ($languages as $lang) {
                if (strtolower($lang->getCode()) === $langCode) {
                    return $this->setValueInLanguage($fieldName, $lang->getId(), $value);
                }
            }
        }

        // Fallback zu Parent-Klasse
        if (method_exists(get_parent_class($this), '__call')) {
            return parent::__call($method, $args);
        }

        throw new \BadMethodCallException("Method {$method} does not exist");
    }
}