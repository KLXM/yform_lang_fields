<?php

use KLXM\YformLangFields\LangHelper;

class rex_yform_value_lang_textarea extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
        // Wert normalisieren - entweder JSON-String oder leer
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        // Default-Wert setzen wenn leer und noch nicht gesendet
        if ('' === $this->getValue() && !$this->params['send']) {
            $default = $this->getElement('default');
            $this->setValue(is_string($default) ? $default : '');
        }

        // Werte für E-Mail und Datenbank setzen (vor Template-Ausgabe)
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        // Template-Ausgabe nur wenn nötig
        if ($this->needsOutput() && $this->isViewable()) {
            $templateParams = $this->getTemplateParams();

            if (!$this->isEditable()) {
                $attrElement = $this->getElement('attributes');
                $attributes = empty($attrElement) || !is_string($attrElement) ? [] : (json_decode($attrElement, true) ?: []);
                $attributes['readonly'] = 'readonly';
                $this->setElement('attributes', json_encode($attributes));
            }

            $this->params['form_output'][$this->getId()] = $this->parse([
                'value.lang_field.tpl.php',
                'value.lang_textarea.tpl.php',
            ], $templateParams);
        }

        // POST-Daten verarbeiten
        if (isset($_POST['FORM'][$this->params['form_name']]['send'])) {
            $formName = $this->params['form_name'];
            $fieldId = $this->getId();

            if (isset($_POST['FORM'][$formName][$fieldId]) && is_array($_POST['FORM'][$formName][$fieldId])) {
                $postValue = $_POST['FORM'][$formName][$fieldId];
                $jsonValue = $this->formatValueForSave($postValue);
                $this->setValue($jsonValue);

                $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

                if ($this->saveInDb()) {
                    $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
                }
            }
        }
    }

    /**
     * @param mixed $data
     */
    protected function formatValueForSave($data): string
    {
        if (!is_array($data)) {
            return '';
        }

        $normalized = [];

        foreach ($data as $item) {
            if (is_array($item) && isset($item['clang_id'], $item['value']) && is_scalar($item['value']) && '' !== trim((string) $item['value'])) {
                $normalized[] = [
                    'clang_id' => (int) $item['clang_id'],
                    'value' => trim((string) $item['value']),
                ];
            }
        }

        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
        return false === $json ? '' : $json;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTemplateParams(): array
    {
        $value = $this->parseValue($this->getValue());

        // Erste Sprache automatisch hinzufügen wenn noch keine Werte vorhanden
        $firstLang = null;
        if (empty($value)) {
            $firstLang = rex_clang::getCurrent();
            $value = [
                [
                    'clang_id' => $firstLang->getId(),
                    'value' => '',
                ],
            ];
        }

        $attributes = $this->getElement('attributes');
        $attributes = is_string($attributes) ? $attributes : '';

        $parsedAttributes = $this->normalizeAttributeAliases($this->parseFieldAttributes($attributes));

        if (!isset($parsedAttributes['rows'])) { // @phpstan-ignore-line
            $parsedAttributes['rows'] = '5';
        }

        $editorType = $this->detectEditorType($parsedAttributes);

        return [
            'field' => $this,
            'value' => $value,
            'field_type' => 'textarea',
            'field_name' => $this->getFieldName(),
            'field_id' => $this->getFieldId(),
            'label' => $this->getLabel(),
            'attributes' => $this->getElement('attributes'),
            'parsed_attributes' => $parsedAttributes,
            'notice' => $this->getElement('notice'),
            'required' => $this->getElement('required'),
            'available_languages' => LangHelper::getAvailableLanguages($value),
            'all_languages' => LangHelper::getActiveLanguages(),
            'first_language_id' => null !== $firstLang ? $firstLang->getId() : 1,
            'editor_type' => $editorType,
        ];
    }

    /**
     * @return array<string, scalar>
     */
    private function parseFieldAttributes(string $attributes): array
    {
        $attributes = trim($attributes);
        if ('' === $attributes) {
            return [];
        }

        if ('{' === $attributes[0]) {
            $decoded = json_decode($attributes, true);
            if (is_array($decoded)) {
                $parsed = [];
                foreach ($decoded as $key => $value) {
                    if (!is_string($key) || '' === $key || !is_scalar($value)) {
                        continue;
                    }
                    $parsed[$key] = $value;
                }

                return $parsed;
            }
        }

        $parsed = [];
        if (preg_match_all('/([^=\s]+)="([^"]*)"|([^=\s]+)=([^\s]+)/', $attributes, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $quotedKey = $match[1] ?? '';
                if ('' !== $quotedKey) {
                    $parsed[$quotedKey] = $match[2] ?? '';
                    continue;
                }

                $bareKey = $match[3] ?? '';
                if ('' !== $bareKey) {
                    $parsed[$bareKey] = $match[4] ?? '';
                }
            }
        }

        return $parsed;
    }

    /**
     * @param array<string, scalar> $attributes
     *
     * @return array<string, scalar>
     */
    private function normalizeAttributeAliases(array $attributes): array
    {
        if (isset($attributes['profile']) && !isset($attributes['data-profile'])) {
            $attributes['data-profile'] = $attributes['profile'];
            unset($attributes['profile']);
        }

        if (isset($attributes['lang']) && !isset($attributes['data-lang'])) {
            $attributes['data-lang'] = $attributes['lang'];
            unset($attributes['lang']);
        }

        return $attributes;
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function detectEditorType(array $attributes): string
    {
        $classValue = isset($attributes['class']) // @phpstan-ignore-line
            ? strtolower((string) $attributes['class'])
            : '';

        if (false !== strpos($classValue, 'cke5-editor') || false !== strpos($classValue, ' cke5') || 0 === strpos($classValue, 'cke5')) {
            return 'cke5';
        }

        if (false !== strpos($classValue, 'tiny-editor') || false !== strpos($classValue, 'tinymceeditor')) {
            return 'tinymce';
        }

        return 'none';
    }

    /**
     * @param mixed $value
     *
     * @return list<array{clang_id: int, value: mixed}>
     */
    protected function parseValue($value): array
    {
        if (!is_string($value) || '' === $value) {
            return [];
        }

        return LangHelper::normalizeLanguageData($value);
    }

    public function getDescription(): string
    {
        return 'lang_textarea|name|label|[no_db]|[attributes]|[notice]';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'lang_textarea',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'use_writeassist' => ['type' => 'boolean', 'label' => 'WriteAssist KI-Übersetzung', 'default' => 0],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_lang_textarea_description'),
            'db_type' => ['text', 'mediumtext'],
            'famous' => false,
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function getListValue($params): string
    {
        $value = (string) ($params['subject'] ?? '');
        if ('' === $value) {
            return '<span>-</span>';
        }

        $parsed = LangHelper::normalizeLanguageData($value);
        return LangHelper::buildListPopover($parsed, 'text', 0);
    }
}
