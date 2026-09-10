<?php

namespace KLXM\YformLangFields;

use rex_yform_manager_dataset;
use rex_yform_manager_query;

/**
 * Erweiterte Query-Klasse für mehrsprachige YOrm-Abfragen.
 *
 * @extends rex_yform_manager_query<rex_yform_manager_dataset>
 */
class LangQuery extends rex_yform_manager_query
{
    /** Fortlaufender Zähler für eindeutige Bind-Platzhalternamen je Instanz. */
    private int $placeholderCounter = 0;

    /**
     * Nach Übersetzung in bestimmter Sprache filtern.
     */
    public function whereTranslationExists(string $field, int $clangId): self
    {
        $placeholder = ':lang_exists_' . $this->placeholderCounter++;
        $this->whereRaw(
            "EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id')) AS jt WHERE jt.clang_id = {$placeholder})",
            [$placeholder => $clangId],
        );
        return $this;
    }

    /**
     * Nach nicht-leerer Übersetzung in bestimmter Sprache filtern.
     */
    public function whereTranslationNotEmpty(string $field, int $clangId): self
    {
        $placeholder = ':lang_not_empty_' . $this->placeholderCounter++;
        $this->whereRaw(
            "EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$placeholder} AND jt.value <> '')",
            [$placeholder => $clangId],
        );
        return $this;
    }

    /**
     * Nach Übersetzung in aktueller Sprache filtern.
     */
    public function whereCurrentLanguageExists(string $field): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->whereTranslationExists($field, $currentLang->getId());
    }

    /**
     * Nach Text in bestimmter Sprache suchen.
     */
    public function whereTranslationLike(string $field, int $clangId, string $value): self
    {
        $placeholder = ':lang_like_' . $this->placeholderCounter++;
        $this->whereRaw(
            "EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$clangId} AND jt.value LIKE {$placeholder})",
            [$placeholder => '%' . $value . '%'],
        );
        return $this;
    }

    /**
     * Nach Text in aktueller Sprache suchen.
     */
    public function whereCurrentLanguageLike(string $field, string $value): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->whereTranslationLike($field, $currentLang->getId(), $value);
    }

    /**
     * Nach vollständig übersetzten Datensätzen filtern.
     *
     * @param list<string> $fields
     * @param list<int>    $requiredLanguages
     */
    public function whereFullyTranslated(array $fields, array $requiredLanguages): self
    {
        $conditions = [];

        foreach ($fields as $field) {
            foreach ($requiredLanguages as $clangId) {
                $conditions[] = "EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id')) AS jt WHERE jt.clang_id = {$clangId})";
            }
        }

        if (!empty($conditions)) {
            $this->whereRaw('(' . implode(' AND ', $conditions) . ')');
        }

        return $this;
    }

    /**
     * Nach unvollständig übersetzten Datensätzen filtern.
     *
     * @param list<string> $fields
     * @param list<int>    $requiredLanguages
     */
    public function whereIncompleteTranslations(array $fields, array $requiredLanguages): self
    {
        $conditions = [];

        foreach ($fields as $field) {
            foreach ($requiredLanguages as $clangId) {
                $conditions[] = "NOT EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id')) AS jt WHERE jt.clang_id = {$clangId})";
            }
        }

        if (!empty($conditions)) {
            $this->whereRaw('(' . implode(' OR ', $conditions) . ')');
        }

        return $this;
    }

    /**
     * Sortierung nach übersetztem Feld in bestimmter Sprache.
     */
    public function orderByTranslation(string $field, int $clangId, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderByRaw("(SELECT jt.value FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$clangId} LIMIT 1)", $direction);
        return $this;
    }

    /**
     * Sortierung nach übersetztem Feld in aktueller Sprache.
     */
    public function orderByCurrentLanguage(string $field, string $direction = 'ASC'): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        return $this->orderByTranslation($field, $currentLang->getId(), $direction);
    }

    /**
     * Gruppierung nach Übersetzungsstatus.
     */
    public function groupByTranslationStatus(string $field, int $clangId): self
    {
        $this->groupByRaw("CASE WHEN EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id')) AS jt WHERE jt.clang_id = {$clangId}) THEN 'translated' ELSE 'untranslated' END");
        return $this;
    }

    /**
     * Zählung der verfügbaren Übersetzungen pro Datensatz.
     */
    public function selectTranslationCount(string $field, string $alias = 'translation_count'): self
    {
        $this->selectRaw("JSON_LENGTH(`{$field}`) as {$alias}");
        return $this;
    }

    /**
     * Auswahl der Übersetzung für bestimmte Sprache.
     */
    public function selectTranslation(string $field, int $clangId, ?string $alias = null): self
    {
        $alias = $alias ?: $field . '_' . $clangId;
        $this->selectRaw("(SELECT jt.value FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$clangId} LIMIT 1) as {$alias}");
        return $this;
    }

    /**
     * Auswahl der Übersetzung für aktuelle Sprache.
     */
    public function selectCurrentLanguage(string $field, ?string $alias = null): self
    {
        $currentLang = LangHelper::getCurrentLanguage();
        $alias = $alias ?: $field . '_current';
        return $this->selectTranslation($field, $currentLang->getId(), $alias);
    }

    /**
     * Erweiterte Suche über mehrere Sprachen.
     */
    public function searchInAllLanguages(string $field, string $searchTerm): self
    {
        $languages = LangHelper::getActiveLanguages();
        $conditions = [];
        $params = [];

        $i = 0;
        foreach ($languages as $lang) {
            $placeholder = ':lang_search_' . $i;
            $conditions[] = "EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$lang->getId()} AND jt.value LIKE {$placeholder})";
            $params[$placeholder] = '%' . $searchTerm . '%';
            ++$i;
        }

        if (!empty($conditions)) {
            $this->whereRaw('(' . implode(' OR ', $conditions) . ')', $params);
        }

        return $this;
    }

    /**
     * Fallback-Auswahl: Bevorzugte Sprache oder erste verfügbare Übersetzung.
     *
     * @param list<int> $fallbackClangIds
     */
    public function selectWithFallback(string $field, int $preferredClangId, array $fallbackClangIds = [], ?string $alias = null): self
    {
        $alias = $alias ?: $field . '_fallback';

        $cases = ["WHEN EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id')) AS jt WHERE jt.clang_id = {$preferredClangId}) THEN (SELECT jt.value FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$preferredClangId} LIMIT 1)"];

        foreach ($fallbackClangIds as $clangId) {
            $cases[] = "WHEN EXISTS (SELECT 1 FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id')) AS jt WHERE jt.clang_id = {$clangId}) THEN (SELECT jt.value FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (clang_id INT PATH '\$.clang_id', value TEXT PATH '\$.value')) AS jt WHERE jt.clang_id = {$clangId} LIMIT 1)";
        }

        $cases[] = "ELSE (SELECT jt.value FROM JSON_TABLE(`{$field}`, '\$[*]' COLUMNS (value TEXT PATH '\$.value')) AS jt LIMIT 1)";

        $this->selectRaw('CASE ' . implode(' ', $cases) . ' END as ' . $alias);

        return $this;
    }
}
