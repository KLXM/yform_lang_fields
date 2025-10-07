/**
 * YForm Lang Fields JavaScript
 * Handles dynamic adding/removing of language fields
 */

(function($) {
    'use strict';

    var YformLangFields = {
        
        init: function() {
            this.bindEvents();
            this.initExistingFields();
            this.bindCKE5Events();
        },

        bindEvents: function() {
            var self = this;

            // Add new language field
            $(document).on('click', '.btn-add-lang-field', function(e) {
                e.preventDefault();
                self.addLanguageField($(this));
            });

            // Remove language field
            $(document).on('click', '.btn-remove-lang-field', function(e) {
                e.preventDefault();
                self.removeLanguageField($(this));
            });

            // Language selection change
            $(document).on('change', '.lang-select-new', function() {
                var $btn = $(this).closest('.lang-field-add-section').find('.btn-add-lang-field');
                if ($(this).val()) {
                    $btn.prop('disabled', false);
                } else {
                    $btn.prop('disabled', true);
                }
            });

            // Media selector
            $(document).on('click', '.btn-media-select', function(e) {
                e.preventDefault();
                self.openMediaSelector($(this));
            });
        },

        initExistingFields: function() {
            // Update indices for existing fields
            $('.yform-lang-field').each(function() {
                var $container = $(this);
                $container.find('.lang-field-item').each(function(index) {
                    $(this).attr('data-index', index);
                });
            });

            // Initialize CKE5 for existing fields using rex:ready
            this.initExistingCKE5Fields();
        },

        addLanguageField: function($btn) {
            var $container = $btn.closest('.yform-lang-field');
            var $langSelect = $container.find('.lang-select-new');
            var selectedLangId = $langSelect.val();
            
            if (!selectedLangId) {
                return;
            }

            var $selectedOption = $langSelect.find('option:selected');
            var langName = $selectedOption.data('name');
            var langCode = $selectedOption.data('code');
            var fieldType = $btn.data('field-type');
            var fieldId = $btn.data('field-id');
            var attributes = $btn.data('attributes') || '';
            var description = $btn.data('description') || '';
            var withText = ($btn.data('with-text') === '1' || $btn.data('with-text') === 1 || $btn.data('with-text') === true);
            var textLabel = $btn.data('text-label') || 'Beschreibung';
            
            console.log('Button data-with-text:', $btn.data('with-text'), 'Converted to boolean:', withText);

            // Generate new index
            var newIndex = $container.find('.lang-field-item').length;
            var fieldIdValue = $btn.data('field-id-value');
            var inputName = 'FORM[' + $btn.data('form-name') + '][' + fieldIdValue + '][' + newIndex + ']';
            var inputId = fieldId + '_' + newIndex;

            // Create new field HTML
            var fieldHtml = this.generateFieldHtml({
                clangId: selectedLangId,
                langName: langName,
                langCode: langCode,
                fieldType: fieldType,
                inputName: inputName,
                inputId: inputId,
                attributes: attributes,
                index: newIndex,
                rows: $btn.data('rows') || 5,
                useEditor: $btn.data('editor') === '1' || $btn.data('editor') === 1,
                types: $btn.data('types') || '',
                category: $btn.data('category') || '',
                description: description,
                withText: withText,
                textLabel: textLabel
            });

            // Add to container
            var $newField = $(fieldHtml);
            $container.find('.lang-fields-container').append($newField);

            // Remove from available languages
            $selectedOption.remove();
            $langSelect.val('');
            $btn.prop('disabled', true);

            // Update add section status
            this.toggleAddSection($container);

            // Initialize CKE5 for new field IMMEDIATELY
            this.initCKE5ForNewField($newField);

            // Update delete buttons
            var $items = $container.find('.lang-field-item');
            if ($items.length > 1) {
                $items.each(function(index) {
                    var $lockedDiv = $(this).find('div[title="Erste Sprache kann nicht entfernt werden"]');
                    if ($lockedDiv.length && index === 0) {
                        $lockedDiv.replaceWith('<button type="button" class="btn btn-danger btn-block btn-remove-lang-field" title="Übersetzung entfernen"><i class="fa fa-trash"></i></button>');
                    }
                });
            }
        },

        removeLanguageField: function($btn) {
            var $fieldItem = $btn.closest('.lang-field-item');
            var $container = $btn.closest('.yform-lang-field');
            var clangId = $fieldItem.data('clang-id');
            var itemIndex = $fieldItem.index();
            
            // Verhindere das Löschen der ersten Sprache wenn sie die einzige ist
            var totalItems = $container.find('.lang-field-item').length;
            if (itemIndex === 0 && totalItems === 1) {
                alert('Die erste Sprache kann nicht entfernt werden.');
                return;
            }
            
            // Get language info from panel-heading
            var $heading = $fieldItem.find('.panel-heading strong');
            var langName = $heading.text().trim();
            var $codeSpan = $fieldItem.find('.panel-heading .text-muted');
            var langCodeText = $codeSpan.text().trim();
            // Extract code from (xx) format
            var langCode = langCodeText.replace(/[()]/g, '');
            var langDisplayName = langName + ' ' + langCodeText;

            // Add back to available languages
            var $langSelect = $container.find('.lang-select-new');
            var $newOption = $('<option></option>')
                .attr('value', clangId)
                .attr('data-name', langName)
                .attr('data-code', langCode)
                .text(langDisplayName);
            
            // Insert in alphabetical order
            var inserted = false;
            $langSelect.find('option:not(:first)').each(function() {
                if ($(this).text() > langDisplayName) {
                    $(this).before($newOption);
                    inserted = true;
                    return false;
                }
            });
            if (!inserted) {
                $langSelect.append($newOption);
            }

            // Wichtig: Add-Sektion wieder anzeigen, da jetzt eine Sprache verfügbar ist
            var $addSection = $container.find('.lang-field-add-section');
            if ($addSection.length) {
                $addSection.show();
            }

            var self = this;
            
            // Remove field with animation
            $fieldItem.fadeOut(300, function() {
                $(this).remove();
                
                // Reindex remaining fields
                $container.find('.lang-field-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('input, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name && name.includes('[')) {
                            var baseName = name.split('[')[0];
                            var suffix = name.substring(name.indexOf('][') + 1);
                            $(this).attr('name', baseName + '[' + index + suffix);
                        }
                    });
                });
                
                // Update delete buttons
                var $items = $container.find('.lang-field-item');
                $items.each(function(index) {
                    var $deleteBtn = $(this).find('.btn-remove-lang-field');
                    
                    if (index === 0 && $items.length === 1) {
                        // Lock-Icon mit gleicher Positionierung wie Delete-Button
                        $deleteBtn.replaceWith('<span class="text-muted" style="position: absolute; top: 10px; right: 10px; font-size: 11px;"><i class="fa fa-lock"></i></span>');
                    }
                });
                
                // Update add section status
                self.toggleAddSection($container);
            });
        },

        generateFieldHtml: function(options) {
            var html = '<div class="lang-field-item panel panel-default" data-clang-id="' + options.clangId + '" data-index="' + options.index + '" style="margin-bottom: 15px;">';
            
            // Panel Header mit Sprache und Delete-Button
            html += '<div class="panel-heading" style="position: relative; padding-right: 50px;">';
            html += '<i class="fa-solid fa-flag" style="margin-right: 8px; color: #777;"></i>';
            html += '<strong>' + this.escapeHtml(options.langName) + '</strong> ';
            html += '<small class="text-muted">(' + this.escapeHtml(options.langCode) + ')</small>';
            html += '<button type="button" class="btn btn-danger btn-xs btn-remove-lang-field" ';
            html += 'style="position: absolute; top: 8px; right: 10px;" title="Übersetzung entfernen">';
            html += '<i class="fa fa-trash"></i></button>';
            html += '</div>';
            
            // Panel Body mit dem Feld
            html += '<div class="panel-body">';
            
            if (options.fieldType === 'text') {
                html += '<input type="text" name="' + options.inputName + '[value]" id="' + options.inputId + '" ';
                html += 'class="form-control lang-input" value="" ' + options.attributes + ' ';
                html += 'data-clang-id="' + options.clangId + '" placeholder="' + this.escapeHtml(options.langName) + '" />';
                html += '<input type="hidden" name="' + options.inputName + '[clang_id]" value="' + options.clangId + '" />';
            } else if (options.fieldType === 'textarea') {
                var textareaClass = 'form-control lang-textarea';

                if (options.useEditor) {
                    textareaClass += ' cke5-editor';
                }

                html += '<textarea name="' + options.inputName + '[value]" id="' + options.inputId + '" ';
                html += 'class="' + textareaClass + '" rows="' + options.rows + '" ' + options.attributes + ' ';
                
                // CKE5-spezifische Daten-Attribute hinzufügen
                if (options.useEditor) {
                    html += 'data-profile="default" data-lang="' + this.escapeHtml(options.langCode) + '" ';
                }
                
                html += 'data-clang-id="' + options.clangId + '"></textarea>';
                html += '<input type="hidden" name="' + options.inputName + '[clang_id]" value="' + options.clangId + '" />';
            } else if (options.fieldType === 'media') {
                // Eindeutige Widget-ID generieren
                if (!this.mediaWidgetCounter) {
                    this.mediaWidgetCounter = 1000; // Start bei 1000 um Konflikte zu vermeiden
                }
                this.mediaWidgetCounter++;
                var widgetId = this.mediaWidgetCounter;
                
                // Parameter für openREXMedia zusammenbauen
                var openParams = '';
                if (options.category) {
                    openParams += ", '&rex_file_category=" + parseInt(options.category) + "'";
                }
                if (options.types) {
                    if (!openParams) openParams = ", ''";
                    openParams += "&args[types]=" + encodeURIComponent(options.types) + "'";
                }
                
                var mediaNameSuffix = options.withText ? '[media]' : '';
                var marginBottom = options.withText ? ' style="margin-bottom: 10px;"' : '';
                
                // Medienauswahl
                html += '<div class="input-group"' + marginBottom + '>';
                html += '<input type="text" name="' + options.inputName + '[value]' + mediaNameSuffix + '" id="REX_MEDIA_' + widgetId + '" ';
                html += 'class="form-control" value="" data-clang-id="' + options.clangId + '" readonly />';
                html += '<input type="hidden" name="' + options.inputName + '[clang_id]" value="' + options.clangId + '" />';
                html += '<span class="input-group-btn">';
                html += '<a href="#" class="btn btn-popup" onclick="openREXMedia(' + widgetId + openParams + '); return false;" title="Medium auswählen">';
                html += '<i class="rex-icon rex-icon-open-mediapool"></i></a>';
                html += '<a href="#" class="btn btn-popup" onclick="deleteREXMedia(' + widgetId + '); return false;" title="Medium entfernen">';
                html += '<i class="rex-icon rex-icon-delete-media"></i></a>';
                html += '</span>';
                html += '</div>';
                
                // Optional: Zusätzliches Textfeld
                if (options.withText) {
                    console.log('Adding text field with label:', options.textLabel);
                    html += '<input type="text" name="' + options.inputName + '[value][text]" ';
                    html += 'class="form-control" style="margin-top: 10px;" value="" ';
                    html += 'placeholder="' + this.escapeHtml(options.textLabel) + '" />';
                } else {
                    console.log('Text field NOT added. withText:', options.withText);
                }
            }
            
            html += '</div>'; // panel-body
            html += '</div>'; // panel
            
            return html;
        },

        openMediaSelector: function($btn) {
            var targetId = $btn.data('target');
            var $input = $(targetId);
            var types = $input.data('types') || '';
            var category = $input.data('category') || '';
            
            // Entferne # vom targetId für die Funktion
            var inputId = targetId.replace('#', '');
            
            console.log('YForm Lang Media: Opening media selector', {
                targetId: targetId,
                inputId: inputId,
                types: types,
                category: category,
                openREXMediaAvailable: typeof openREXMedia !== 'undefined'
            });
            
            if (typeof openREXMedia === 'function') {
                // Erstelle eine globale Callback-Funktion für dieses Feld
                var callbackName = 'selectMedia_' + inputId.replace(/[^a-zA-Z0-9]/g, '_');
                
                window[callbackName] = function(filename) {
                    console.log('YForm Lang Media: Callback aufgerufen für', inputId, 'Datei:', filename);
                    $input.val(filename);
                    $input.trigger('change');
                    
                    // Cleanup
                    delete window[callbackName];
                };
                
                // REDAXO Media-Widget öffnen mit Callback
                openREXMedia(inputId, callbackName);
            } else {
                console.warn('YForm Lang Media: openREXMedia Funktion nicht verfügbar');
                var filename = prompt('Dateiname eingeben:', $input.val());
                if (filename !== null) {
                    $input.val(filename);
                }
            }
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        toggleAddSection: function($container) {
            var $addSection = $container.find('.lang-field-add-section');
            if ($addSection.length === 0) return;
            
            var $langSelect = $addSection.find('.lang-select-new');
            var availableOptions = $langSelect.find('option').length - 1;
            
            if (availableOptions > 0) {
                $addSection.show();
            } else {
                $addSection.hide();
            }
        },

        // CKE5-Initialisierung für bestehende Felder
        initExistingCKE5Fields: function() {
            // REDAXO initialisiert automatisch beim Laden - wir machen nichts
        },

        // CKE5-Initialisierung für neue Felder
        initCKE5ForNewField: function($field) {
            var self = this;
            var $textareas = $field.find('textarea.cke5-editor');
            console.log('YForm Lang: Initialisiere CKE5 für', $textareas.length, 'neue Textareas');
            
            if ($textareas.length > 0) {
                $textareas.each(function() {
                    var $textarea = $(this);
                    var oldId = $textarea.attr('id');
                    
                    // Generiere neue CKE5-konforme ID
                    var newId = 'ck' + Math.random().toString(16).slice(2);
                    $textarea.attr('id', newId);
                    
                    console.log('YForm Lang: Ändere ID von', oldId, 'zu', newId);
                    console.log('YForm Lang: Hat cke5-editor Klasse:', $textarea.hasClass('cke5-editor'));
                    
                    // Prüfe ob bereits ein Editor existiert
                    if (typeof ckeditors !== 'undefined' && ckeditors[newId]) {
                        console.warn('YForm Lang: Editor existiert bereits für', newId);
                        return;
                    }
                    
                    // Prüfe ob cke5_init verfügbar ist
                    if (typeof cke5_init === 'function') {
                        console.log('YForm Lang: Rufe cke5_init auf für', newId);
                        setTimeout(function() {
                            cke5_init($textarea);
                        }, 100);
                    } else {
                        console.warn('YForm Lang: cke5_init Funktion nicht verfügbar');
                    }
                });
            } else {
                console.warn('YForm Lang: Keine .cke5-editor Textareas gefunden in neuem Feld');
            }
        },

        // CKE5 Events binden
        bindCKE5Events: function() {
            var self = this;

            // Event-Handler für erfolgreiche CKE5-Initialisierung
            $(window).off('rex:cke5IsInit.langfields').on('rex:cke5IsInit.langfields', function(event, editor, uniqueId) {
                var $sourceElement = $(editor.sourceElement);
                if ($sourceElement.hasClass('cke5-editor') && $sourceElement.closest('.yform-lang-field').length > 0) {
                    console.log('CKE5 lang field initialized:', $sourceElement.attr('id'));
                }
            });

            // Event für CKE5-Fehler
            $(window).off('rex:cke5Error.langfields').on('rex:cke5Error.langfields', function(event, error, editorId) {
                console.warn('CKE5 error in lang field:', editorId, error);
            });
        }
    };

    // Initialize when REDAXO is ready
    $(document).on('rex:ready', function() {
        YformLangFields.init();
    });

    // Make globally available
    window.YformLangFields = YformLangFields;

})(jQuery);
