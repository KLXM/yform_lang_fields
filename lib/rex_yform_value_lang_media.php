<?php

use KLXM\YformLangFields\LangHelper;

class rex_yform_value_lang_media extends rex_yform_value_abstract
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
                'value.lang_media.tpl.php',
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
        $withText = (bool) $this->getElement('with_text');

        foreach ($data as $item) {
            if (!is_array($item) || !isset($item['clang_id'], $item['value'])) {
                continue;
            }

            $clangId = (int) $item['clang_id'];
            $value = $item['value'];

            if ($withText && is_array($value)) {
                $media = isset($value['media']) && is_scalar($value['media']) ? trim((string) $value['media']) : '';
                $text = isset($value['text']) && is_scalar($value['text']) ? trim((string) $value['text']) : '';

                if ('' !== $media) {
                    $normalized[] = [
                        'clang_id' => $clangId,
                        'value' => [
                            'media' => $media,
                            'text' => $text,
                        ],
                    ];
                }
            } else {
                if (is_array($value)) {
                    $media = isset($value['media']) && is_scalar($value['media']) ? trim((string) $value['media']) : '';
                } elseif (is_scalar($value)) {
                    $media = trim((string) $value);
                } else {
                    $media = '';
                }

                if ('' !== $media) {
                    $normalized[] = [
                        'clang_id' => $clangId,
                        'value' => $media,
                    ];
                }
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

        return [
            'field' => $this,
            'value' => $value,
            'field_type' => 'media',
            'field_name' => $this->getFieldName(),
            'field_id' => $this->getFieldId(),
            'label' => $this->getLabel(),
            'attributes' => $this->getElement('attributes'),
            'notice' => $this->getElement('notice'),
            'required' => $this->getElement('required'),
            'available_languages' => LangHelper::getAvailableLanguages($value),
            'all_languages' => LangHelper::getActiveLanguages(),
            'first_language_id' => null !== $firstLang ? $firstLang->getId() : 1,
        ];
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
        return 'lang_media|name|label|[description]|[types]|[category]|[preview]|[with_text]|[text_label]';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'lang_media',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'description' => ['type' => 'text', 'label' => 'Beschreibung/Hilfetext', 'notice' => 'Optional: Hilfetext unter dem Label'],
                'types' => ['type' => 'text', 'label' => 'Erlaubte Dateitypen', 'notice' => 'z.B. jpg,png,gif'],
                'category' => ['type' => 'text', 'label' => 'Medienkategorie', 'notice' => 'ID der Medienpool-Kategorie'],
                'preview' => ['type' => 'boolean', 'label' => 'Vorschau anzeigen', 'default' => 1],
                'with_text' => ['type' => 'boolean', 'label' => 'Zusätzliches Textfeld', 'notice' => 'Für Alt-Text, Bildunterschrift, etc.', 'default' => 0],
                'text_label' => ['type' => 'text', 'label' => 'Label für Textfeld', 'notice' => 'z.B. "Alt-Text", "Bildunterschrift"', 'default' => 'Beschreibung'],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => 'Mehrsprachiges Medienfeld mit optionalem Textfeld',
            'db_type' => ['text'],
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
        if (empty($parsed)) {
            return '<span>-</span>';
        }

        $displayValues = [];
        foreach ($parsed as $item) {
            if (empty($item['value'])) {
                continue;
            }

            $clang = rex_clang::get($item['clang_id']);
            $langCode = $clang ? $clang->getCode() : (string) $item['clang_id'];

            if (is_array($item['value'])) {
                $mediaValue = isset($item['value']['media']) && is_scalar($item['value']['media']) ? (string) $item['value']['media'] : '';
                $displayValues[] = $langCode . ': ' . rex_escape($mediaValue);
            } elseif (is_scalar($item['value'])) {
                $displayValues[] = $langCode . ': ' . rex_escape((string) $item['value']);
            }
        }

        return '<span>' . implode(' | ', $displayValues) . '</span>';
    }
}
