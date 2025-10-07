<?php

use KLXM\YformLangFields\LangHelper;

class rex_yform_value_lang_text extends rex_yform_value_abstract
{
    public function enterObject()
    {
        // Wert normalisieren - entweder JSON-String oder leer
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        // Default-Wert setzen wenn leer und noch nicht gesendet
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
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
                $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
                $attributes['readonly'] = 'readonly';
                $this->setElement('attributes', json_encode($attributes));
            }
            
            $this->params['form_output'][$this->getId()] = $this->parse([
                'value.lang_field.tpl.php', 
                'value.lang_text.tpl.php'
            ], $templateParams);
        }

        // POST-Daten verarbeiten - prüfe auf 'send' in POST und korrekte Struktur
        if (isset($_POST['FORM']) && isset($_POST['FORM'][$this->params['form_name']]['send'])) {
            $formName = $this->params['form_name'];
            $fieldId = $this->getId();
            
            // Debug: Alle POST-Daten loggen
            if (rex::isBackend()) {
                error_log('YForm Lang Debug POST Processing: ' . json_encode([
                    'form_name' => $formName,
                    'field_id' => $fieldId,
                    'field_name' => $this->getName(),
                    'post_structure' => isset($_POST['FORM'][$formName][$fieldId]) ? 'EXISTS' : 'MISSING',
                    'post_data' => isset($_POST['FORM'][$formName][$fieldId]) ? $_POST['FORM'][$formName][$fieldId] : 'NO DATA'
                ]));
            }
            
            if (isset($_POST['FORM'][$formName][$fieldId]) && is_array($_POST['FORM'][$formName][$fieldId])) {
                $postValue = $_POST['FORM'][$formName][$fieldId];
                $jsonValue = $this->formatValueForSave($postValue);
                $this->setValue($jsonValue);
                
                // Werte für E-Mail und Datenbank erneut setzen nach POST-Verarbeitung
                $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
                
                if ($this->saveInDb()) {
                    $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
                }
                
                error_log('YForm Lang Field saved: ' . $this->getName() . ' = ' . $this->getValue());
            }
        }
    }

    protected function formatValueForSave($data): string
    {
        if (!is_array($data)) {
            return '';
        }
        
        $normalized = [];
        
        foreach ($data as $item) {
            if (is_array($item) && isset($item['clang_id']) && isset($item['value']) && !empty(trim($item['value']))) {
                $normalized[] = [
                    'clang_id' => (int) $item['clang_id'],
                    'value' => trim($item['value'])
                ];
            }
        }
        
        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    protected function getTemplateParams(): array
    {
        $value = $this->parseValue($this->getValue());
        
        // Sicherstellen, dass $value immer ein Array ist
        if (!is_array($value)) {
            $value = [];
        }
        
        // Erste Sprache automatisch hinzufügen wenn noch keine Werte vorhanden
        if (empty($value)) {
            $firstLang = rex_clang::getCurrent() ?: rex_clang::getAll()[0];
            if ($firstLang) {
                $value = [
                    [
                        'clang_id' => $firstLang->getId(),
                        'value' => ''
                    ]
                ];
            }
        }
        
        return [
            'field' => $this,
            'value' => $value,
            'field_type' => 'text',
            'field_name' => $this->getFieldName(),
            'field_id' => $this->getFieldId(),
            'label' => $this->getLabel(),
            'attributes' => $this->getElement('attributes'),
            'notice' => $this->getElement('notice'),
            'required' => $this->getElement('required'),
            'available_languages' => LangHelper::getAvailableLanguages($value),
            'all_languages' => LangHelper::getActiveLanguages(),
            'first_language_id' => isset($firstLang) ? $firstLang->getId() : 1
        ];
    }

    protected function parseValue($value): array
    {
        if (empty($value) || !is_string($value)) {
            return [];
        }
        
        return LangHelper::normalizeLanguageData($value);
    }

    public function getDescription(): string
    {
        return 'lang_text|name|label|[no_db]|[attributes]|[notice]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'lang_text',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_lang_text_description'),
            'db_type' => ['text'],
            'famous' => false,
        ];
    }

    public static function getListValue($params)
    {
        $value = (string) $params['subject'];
        if (empty($value)) {
            return '<span>-</span>';
        }
        
        $parsed = LangHelper::normalizeLanguageData($value);
        if (empty($parsed)) {
            return '<span>-</span>';
        }
        
        $displayValues = [];
        foreach ($parsed as $item) {
            if (!empty($item['value'])) {
                $clang = rex_clang::get($item['clang_id']);
                $langCode = $clang ? $clang->getCode() : $item['clang_id'];
                $displayValues[] = $langCode . ': ' . rex_escape(mb_substr($item['value'], 0, 50));
            }
        }
        
        return '<span>' . implode(' | ', $displayValues) . '</span>';
    }
}