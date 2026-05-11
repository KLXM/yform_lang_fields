<?php
/**
 * @var rex_yform_value_abstract                        $this
 * @var list<array{clang_id: int|string, value: mixed}> $value
 * @var string                                          $field_type
 * @var string                                          $field_name
 * @var string                                          $field_id
 * @var string                                          $label
 * @var string                                          $attributes
 * @var string                                          $notice
 * @var bool                                            $required
 * @var list<rex_clang>                                 $available_languages
 * @var array<int, rex_clang>                           $all_languages
 */

$fieldClass = 'yform-lang-field yform-lang-' . $field_type;
if ($required) {
    $fieldClass .= ' required';
}

/** @var array<string, mixed> $parsed_attributes */
$parsed_attributes = isset($parsed_attributes) && is_array($parsed_attributes) ? $parsed_attributes : [];
$use_editor = isset($use_editor) ? (bool) $use_editor : false;
?>

<div class="form-group <?= $fieldClass ?>" id="<?= $this->getHTMLId() ?>">

    <label class="control-label">
        <?= rex_escape($label) ?>
        <?php if ($required): ?>
            <span class="text-danger">*</span>
        <?php endif; ?>
    </label>

    <?php
    $description = $this->getElement('description');
    $description = is_string($description) ? $description : '';
    if ('' !== $description):
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
            if (!$clang) {
                continue;
            }

            $inputId = $field_id . '_' . $index;
            $itemValue = $item['value'] ?? '';
            ?>
            <div class="lang-field-item panel panel-default" data-clang-id="<?= $clangId ?>" style="margin-bottom: 15px;">
                <div class="panel-heading" style="position: relative; padding-right: 50px;">
                    <i class="fa-solid fa-flag" style="margin-right: 8px; color: #777;"></i>
                    <strong><?= rex_escape($clang->getName()) ?></strong>
                    <small class="text-muted">(<?= rex_escape($clang->getCode()) ?>)</small>

                    <!-- Delete Button oben rechts -->
                    <?php if ($index > 0 || count($value) > 1): ?>
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
                    <?php if ('text' === $field_type): ?>
                        <input type="text"
                               name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][value]"
                               id="<?= rex_escape($inputId) ?>"
                               class="form-control lang-input"
                               value="<?= rex_escape(is_scalar($itemValue) ? (string) $itemValue : '') ?>"
                               data-clang-id="<?= $clangId ?>"
                               placeholder="<?= rex_escape($this->getLabel()) ?>" />
                        <input type="hidden" name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][clang_id]" value="<?= $clangId ?>" />

                    <?php elseif ('textarea' === $field_type): ?>
                        <?php
                        $rows = isset($parsed_attributes['rows']) && is_scalar($parsed_attributes['rows']) ? (string) $parsed_attributes['rows'] : '5';
                        $useEditor = $use_editor;
                        $textareaClass = 'form-control lang-textarea';

                        // Parse attributes JSON if present
                        $attributesJson = $this->getElement('attributes');
                        $parsedAttributes = [];
                        if (is_string($attributesJson) && '' !== $attributesJson) {
                            $decoded = json_decode($attributesJson, true);
                            if (is_array($decoded)) {
                                $parsedAttributes = $decoded;
                                if (isset($parsedAttributes['class']) && is_string($parsedAttributes['class'])) {
                                    $textareaClass = $parsedAttributes['class'];
                                    unset($parsedAttributes['class']);
                                }
                            }
                        }

                        // Check if CKE5 is requested via class or editor flag
                        $hasCke5Class = isset($parsed_attributes['class']) && is_string($parsed_attributes['class']) && false !== strpos($parsed_attributes['class'], 'cke5');
                        if ($useEditor || $hasCke5Class) {
                            if (false === strpos($textareaClass, 'cke5-editor')) {
                                $textareaClass .= ' cke5-editor';
                            }
                            if (!isset($parsedAttributes['data-lang'])) {
                                $parsedAttributes['data-lang'] = $clang->getCode();
                            }
                        }

                        // Build attribute string
                        $attributeString = '';
                        foreach ($parsedAttributes as $attr => $attrValue) {
                            if (!is_scalar($attrValue)) {
                                continue;
                            }
                            $attributeString .= ' ' . rex_escape((string) $attr) . '="' . rex_escape((string) $attrValue) . '"';
                        }
                        ?>
                        <textarea name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][value]"
                                  id="<?= rex_escape($inputId) ?>"
                                  class="<?= rex_escape($textareaClass) ?>"
                                  rows="<?= rex_escape($rows) ?>"
                                  data-clang-id="<?= $clangId ?>"
                                  placeholder="<?= rex_escape($this->getLabel()) ?>"<?= $attributeString ?>><?= rex_escape(is_scalar($itemValue) ? (string) $itemValue : '') ?></textarea>
                        <input type="hidden" name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][clang_id]" value="<?= $clangId ?>" />

                    <?php elseif ('media' === $field_type): ?>
                        <?php
                        $typesElem = $this->getElement('types');
                        $types = is_string($typesElem) ? $typesElem : '';
                        $categoryElem = $this->getElement('category');
                        $category = is_scalar($categoryElem) ? (string) $categoryElem : '';
                        $previewElem = $this->getElement('preview');
                        $preview = false === $previewElem ? true : (bool) $previewElem;
                        $withText = (bool) $this->getElement('with_text');
                        $textLabelElem = $this->getElement('text_label');
                        $textLabel = is_string($textLabelElem) && '' !== $textLabelElem ? $textLabelElem : 'Beschreibung';

                        // Eindeutige numerische ID für REDAXO Media-Widget
                        static $widgetCounter = 0;
                        ++$widgetCounter;
                        $widgetId = $widgetCounter;

                        // Parameter für openREXMedia zusammenbauen
                        $mediaParams = '';
                        if ('' !== $category) {
                            $mediaParams .= '&rex_file_category=' . (int) $category;
                        }
                        if ('' !== $types) {
                            $mediaParams .= '&args[types]=' . urlencode($types);
                        }

                        // Text-Wert extrahieren (falls vorhanden)
                        $mediaValue = '';
                        $textValue = '';
                        if (is_array($itemValue)) {
                            $mediaValue = isset($itemValue['media']) && is_scalar($itemValue['media']) ? (string) $itemValue['media'] : '';
                            $textValue = isset($itemValue['text']) && is_scalar($itemValue['text']) ? (string) $itemValue['text'] : '';
                        } elseif (is_scalar($itemValue)) {
                            $mediaValue = (string) $itemValue;
                        }
                        ?>

                        <!-- Medienauswahl -->
                        <div class="input-group" style="margin-bottom: <?= $withText ? '10px' : '0' ?>;">
                            <input type="text"
                                   name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][value]<?= $withText ? '[media]' : '' ?>"
                                   id="REX_MEDIA_<?= $widgetId ?>"
                                   class="form-control"
                                   value="<?= rex_escape($mediaValue) ?>"
                                   placeholder="<?= rex_escape($this->getLabel()) ?>"
                                   readonly />
                            <input type="hidden" name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][clang_id]" value="<?= $clangId ?>" />
                            <span class="input-group-btn">
                                <a href="#" class="btn btn-popup"
                                   onclick="openREXMedia(<?= $widgetId ?><?php if ('' !== $mediaParams): ?>, '<?= rex_escape($mediaParams) ?>'<?php endif; ?>); return false;"
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
                                   name="FORM[<?= rex_escape($this->params['form_name']) ?>][<?= $this->getId() ?>][<?= (int) $index ?>][value][text]"
                                   class="form-control"
                                   style="margin-top: 10px;"
                                   value="<?= rex_escape($textValue) ?>"
                                   placeholder="<?= rex_escape($textLabel) ?>" />
                        <?php endif; ?>

                        <?php if ($preview && '' !== $mediaValue): ?>
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
    $showAddSection = count($available_languages) > 0;
    $addSectionStyle = $showAddSection ? '' : ' style="display: none;"';

    $editorElem = $this->getElement('editor');
    $editorEnabled = ($use_editor || (false !== $editorElem && (bool) $editorElem)) ? '1' : '0';
    $previewElem2 = $this->getElement('preview');
    $previewEnabled = (false === $previewElem2 ? true : (bool) $previewElem2) ? '1' : '0';
    $withTextElem = $this->getElement('with_text');
    $withTextEnabled = (false !== $withTextElem && (bool) $withTextElem) ? '1' : '0';
    $textLabelDataElem = $this->getElement('text_label');
    $textLabelData = is_string($textLabelDataElem) && '' !== $textLabelDataElem ? $textLabelDataElem : 'Beschreibung';
    $descriptionDataElem = $this->getElement('description');
    $descriptionData = is_string($descriptionDataElem) ? $descriptionDataElem : '';
    $typesData = $this->getElement('types');
    $typesData = is_string($typesData) ? $typesData : '';
    $categoryData = $this->getElement('category');
    $categoryData = is_scalar($categoryData) ? (string) $categoryData : '';
    $rowsData = isset($parsed_attributes['rows']) && is_scalar($parsed_attributes['rows']) ? (string) $parsed_attributes['rows'] : '5';
    ?>
    <div class="lang-field-add-section"<?= $addSectionStyle ?>>
        <div class="panel panel-default" style="margin-top: 10px; margin-bottom: 0;">
            <div class="panel-body" style="padding: 10px;">
                <div class="row">
                    <div class="col-sm-4">
                        <select class="form-control input-sm lang-select-new" data-field-name="<?= rex_escape($field_name) ?>">
                            <option value="">Sprache wählen...</option>
                            <?php foreach ($available_languages as $lang): ?>
                                <option value="<?= $lang->getId() ?>"
                                        data-name="<?= rex_escape($lang->getName()) ?>"
                                        data-code="<?= rex_escape($lang->getCode()) ?>">
                                    <?= rex_escape($lang->getName() . ' (' . $lang->getCode() . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-8">
                        <button type="button"
                                class="btn btn-success btn-sm btn-add-lang-field"
                                data-field-type="<?= rex_escape($field_type) ?>"
                                data-field-name="<?= rex_escape($field_name) ?>"
                                data-field-id="<?= rex_escape($field_id) ?>"
                                data-field-id-value="<?= $this->getId() ?>"
                                data-form-name="<?= rex_escape($this->params['form_name']) ?>"
                                data-attributes=""
                                data-rows="<?= rex_escape($rowsData) ?>"
                                data-editor="<?= $editorEnabled ?>"
                                data-types="<?= rex_escape($typesData) ?>"
                                data-category="<?= rex_escape($categoryData) ?>"
                                data-preview="<?= $previewEnabled ?>"
                                data-with-text="<?= $withTextEnabled ?>"
                                data-text-label="<?= rex_escape($textLabelData) ?>"
                                data-description="<?= rex_escape($descriptionData) ?>"
                                disabled="">
                            <i class="fa fa-plus"></i> Übersetzung hinzufügen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template für neue Felder (hidden) -->
    <div class="lang-field-template" style="display: none;" data-field-type="<?= rex_escape($field_type) ?>">
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
