<?php

namespace KLXM\YformLangFields;

use rex_yform_manager_query;
use rex_clang;

/**
 * Erweiterte Query-Klasse für mehrsprachige YOrm-Abfragen
 */
class LangQuery extends rex_yform_manager_query
{
    /**
     * Nach Übersetzung in bestimmter Sprache filtern
     */
    public function whereTranslationExists(string $field, int $clangId): self
    {
        $this->whereRaw("JSON_EXTRACT(`{$field}`, '$[*].clang_id') LIKE '%{$clangId}%'");
        return $this;
    }

    /**
     * Nach nicht-leerer Übersetzung in bestimmter Sprache filtern
     */
    public function whereTranslationNotEmpty(string $field, int $clangId): self
    {
        $this->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$[*].value[0]')) != '' AND JSON_EXTRACT(`{$field}`, '$[*].clang_id') = {$clangId}");
        return $this;
    }

    /**
     * Nach Übersetzung in aktueller Sprache filtern
     */
    public function whereCurrentLanguageExists(string $field): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->whereTranslationExists($field, $currentLang->getId());
    }

    /**
     * Nach Text in bestimmter Sprache suchen
     */
    public function whereTranslationLike(string $field, int $clangId, string $value): self
    {
        // JSON-Suche nach Wert in bestimmter Sprache
        $this->whereRaw("EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (clang_id INT PATH '$.clang_id', value TEXT PATH '$.value')) AS jt WHERE jt.clang_id = {$clangId} AND jt.value LIKE ?)", ["%{$value}%"]);
        return $this;
    }

    /**
     * Nach Text in aktueller Sprache suchen
     */
    public function whereCurrentLanguageLike(string $field, string $value): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->whereTranslationLike($field, $currentLang->getId(), $value);
    }

    /**
     * Nach vollständig übersetzten Datensätzen filtern
     */
    public function whereFullyTranslated(array $fields, array $requiredLanguages): self
    {
        $conditions = [];
        
        foreach ($fields as $field) {
            foreach ($requiredLanguages as $clangId) {
                $conditions[] = "JSON_EXTRACT(`{$field}`, '$[*].clang_id') LIKE '%{$clangId}%'";
            }
        }
        
        if (!empty($conditions)) {
            $this->whereRaw('(' . implode(' AND ', $conditions) . ')');
        }
        
        return $this;
    }

    /**
     * Nach unvollständig übersetzten Datensätzen filtern
     */
    public function whereIncompleteTranslations(array $fields, array $requiredLanguages): self
    {
        $conditions = [];
        
        foreach ($fields as $field) {
            foreach ($requiredLanguages as $clangId) {
                $conditions[] = "JSON_EXTRACT(`{$field}`, '$[*].clang_id') NOT LIKE '%{$clangId}%'";
            }
        }
        
        if (!empty($conditions)) {
            $this->whereRaw('(' . implode(' OR ', $conditions) . ')');
        }
        
        return $this;
    }

    /**
     * Sortierung nach übersetztem Feld in bestimmter Sprache
     */
    public function orderByTranslation(string $field, int $clangId, string $direction = 'ASC'): self
    {
        // Subquery für Sortierung nach übersetztem Wert
        $this->orderByRaw("(SELECT jt.value FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (clang_id INT PATH '$.clang_id', value TEXT PATH '$.value')) AS jt WHERE jt.clang_id = {$clangId} LIMIT 1) {$direction}");
        return $this;
    }

    /**
     * Sortierung nach übersetztem Feld in aktueller Sprache
     */
    public function orderByCurrentLanguage(string $field, string $direction = 'ASC'): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->orderByTranslation($field, $currentLang->getId(), $direction);
    }

    /**
     * Gruppierung nach Übersetzungsstatus
     */
    public function groupByTranslationStatus(string $field, int $clangId): self
    {
        $this->groupByRaw("CASE WHEN JSON_EXTRACT(`{$field}`, '$[*].clang_id') LIKE '%{$clangId}%' THEN 'translated' ELSE 'untranslated' END");
        return $this;
    }

    /**
     * Zählung der verfügbaren Übersetzungen pro Datensatz
     */
    public function selectTranslationCount(string $field, string $alias = 'translation_count'): self
    {
        $this->selectRaw("JSON_LENGTH(`{$field}`) as {$alias}");
        return $this;
    }

    /**
     * Auswahl der Übersetzung für bestimmte Sprache
     */
    public function selectTranslation(string $field, int $clangId, string $alias = null): self
    {
        $alias = $alias ?: $field . '_' . $clangId;
        $this->selectRaw("(SELECT jt.value FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (clang_id INT PATH '$.clang_id', value TEXT PATH '$.value')) AS jt WHERE jt.clang_id = {$clangId} LIMIT 1) as {$alias}");
        return $this;
    }

    /**
     * Auswahl der Übersetzung für aktuelle Sprache
     */
    public function selectCurrentLanguage(string $field, string $alias = null): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        $alias = $alias ?: $field . '_current';
        return $this->selectTranslation($field, $currentLang->getId(), $alias);
    }

    /**
     * Erweiterte Suche über mehrere Sprachen
     */
    public function searchInAllLanguages(string $field, string $searchTerm): self
    {
        $languages = LangHelper::getActiveLanguages();
        $conditions = [];
        
        foreach ($languages as $lang) {
            $conditions[] = "EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (clang_id INT PATH '$.clang_id', value TEXT PATH '$.value')) AS jt WHERE jt.clang_id = {$lang->getId()} AND jt.value LIKE ?)";
        }
        
        if (!empty($conditions)) {
            $params = array_fill(0, count($conditions), "%{$searchTerm}%");
            $this->whereRaw('(' . implode(' OR ', $conditions) . ')', $params);
        }
        
        return $this;
    }

    /**
     * Fallback-Auswahl: Bevorzugte Sprache oder erste verfügbare Übersetzung
     */
    public function selectWithFallback(string $field, int $preferredClangId, array $fallbackClangIds = [], string $alias = null): self
    {
        $alias = $alias ?: $field . '_fallback';
        
        $cases = ["WHEN JSON_EXTRACT(`{$field}`, '$[*].clang_id') LIKE '%{$preferredClangId}%' THEN (SELECT jt.value FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (clang_id INT PATH '$.clang_id', value TEXT PATH '$.value')) AS jt WHERE jt.clang_id = {$preferredClangId} LIMIT 1)"];
        
        foreach ($fallbackClangIds as $clangId) {
            $cases[] = "WHEN JSON_EXTRACT(`{$field}`, '$[*].clang_id') LIKE '%{$clangId}%' THEN (SELECT jt.value FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (clang_id INT PATH '$.clang_id', value TEXT PATH '$.value')) AS jt WHERE jt.clang_id = {$clangId} LIMIT 1)";
        }
        
        $cases[] = "ELSE (SELECT jt.value FROM JSON_TABLE(`{$field}`, '$[*]' COLUMNS (value TEXT PATH '$.value')) AS jt LIMIT 1)";
        
        $this->selectRaw("CASE " . implode(' ', $cases) . " END as {$alias}");
        
        return $this;
    }
}