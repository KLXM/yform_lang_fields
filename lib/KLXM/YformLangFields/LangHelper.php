<?php

namespace KLXM\YformLangFields;

use rex_clang;
use rex_i18n;

class LangHelper
{
    /**
     * Alle aktiven Sprachen abrufen
     */
    public static function getActiveLanguages(): array
    {
        return rex_clang::getAll();
    }

    /**
     * Aktuelle Backend-Sprache abrufen
     */
    public static function getCurrentLanguage(): rex_clang
    {
        return rex_clang::getCurrent();
    }

    /**
     * JSON-Daten für Sprachfeld validieren und normalisieren
     */
    public static function normalizeLanguageData($data): array
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            return [];
        }

        $normalized = [];
        $languages = self::getActiveLanguages();

        foreach ($data as $item) {
            if (!isset($item['clang_id']) || !isset($languages[$item['clang_id']])) {
                continue;
            }

            $clangId = (int) $item['clang_id'];
            
            // Nur eine Übersetzung pro Sprache erlauben
            if (!isset($normalized[$clangId])) {
                $normalized[$clangId] = [
                    'clang_id' => $clangId,
                    'value' => $item['value'] ?? ''
                ];
            }
        }

        return array_values($normalized);
    }

    /**
     * Wert für bestimmte Sprache aus JSON-Daten extrahieren
     */
    public static function getValueForLanguage($data, int $clangId): string
    {
        $normalized = self::normalizeLanguageData($data);
        
        foreach ($normalized as $item) {
            if ($item['clang_id'] === $clangId) {
                return $item['value'];
            }
        }

        return '';
    }

    /**
     * Überprüfen ob für Sprache eine Übersetzung existiert
     */
    public static function hasTranslationForLanguage($data, int $clangId): bool
    {
        $value = self::getValueForLanguage($data, $clangId);
        return !empty(trim($value));
    }

    /**
     * HTML für Sprach-Select generieren
     */
    public static function getLanguageSelectHtml(string $name, int $selectedId = null): string
    {
        $languages = self::getActiveLanguages();
        $html = '<select name="' . $name . '" class="form-control lang-select">';
        $html .= '<option value="">' . rex_i18n::msg('yform_lang_fields_select_language') . '</option>';
        
        foreach ($languages as $lang) {
            $selected = $selectedId === $lang->getId() ? ' selected' : '';
            $html .= '<option value="' . $lang->getId() . '"' . $selected . '>';
            $html .= rex_escape($lang->getName() . ' (' . $lang->getCode() . ')');
            $html .= '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }

    /**
     * Verfügbare Sprachen für neue Übersetzungen
     */
    public static function getAvailableLanguages($existingData): array
    {
        // Sicherstellen, dass existingData ein Array ist
        if (!is_array($existingData)) {
            $existingData = [];
        }
        
        $normalized = self::normalizeLanguageData($existingData);
        
        // Sicherstellen, dass $normalized ein Array ist vor array_column
        if (!is_array($normalized)) {
            $normalized = [];
        }
        
        $usedLanguages = is_array($normalized) && !empty($normalized) ? array_column($normalized, 'clang_id') : [];
        $allLanguages = self::getActiveLanguages();
        
        $available = [];
        foreach ($allLanguages as $lang) {
            if (!in_array($lang->getId(), $usedLanguages)) {
                $available[] = $lang;
            }
        }
        
        return $available;
    }
}