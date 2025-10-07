<?php

use KLXM\YformLangFields\LangHelper;

/**
 * Validator für mehrsprachige Felder
 */
class rex_yform_validate_lang_required extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $fieldName = $this->getElement(2);
        $requiredLanguages = $this->getElement(3, ''); // Kommaseparierte Liste von Clang-IDs
        $message = $this->getElement(4, \rex_i18n::msg('yform_lang_fields_validation_required'));

        if (empty($fieldName)) {
            return;
        }

        // Feldwert abrufen
        $fieldValue = $this->params['value_pool']['email'][$fieldName] ?? '';
        
        if (empty($fieldValue)) {
            $fieldValue = $this->getValueForField($fieldName);
        }

        $translations = LangHelper::normalizeLanguageData($fieldValue);
        
        // Wenn Pflichtsprachen definiert sind, diese prüfen
        if (!empty($requiredLanguages)) {
            $requiredLangIds = array_map('intval', array_filter(explode(',', $requiredLanguages)));
            
            foreach ($requiredLangIds as $clangId) {
                $hasTranslation = false;
                
                foreach ($translations as $translation) {
                    if ($translation['clang_id'] === $clangId && !empty(trim($translation['value']))) {
                        $hasTranslation = true;
                        break;
                    }
                }
                
                if (!$hasTranslation) {
                    $clang = \rex_clang::get($clangId);
                    $langName = $clang ? $clang->getName() : "ID $clangId";
                    $this->params['warning'][$this->getId()] = str_replace('{language}', $langName, $message);
                    $this->params['warning_messages'][$this->getId()] = $this->params['warning'][$this->getId()];
                    return;
                }
            }
        } else {
            // Mindestens eine Übersetzung erforderlich
            $hasAnyTranslation = false;
            
            foreach ($translations as $translation) {
                if (!empty(trim($translation['value']))) {
                    $hasAnyTranslation = true;
                    break;
                }
            }
            
            if (!$hasAnyTranslation) {
                $this->params['warning'][$this->getId()] = $message;
                $this->params['warning_messages'][$this->getId()] = $message;
            }
        }
    }

    protected function getValueForField($fieldName)
    {
        // Aus POST-Daten verarbeiten
        $postValue = $_POST['FORM'][$this->params['form_name']][$fieldName] ?? null;
        
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
            
            return json_encode($formattedData, JSON_UNESCAPED_UNICODE);
        }
        
        return '';
    }

    public function getDescription(): string
    {
        return 'Validiert mehrsprachige Felder auf erforderliche Übersetzungen';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'validate',
            'name' => 'lang_required',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => 'Feldname', 'notice' => 'Name des mehrsprachigen Feldes'],
                'required_languages' => ['type' => 'text', 'label' => 'Pflichtsprachen', 'notice' => 'Kommaseparierte Liste von Clang-IDs (leer = mindestens eine Sprache)'],
                'message' => ['type' => 'text', 'label' => 'Fehlermeldung', 'notice' => 'Verwenden Sie {language} als Platzhalter für den Sprachnamen'],
            ],
            'description' => $this->getDescription(),
            'famous' => false,
        ];
    }
}

// Klasse ist bereits als rex_yform_validate_lang_required definiert