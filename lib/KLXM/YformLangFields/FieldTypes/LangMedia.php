<?php

use KLXM\YformLangFields\LangHelper;

class rex_yform_value_lang_media extends rex_yform_value_abstract
{
    protected function parseValue($value): array
    {
        return LangHelper::normalizeLanguageData($value);
    }

    protected function formatValueForSave($data): string
    {
        // Media-Felder enthalten Dateinamen als Werte
        $normalized = [];
        
        foreach ($data as $item) {
            if (isset($item['clang_id']) && isset($item['value']) && !empty(trim($item['value']))) {
                $normalized[] = [
                    'clang_id' => (int) $item['clang_id'],
                    'value' => trim($item['value'])
                ];
            }
        }
        
        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    protected function getFieldValue()
    {
        $value = $this->getValue();
        
        if (is_string($value) && !empty($value)) {
            return $this->parseValue($value);
        }
        
        // Werte aus POST-Request verarbeiten
        $postValue = $_POST['FORM'][$this->params['form_name']][$this->getName()] ?? null;
        if (is_array($postValue)) {
            $formattedData = [];
            
            foreach ($postValue as $item) {
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
            'value.lang_media.tpl.php'
        ], [
            'field' => $this,
            'value' => $value,
            'field_type' => 'media',
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
        
        $this->params['value_pool']['email'][$this->getName()] = $formattedValue;
        $this->params['value_pool']['sql'][$this->getName()] = $formattedValue;
    }

    public function getDescription(): string
    {
        return 'Mehrsprachiges Medienfeld mit JSON-Speicherung';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'lang_media',
            'values' => [
                'name' => ['type' => 'name', 'label' => 'Name'],
                'label' => ['type' => 'text', 'label' => 'Bezeichnung'],
                'notice' => ['type' => 'text', 'label' => 'Hinweistext'],
                'required' => ['type' => 'boolean', 'label' => 'Pflichtfeld'],
                'types' => ['type' => 'text', 'label' => 'Erlaubte Dateitypen (z.B. jpg,png,gif)'],
                'category' => ['type' => 'text', 'label' => 'Medienkategorie'],
                'preview' => ['type' => 'boolean', 'label' => 'Vorschau anzeigen'],
            ],
            'description' => $this->getDescription(),
            'formbuilder' => true,
            'famous' => false,
            'db_type' => ['text'],
        ];
    }


}