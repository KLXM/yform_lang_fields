<?php
/**
 * @var rex_yform_value_abstract $this
 * @var array $value
 * @var string $field_type
 * @var string $field_name
 * @var string $field_id
 * @var string $label
 * @var string $attributes
 * @var string $notice
 * @var bool $required
 * @var array $available_languages
 * @var array $all_languages
 */

use KLXM\YformLangFields\LangHelper;

// Sicherstellen, dass $value immer ein Array ist
if (!is_array($value)) {
    $value = [];
}

$fieldClass = 'yform-lang-field yform-lang-' . $field_type;
if ($required) {
    $fieldClass .= ' required';
}
?>

<div class="form-group <?= $fieldClass ?>" id="<?= $this->getHTMLId() ?>">
    
    <label class="control-label">
        <?= rex_escape($label) ?>
        <?php if ($required): ?>
            <span class="text-danger">*</span>
        <?php endif; ?>
    </label>
    
    <?php 
    $description = $this->getElement('description', '');
    if ($description): 
    ?>
        <p class="help-block small text-muted"><?= nl2br(rex_escape($description)) ?></p>
    <?php endif; ?>
    
    <?php if ($notice): ?>
        <p class="help-block"><?= rex_escape($notice) ?></p>
    <?php endif; ?>

    <!-- Bestehende Übersetzungen -->
    <div class="lang-fields-container" data-field-name="<?= rex_escape($field_name) ?>">
        <?php foreach ($value as $index => $item): ?>
            <?php 
            $clangId = (int) $item['clang_id'];
            $clang = rex_clang::get($clangId);
            if (!$clang) continue;
            
            $inputName = $field_name . '[' . $index . ']';
            $inputId = $field_id . '_' . $index;
            ?>
            <div class="lang-field-item panel panel-default" data-clang-id="<?= $clangId ?>" style="margin-bottom: 15px;">
                <div class="panel-heading" style="position: relative; padding-right: 50px;">
                    <i class="fa-solid fa-flag" style="margin-right: 8px; color: #777;"></i>
                    <strong><?= rex_escape($clang->getName()) ?></strong>
                    <small class="text-muted">(<?= rex_escape($clang->getCode()) ?>)</small>
                    
                    <!-- Delete Button oben rechts -->
                    <?php if ($index > 0 || (is_array($value) && count($value) > 1)): ?>
                        <button type="button" 
                                class="btn btn-danger btn-xs btn-remove-lang-field"
                                style="position: absolute; top: 8px; right: 10px;"
                                title="Übersetzung entfernen">
                            <i class="fa fa-trash"></i>
                        </button>
                    <?php else: ?>
                        <span class="text-muted" 
                              style="position: absolute; top: 10px; right: 10px; font-size: 11px;">
                            <i class="fa fa-lock"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if ($field_type === 'text'): ?>
                        <input type="text" 
                               name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][value]" 
                               id="<?= $inputId ?>"
                               class="form-control lang-input" 
                               value="<?= rex_escape($item['value']) ?>"
                               data-clang-id="<?= $clangId ?>"
                               placeholder="<?= rex_escape($this->getLabel()) ?>" />
                        <input type="hidden" name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][clang_id]" value="<?= $clangId ?>" />
                    
                    <?php elseif ($field_type === 'textarea'): ?>
                        <?php 
                        // Template-Parameter verwenden (wurden in getTemplateParams() gesetzt)
                        $rows = isset($parsed_attributes['rows']) ? $parsed_attributes['rows'] : 5;
                        $useEditor = isset($use_editor) ? $use_editor : false;
                        $textareaClass = 'form-control lang-textarea';
                        
                        // Parse attributes JSON if present
                        $attributesJson = $this->getElement('attributes');
                        $parsedAttributes = [];
                        if (!empty($attributesJson)) {
                            $decoded = json_decode($attributesJson, true);
                            if (is_array($decoded)) {
                                $parsedAttributes = $decoded;
                                // Merge classes
                                if (isset($parsedAttributes['class'])) {
                                    $textareaClass = $parsedAttributes['class'];
                                    unset($parsedAttributes['class']);
                                }
                            }
                        }
                        
                        // Check if CKE5 is requested via class or editor flag
                        if ($useEditor || (isset($parsed_attributes['class']) && strpos($parsed_attributes['class'], 'cke5') !== false)) {
                            if (strpos($textareaClass, 'cke5-editor') === false) {
                                $textareaClass .= ' cke5-editor';
                            }
                            // Für CKE5 zusätzliche Data-Attribute
                            if (!isset($parsedAttributes['data-lang'])) {
                                $parsedAttributes['data-lang'] = $clang->getCode();
                            }
                        }
                        
                        // Build attribute string
                        $attributeString = '';
                        foreach ($parsedAttributes as $attr => $value) {
                            $attributeString .= ' ' . rex_escape($attr) . '="' . rex_escape($value) . '"';
                        }
                        ?>
                        <textarea name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][value]" 
                                  id="<?= $inputId ?>"
                                  class="<?= $textareaClass ?>" 
                                  rows="<?= $rows ?>"
                                  data-clang-id="<?= $clangId ?>"
                                  placeholder="<?= rex_escape($this->getLabel()) ?>"<?= $attributeString ?>><?= rex_escape($item['value']) ?></textarea>
                        <input type="hidden" name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][clang_id]" value="<?= $clangId ?>" />
                    
                    <?php elseif ($field_type === 'media'): ?>
                        <?php 
                        $types = $this->getElement('types', '');
                        $category = $this->getElement('category', '');
                        $preview = $this->getElement('preview', true);
                        $withText = $this->getElement('with_text', false);
                        $textLabel = $this->getElement('text_label', 'Beschreibung');
                        
                        // Eindeutige numerische ID für REDAXO Media-Widget
                        static $widgetCounter = 0;
                        $widgetCounter++;
                        $widgetId = $widgetCounter;
                        
                        // Parameter für openREXMedia zusammenbauen
                        $mediaParams = '';
                        if ($category) {
                            $mediaParams .= '&rex_file_category=' . (int) $category;
                        }
                        if ($types) {
                            $mediaParams .= '&args[types]=' . urlencode($types);
                        }
                        
                        // Text-Wert extrahieren (falls vorhanden)
                        $mediaValue = '';
                        $textValue = '';
                        if (is_array($item['value'])) {
                            $mediaValue = isset($item['value']['media']) ? $item['value']['media'] : '';
                            $textValue = isset($item['value']['text']) ? $item['value']['text'] : '';
                        } else {
                            $mediaValue = $item['value'];
                        }
                        ?>
                        
                        <!-- Medienauswahl -->
                        <div class="input-group" style="margin-bottom: <?= $withText ? '10px' : '0' ?>;">
                            <input type="text" 
                                   name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][value]<?= $withText ? '[media]' : '' ?>" 
                                   id="REX_MEDIA_<?= $widgetId ?>"
                                   class="form-control" 
                                   value="<?= rex_escape($mediaValue) ?>"
                                   placeholder="<?= rex_escape($this->getLabel()) ?>"
                                   readonly />
                            <input type="hidden" name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][clang_id]" value="<?= $clangId ?>" />
                            <span class="input-group-btn">
                                <a href="#" class="btn btn-popup" 
                                   onclick="openREXMedia(<?= $widgetId ?><?php if ($mediaParams): ?>, '<?= $mediaParams ?>'<?php endif; ?>); return false;" 
                                   title="Medium auswählen">
                                    <i class="rex-icon rex-icon-open-mediapool"></i>
                                </a>
                                <a href="#" class="btn btn-popup" 
                                   onclick="deleteREXMedia(<?= $widgetId ?>); return false;" 
                                   title="Medium entfernen">
                                    <i class="rex-icon rex-icon-delete-media"></i>
                                </a>
                            </span>
                        </div>
                        
                        <?php if ($withText): ?>
                            <!-- Zusätzliches Textfeld -->
                            <input type="text" 
                                   name="FORM[<?= $this->params['form_name'] ?>][<?= $this->getId() ?>][<?= $index ?>][value][text]" 
                                   class="form-control" 
                                   style="margin-top: 10px;"
                                   value="<?= rex_escape($textValue) ?>"
                                   placeholder="<?= rex_escape($textLabel) ?>" />
                        <?php endif; ?>
                        
                        <?php if ($preview && !empty($mediaValue)): ?>
                            <div class="media-preview" style="margin-top: 10px;">
                                <img src="<?= rex_url::media($mediaValue) ?>" 
                                     alt="<?= rex_escape($mediaValue) ?>" 
                                     style="max-width: 100%; max-height: 150px; border: 1px solid #ddd; border-radius: 3px; padding: 5px;" />
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Neue Übersetzung hinzufügen - Kompakt wie MBlock -->
    <?php 
    // Add-Sektion immer rendern, aber verstecken wenn keine Sprachen verfügbar
    $showAddSection = is_array($available_languages) && count($available_languages) > 0;
    $addSectionStyle = $showAddSection ? '' : ' style="display: none;"';
    ?>
    <div class="lang-field-add-section"<?= $addSectionStyle ?>>
        <div class="panel panel-default" style="margin-top: 10px; margin-bottom: 0;">
            <div class="panel-body" style="padding: 10px;">
                <div class="row">
                    <div class="col-sm-4">
                        <select class="form-control input-sm lang-select-new" data-field-name="<?= rex_escape($field_name) ?>">
                            <option value="">Sprache wählen...</option>
                            <?php if (is_array($available_languages)): ?>
                                <?php foreach ($available_languages as $lang): ?>
                                    <option value="<?= $lang->getId() ?>" 
                                            data-name="<?= rex_escape($lang->getName()) ?>"
                                            data-code="<?= rex_escape($lang->getCode()) ?>">
                                        <?= rex_escape($lang->getName() . ' (' . $lang->getCode() . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-sm-8">
                        <button type="button" 
                                class="btn btn-success btn-sm btn-add-lang-field"
                                data-field-type="<?= $field_type ?>"
                                data-field-name="<?= rex_escape($field_name) ?>"
                                data-field-id="<?= rex_escape($field_id) ?>"
                                data-field-id-value="<?= $this->getId() ?>"
                                data-form-name="<?= rex_escape($this->params['form_name']) ?>"
                                data-attributes=""
                                data-rows="<?= isset($parsed_attributes['rows']) ? $parsed_attributes['rows'] : 5 ?>"
                                data-editor="<?= (isset($use_editor) && $use_editor) || $this->getElement('editor', false) ? '1' : '0' ?>"
                                data-types="<?= rex_escape($this->getElement('types', '')) ?>"
                                data-category="<?= rex_escape($this->getElement('category', '')) ?>"
                                data-preview="<?= $this->getElement('preview', true) ? '1' : '0' ?>"
                                data-with-text="<?= $this->getElement('with_text', false) ? '1' : '0' ?>"
                                data-text-label="<?= rex_escape($this->getElement('text_label', 'Beschreibung')) ?>"
                                data-description="<?= rex_escape($this->getElement('description', '')) ?>"
                                disabled="">
                            <i class="fa fa-plus"></i> Übersetzung hinzufügen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template für neue Felder (hidden) -->
    <div class="lang-field-template" style="display: none;" data-field-type="<?= $field_type ?>">
        <!-- Wird per JavaScript gefüllt -->
    </div>

    <!-- Validation Messages -->
    <?php if (!empty($this->params['warning_messages'])): ?>
        <div class="help-block text-danger">
            <?php foreach ($this->params['warning_messages'] as $message): ?>
                <p><?= rex_escape($message) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>