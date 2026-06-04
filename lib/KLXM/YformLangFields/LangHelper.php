<?php

namespace KLXM\YformLangFields;

use rex_clang;
use rex_i18n;

class LangHelper
{
    /**
     * Alle aktiven Sprachen abrufen.
     *
     * @return array<int, rex_clang>
     */
    public static function getActiveLanguages(): array
    {
        return rex_clang::getAll();
    }

    /**
     * Aktuelle Backend-Sprache abrufen.
     */
    public static function getCurrentLanguage(): rex_clang
    {
        return rex_clang::getCurrent();
    }

    /**
     * JSON-Daten für Sprachfeld validieren und normalisieren.
     *
     * @param mixed $data
     *
     * @return list<array{clang_id: int, value: mixed}>
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
            if (!is_array($item) || !isset($item['clang_id']) || !isset($languages[(int) $item['clang_id']])) {
                continue;
            }

            $clangId = (int) $item['clang_id'];

            // Nur eine Übersetzung pro Sprache erlauben
            if (!isset($normalized[$clangId])) {
                $normalized[$clangId] = [
                    'clang_id' => $clangId,
                    'value' => $item['value'] ?? '',
                ];
            }
        }

        return array_values($normalized);
    }

    /**
     * Wert für bestimmte Sprache aus JSON-Daten extrahieren.
     *
     * @param mixed $data
     */
    public static function getValueForLanguage($data, int $clangId): string
    {
        $normalized = self::normalizeLanguageData($data);

        foreach ($normalized as $item) {
            if ($item['clang_id'] === $clangId) {
                return is_scalar($item['value']) ? (string) $item['value'] : '';
            }
        }

        return '';
    }

    /**
     * Überprüfen ob für Sprache eine Übersetzung existiert.
     *
     * @param mixed $data
     */
    public static function hasTranslationForLanguage($data, int $clangId): bool
    {
        $value = self::getValueForLanguage($data, $clangId);
        return '' !== trim($value);
    }

    /**
     * HTML für Sprach-Select generieren.
     */
    public static function getLanguageSelectHtml(string $name, ?int $selectedId = null): string
    {
        $languages = self::getActiveLanguages();
        $html = '<select name="' . rex_escape($name) . '" class="form-control lang-select">';
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
     * Baut die HTML-Ausgabe für YForm-Listenansichten von Sprachfeldern.
     *
     * Zeigt den ersten Sprachenwert gekürzt an; weitere Sprachen werden
     * als Bootstrap-Popover (Hover) ohne Layout-Sprünge eingeblendet.
     *
     * Rendert ALLE Sprachen als datenbewusste Spans; welche sichtbar ist,
     * steuert JS anhand des localStorage-Werts `ylf_active_clang`.
     * $preferredClangId dient als CSS-only-Fallback ohne JS.
     *
     * @param list<array{clang_id: int, value: mixed}> $parsed
     * @param 'text'|'media' $mode 'text' für Text/Textarea, 'media' für Media-Felder
     */
    public static function buildListPopover(array $parsed, string $mode = 'text', int $preferredClangId = 0): string
    {
        $onlineClangs = rex_clang::getAll(true);
        $totalOnline = count($onlineClangs);

        if (empty($parsed)) {
            $classes = $totalOnline > 0 ? 'ylf-list-entry ylf-is-incomplete' : 'ylf-list-entry';
            return '<span class="' . $classes . '" data-ylf-default="0"><span>-</span></span>';
        }

        $spans = [];
        $firstNonEmpty = null;
        $translatedOnlineCount = 0;

        foreach ($parsed as $item) {
            if (empty($item['value'])) {
                continue;
            }

            $clangId = (int) $item['clang_id'];
            $clang = rex_clang::get($clangId);
            $code = rex_escape($clang ? mb_strtoupper($clang->getCode()) : (string) $clangId);

            if ('media' === $mode) {
                if (is_array($item['value'])) {
                    $text = isset($item['value']['media']) && is_scalar($item['value']['media'])
                        ? rex_escape((string) $item['value']['media'])
                        : '';
                } else {
                    $text = rex_escape((string) $item['value']);
                }
            } else {
                $raw = is_scalar($item['value']) ? strip_tags((string) $item['value']) : '';
                $text = rex_escape(mb_substr($raw, 0, 60)) . (mb_strlen($raw) > 60 ? '…' : '');
            }

            if ('' === $text) {
                continue;
            }

            if (isset($onlineClangs[$clangId])) {
                $translatedOnlineCount++;
            }

            if (null === $firstNonEmpty) {
                $firstNonEmpty = $clangId;
            }

            // data-ylf-clang wird von JS genutzt; CSS blendet alle außer der aktiven aus
            $spans[] = '<span class="ylf-list-clang" data-ylf-clang="' . $clangId . '">'
                . '<span class="ylf-list-code">' . $code . '</span>&nbsp;'
                . '<span class="ylf-list-val">' . $text . '</span>'
                . '</span>';
        }

        if (empty($spans)) {
            return '<span>-</span>';
        }

        // data-ylf-default: statischer Fallback wenn kein JS / kein localStorage
        $default = $preferredClangId > 0 ? $preferredClangId : (int) $firstNonEmpty;

        $isIncomplete = $translatedOnlineCount < $totalOnline;
        $classes = 'ylf-list-entry';
        if ($isIncomplete) {
            $classes .= ' ylf-is-incomplete';
        }

        return '<span class="' . $classes . '" data-ylf-default="' . $default . '">'
            . implode('', $spans)
            . '</span>';
    }

    /**
     * Gibt zurück ob eine Tabelle mindestens ein lang_*-Feld besitzt.
     *
     * @param array<mixed> $fields YForm-Felder der Tabelle
     */
    public static function tableHasLangFields(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field instanceof \rex_yform_manager_field) {
                $type = $field->getTypeName();
                if (in_array($type, ['lang_text', 'lang_textarea', 'lang_media'], true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Verfügbare Sprachen für neue Übersetzungen.
     *
     * @param mixed $existingData
     *
     * @return list<rex_clang>
     */
    public static function getAvailableLanguages($existingData): array
    {
        $normalized = self::normalizeLanguageData($existingData);
        $usedLanguages = array_column($normalized, 'clang_id');
        $allLanguages = self::getActiveLanguages();

        $available = [];
        foreach ($allLanguages as $lang) {
            if (!in_array($lang->getId(), $usedLanguages, true)) {
                $available[] = $lang;
            }
        }

        return $available;
    }

    /**
     * Resolves a language ID from string or int (support 'en', 'de', 1, 2)
     */
    /** @param mixed $input */
    public static function resolveClangId($input): int
    {
        if (is_numeric($input)) {
            $id = (int) $input;
            if ($id > 0 && 
rex_clang::exists($id)) {
                return $id;
            }
            return 0;
        }

        if (is_string($input) && $input !== '') {
            $code = strtolower(trim($input));
            foreach (
rex_clang::getAll() as $clang) {
                if (strtolower($clang->getCode()) === $code) {
                    return $clang->getId();
                }
            }
        }
        return 0;
    }
}
