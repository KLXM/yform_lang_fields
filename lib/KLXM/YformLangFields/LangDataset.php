<?php

namespace KLXM\YformLangFields;

use rex_clang;
use rex_yform_manager_dataset;
use rex_yform_manager_table;

/**
 * Erweiterte YOrm Dataset-Klasse mit automatischer Array-Konvertierung für Lang-Felder.
 *
 * Verwendung:
 *   class Article extends \KLXM\YformLangFields\LangDataset
 *   {
 *       // $article->getValue('title') gibt direkt ein Array zurück
 *   }
 */
class LangDataset extends rex_yform_manager_dataset
{
    /**
     * Überschreibt getValue() um Lang-Felder automatisch als Array zurückzugeben.
     *
     * @return mixed
     */
    public function getValue(string $key)
    {
        $value = parent::getValue($key);

        if ($this->isLangField($key)) {
            return LangHelper::normalizeLanguageData($value);
        }

        return $value;
    }

    /**
     * Raw-Wert (JSON-String) abrufen ohne Konvertierung.
     *
     * @return mixed
     */
    public function getRawValue(string $key)
    {
        return parent::getValue($key);
    }

    /**
     * Prüft ob ein Feld ein Lang-Feld ist.
     */
    protected function isLangField(string $fieldName): bool
    {
        /** @var array<string, list<string>> $cache */
        static $cache = [];

        $tableName = static::getTableName();

        if (!isset($cache[$tableName])) {
            $cache[$tableName] = [];
            $table = rex_yform_manager_table::get($tableName);

            if ($table) {
                foreach ($table->getValueFields() as $field) {
                    $typeName = $field->getTypeName();
                    if (in_array($typeName, ['lang_text', 'lang_textarea', 'lang_media'], true)) {
                        $cache[$tableName][] = $field->getName();
                    }
                }
            }
        }

        return in_array($fieldName, $cache[$tableName], true);
    }

    /**
     * Convenience-Methode: Wert für aktuelle Sprache.
     *
     * @return mixed
     */
    public function getLang(string $key)
    {
        /** @var list<array{clang_id: int|string, value: mixed}> $data */
        $data = $this->getValue($key);
        $currentLang = LangHelper::getCurrentLanguage();

        foreach ($data as $item) {
            if ((int) $item['clang_id'] === $currentLang->getId()) {
                return $item['value'];
            }
        }

        return '';
    }

    /**
     * Convenience-Methode: Wert für spezifische Sprache.
     *
     * @return mixed
     */
    public function getLangValue(string $key, int $clangId)
    {
        /** @var list<array{clang_id: int|string, value: mixed}> $data */
        $data = $this->getValue($key);

        foreach ($data as $item) {
            if ((int) $item['clang_id'] === $clangId) {
                return $item['value'];
            }
        }

        return '';
    }

    /**
     * Alle verfügbaren Übersetzungen als assoziatives Array [clang_id => value].
     *
     * @return array<int, mixed>
     */
    public function getAllLangValues(string $key): array
    {
        /** @var list<array{clang_id: int|string, value: mixed}> $data */
        $data = $this->getValue($key);
        $result = [];

        foreach ($data as $item) {
            $result[(int) $item['clang_id']] = $item['value'];
        }

        return $result;
    }

    /**
     * Setzt Wert für spezifische Sprache.
     *
     * @param mixed $value
     */
    public function setLangValue(string $key, int $clangId, $value): self
    {
        /** @var list<array{clang_id: int|string, value: mixed}> $currentData */
        $currentData = $this->getValue($key);

        // Bestehende Übersetzung entfernen
        $currentData = array_values(array_filter($currentData, static function (array $item) use ($clangId): bool {
            return (int) $item['clang_id'] !== $clangId;
        }));

        // Neue Übersetzung hinzufügen
        if (!empty($value) || 0 === $value || '0' === $value) {
            $currentData[] = [
                'clang_id' => $clangId,
                'value' => $value,
            ];
        }

        $jsonData = json_encode($currentData, JSON_UNESCAPED_UNICODE);
        if (false === $jsonData) {
            $jsonData = '[]';
        }
        parent::setValue($key, $jsonData);

        return $this;
    }

    /**
     * Prüfen ob Übersetzung für Sprache existiert.
     */
    public function hasTranslationForLanguage(string $field, int $clangId): bool
    {
        /** @var list<array{clang_id: int|string, value: mixed}> $data */
        $data = $this->getValue($field);

        foreach ($data as $item) {
            if ((int) $item['clang_id'] === $clangId && !empty($item['value'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mehrsprachige Felder einer Tabelle identifizieren.
     *
     * @return list<string>
     */
    public static function getMultilangFields(string $tableName): array
    {
        $table = rex_yform_manager_table::get($tableName);
        if (!$table) {
            return [];
        }

        $multilangFields = [];
        foreach ($table->getValueFields() as $field) {
            $typeName = $field->getTypeName();
            if (in_array($typeName, ['lang_text', 'lang_textarea', 'lang_media'], true)) {
                $multilangFields[] = $field->getName();
            }
        }

        return $multilangFields;
    }

    /**
     * Prüfen ob ein Dataset vollständig übersetzt ist.
     *
     * @param list<int> $requiredLanguages
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
     * Übersetzungsstatus für alle Sprachen abrufen.
     *
     * @return array<int, array{language: rex_clang, translated_fields: int, total_fields: int, is_complete: bool, percentage: int}>
     */
    public function getTranslationStatus(): array
    {
        $multilangFields = self::getMultilangFields($this->getTableName());
        $totalFields = count($multilangFields);
        $languages = LangHelper::getActiveLanguages();
        $status = [];

        foreach ($languages as $lang) {
            $clangId = $lang->getId();
            $translated = 0;
            $isComplete = true;

            foreach ($multilangFields as $fieldName) {
                if ($this->hasTranslationForLanguage($fieldName, $clangId)) {
                    ++$translated;
                } else {
                    $isComplete = false;
                }
            }

            $percentage = $totalFields > 0
                ? (int) round(($translated / $totalFields) * 100)
                : 100;

            $status[$clangId] = [
                'language' => $lang,
                'translated_fields' => $translated,
                'total_fields' => $totalFields,
                'is_complete' => $isComplete,
                'percentage' => $percentage,
            ];
        }

        return $status;
    }
}
