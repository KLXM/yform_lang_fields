<?php

use KLXM\YformLangFields\LangHelper;

class rex_yform_value_lang_text extends rex_yform_value_abstract
{
    protected function parseValue($value): array
    {
        return LangHelper::normalizeLanguageData($value);
    }

    protected function formatValueForSave($data): string
    {
        $normalized = LangHelper::normalizeLanguageData($data);
        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    protected function getFieldValue()
    {
        $value = $this->getValue();
        
        if (is_string($value) && !empty($value)) {
            return $this->parseValue($value);
        }
        
        // Werte aus Request verarbeiten (YForm-kompatibel)
        $fieldName = $this->getName();
        $requestValue = $this->params['request'][$fieldName] ?? null;
        
        if (is_array($requestValue)) {
            $formattedData = [];
            
            foreach ($requestValue as $item) {
                if (isset($item['clang_id']) && isset($item['value']) && !empty(trim($item['value']))) {
                    $formattedData[] = [
                        'clang_id' => (int) $item['clang_id'],
                        'value' => $item['value']
                    ];
                }
            }
            
            return $formattedData;
        }

        return [];
    }

    public function enterObject()
    {
        $value = $this->getFieldValue();
        
        $this->params['form_output'][$this->getId()] = $this->parse([
            'value.lang_field.tpl.php', 
            'value.lang_text.tpl.php'
        ], [
            'field' => $this,
            'value' => $value,
            'field_type' => 'text',
            'field_name' => $this->getFieldName(),
            'field_id' => $this->getFieldId(),
            'label' => $this->getLabel(),
            'attributes' => '',
            'notice' => $this->getElement('notice'),
            'required' => $this->getElement('required'),
            'available_languages' => LangHelper::getAvailableLanguages($value),
            'all_languages' => LangHelper::getActiveLanguages()
        ]);
    }

    public function postFormAction(): void
    {
        $value = $this->getFieldValue();
        $formattedValue = $this->formatValueForSave($value);
        
        // Für leere Daten null statt leeres JSON Array verwenden
        if (empty($value) || $formattedValue === '[]') {
            $formattedValue = null;
        }
        
        $this->params['value_pool']['email'][$this->getName()] = $formattedValue;
        $this->params['value_pool']['sql'][$this->getName()] = $formattedValue;
        
        if (isset($this->params['main_table']) && !empty($this->params['main_table'])) {
            $this->params['value_pool']['sql'][$this->getName()] = $formattedValue;
        }
    }

    public function getDescription(): string
    {
        return 'Mehrsprachiges Textfeld mit JSON-Speicherung';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'lang_text',
            'values' => [
                'name' => ['type' => 'name', 'label' => 'Name'],
                'label' => ['type' => 'text', 'label' => 'Bezeichnung'],
                'notice' => ['type' => 'text', 'label' => 'Hinweistext'],
                'required' => ['type' => 'boolean', 'label' => 'Pflichtfeld'],
                'placeholder' => ['type' => 'text', 'label' => 'Platzhalter'],
                'maxlength' => ['type' => 'text', 'label' => 'Maximale Zeichenanzahl'],
            ],
            'description' => $this->getDescription(),
            'formbuilder' => true,
            'famous' => false,
            'db_type' => ['text'],
        ];
    }


}