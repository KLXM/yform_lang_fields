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
            var attributesMap = this.parseAttributesData($btn.attr('data-attributes'));
            var description = $btn.data('description') || '';
            var withText = ($btn.data('with-text') === '1' || $btn.data('with-text') === 1 || $btn.data('with-text') === true);
            var textLabel = $btn.data('text-label') || 'Beschreibung';
            var editorType = String($btn.data('editor-type') || 'none').toLowerCase();
            
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
                attributesMap: attributesMap,
                index: newIndex,
                rows: $btn.data('rows') || 5,
                editorType: editorType,
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

            // Initialize configured rich-text editors for the new field.
            this.initEditorsForNewField($newField, editorType);

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
                var textAttrs = this.cloneAttributes(options.attributesMap || {});
                var textClass = textAttrs['class'] || 'form-control lang-input';
                delete textAttrs['class'];
                delete textAttrs['rows'];
                var textAttributeString = this.buildAttributeString(textAttrs);

                html += '<input type="text" name="' + options.inputName + '[value]" id="' + options.inputId + '" ';
                html += 'class="' + this.escapeHtml(textClass) + '" value="" ' + textAttributeString + ' ';
                html += 'data-clang-id="' + options.clangId + '" placeholder="' + this.escapeHtml(options.langName) + '" />';
                html += '<input type="hidden" name="' + options.inputName + '[clang_id]" value="' + options.clangId + '" />';
            } else if (options.fieldType === 'textarea') {
                var attrs = this.cloneAttributes(options.attributesMap || {});
                if (attrs.profile && !attrs['data-profile']) {
                    attrs['data-profile'] = attrs.profile;
                    delete attrs.profile;
                }
                if (attrs.lang && !attrs['data-lang']) {
                    attrs['data-lang'] = attrs.lang;
                    delete attrs.lang;
                }

                var textareaRows = attrs.rows || options.rows || 5;
                delete attrs.rows;

                var textareaClass = attrs['class'] || 'form-control lang-textarea';
                delete attrs['class'];

                if (options.editorType === 'cke5') {
                    if (textareaClass.indexOf('cke5-editor') === -1) {
                        textareaClass += ' cke5-editor';
                    }
                    if (!attrs['data-lang']) {
                        attrs['data-lang'] = options.langCode;
                    }
                }

                if (options.editorType === 'tinymce') {
                    if (textareaClass.indexOf('tiny-editor') === -1 && textareaClass.indexOf('tinyMCEEditor') === -1) {
                        textareaClass += ' tiny-editor';
                    }
                }

                var attributeString = this.buildAttributeString(attrs);

                html += '<textarea name="' + options.inputName + '[value]" id="' + options.inputId + '" ';
                html += 'class="' + this.escapeHtml(textareaClass) + '" rows="' + this.escapeHtml(String(textareaRows)) + '" ' + attributeString + ' ';
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

        parseAttributesData: function(rawAttributes) {
            if (!rawAttributes || typeof rawAttributes !== 'string') {
                return {};
            }

            try {
                var parsed = JSON.parse(rawAttributes);
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
            } catch (e) {
                // Ignore malformed attributes and keep defaults.
            }

            return {};
        },

        cloneAttributes: function(attributes) {
            if (!attributes || typeof attributes !== 'object') {
                return {};
            }

            return Object.assign({}, attributes);
        },

        buildAttributeString: function(attributes) {
            var self = this;
            var html = '';

            Object.keys(attributes).forEach(function(key) {
                var value = attributes[key];
                if (value === null || value === undefined) {
                    return;
                }

                html += ' ' + self.escapeHtml(String(key)) + '="' + self.escapeHtml(String(value)) + '"';
            });

            return html;
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

        initTinyMceForNewField: function($field) {
            var $textareas = $field.find('textarea.tiny-editor, textarea.tinyMCEEditor');
            if ($textareas.length === 0) {
                return;
            }

            if (typeof tiny_init === 'function') {
                tiny_init($field.closest('.yform-lang-field'));
            }
        },

        initEditorsForNewField: function($field, editorType) {
            var normalizedType = String(editorType || 'none').toLowerCase();

            if (normalizedType === 'cke5') {
                this.initCKE5ForNewField($field);
                return;
            }

            if (normalizedType === 'tinymce') {
                this.initTinyMceForNewField($field);
                return;
            }

            // Fallback: auto-detect by classes for unknown/legacy configurations.
            if ($field.find('textarea.cke5-editor').length > 0) {
                this.initCKE5ForNewField($field);
            }
            if ($field.find('textarea.tiny-editor, textarea.tinyMCEEditor').length > 0) {
                this.initTinyMceForNewField($field);
            }
        },

        // Popover für zusätzliche Sprachen in der YForm-Listenansicht initialisieren
        initListPopovers: function() {
            var $badges = $('[data-bs-toggle="popover"].ylf-list-more');
            if ($badges.length === 0) {
                return;
            }

            // Bootstrap 5
            if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
                $badges.each(function() {
                    new bootstrap.Popover(this, { sanitize: false });
                });
                return;
            }

            // Bootstrap 3/4 via jQuery
            if (typeof $.fn.popover === 'function') {
                $badges.popover({ html: true, trigger: 'hover focus', container: 'body' });
            }
        },
        /**
         * Sprachswitch für YForm-Listenansicht.
         *
         * - Liest localStorage('ylf_active_clang') zum Bestimmen der angezeigten Sprache.
         * - Wandelt den vom EP eingefügten Trigger-Button (.ylf-clang-switch-trigger)
         *   in ein Bootstrap-Dropdown-Menü à la QuickNavigation um.
         * - Blendet beim Klick auf eine Sprache alle .ylf-list-clang-Spans aus und
         *   zeigt nur den gewählten an; schreibt in localStorage.
         */
        initLangSwitch: function() {
            var self = this;
            var $trigger = $('#ylf-clang-switch-trigger');

            if ($trigger.length === 0) {
                // Kein Trigger-Button → Tabelle hat keine Lang-Felder oder
                // wir befinden uns nicht auf einer Listenseite.
                // Dennoch: aktive Sprache auf bestehende Spans anwenden.
                self.applyActiveLang();
                return;
            }

            var clangsRaw = $trigger.attr('data-ylf-clangs');
            var clangs = [];
            try {
                clangs = JSON.parse(clangsRaw || '[]');
            } catch (e) {
                clangs = [];
            }

            if (clangs.length < 2) {
                self.applyActiveLang();
                return;
            }

            // Trigger-Button in Dropdown-Container verwandeln
            var activeId = parseInt(localStorage.getItem('ylf_active_clang') || '0', 10);
            var activeClang = null;
            for (var i = 0; i < clangs.length; i++) {
                if (clangs[i].id === activeId) {
                    activeClang = clangs[i];
                    break;
                }
            }
            if (!activeClang) {
                activeClang = clangs[0];
            }

            // Icon + aktueller Code als Button-Label
            $trigger.html(
                '<i class="rex-icon fa-language"></i> '
                + '<span class="ylf-clang-badge">' + self.escapeHtml(activeClang.code.toUpperCase()) + '</span>'
                + ' <span class="caret"></span>'
            );
            $trigger.attr('data-toggle', 'dropdown')
                    .attr('aria-haspopup', 'true')
                    .attr('aria-expanded', 'false')
                    .addClass('dropdown-toggle');

            // Da der Trigger bereits in einer btn-group liegt (durch YForm generiert),
            // dürfen wir ihn NICHT nochmals wrappen, sonst funktioniert data-toggle="dropdown" in Bootstrap 3 nicht richtig.
            // Stattdessen fügen wir das Dropdown-Menü direkt nach dem Trigger in die bestehende btn-group ein.
            var $group = $trigger.closest('.btn-group');
            if ($group.length === 0) {
                // Fallback falls keine btn-group existiert
                $trigger.wrap('<div class="btn-group ylf-clang-dropdown"></div>');
                $group = $trigger.parent();
            } else {
                $group.addClass('ylf-clang-dropdown');
            }

            var menuHtml = '<ul class="dropdown-menu dropdown-menu-right ylf-clang-menu">';
            menuHtml += '<li class="dropdown-header">Sprache</li>';
            for (var j = 0; j < clangs.length; j++) {
                var c = clangs[j];
                var isCurrent = (c.id === activeClang.id) ? ' class="ylf-clang-current"' : '';
                menuHtml += '<li' + isCurrent + '>'
                    + '<a href="#" data-ylf-switch="' + c.id + '">'
                    + '<strong>' + self.escapeHtml(c.code.toUpperCase()) + '</strong>'
                    + ' <small>' + self.escapeHtml(c.name) + '</small>'
                    + '</a></li>';
            }
            var showIncomplete = localStorage.getItem('ylf_show_incomplete') === 'true';
            var iconClass = showIncomplete ? 'fa-check-square-o' : 'fa-square-o';
            menuHtml += '<li role="separator" class="divider"></li>';
            menuHtml += '<li><a href="#" class="ylf-toggle-incomplete"><i class="fa fa-fw ' + iconClass + '"></i> <small>Fehlende hervorheben</small></a></li>';
            menuHtml += '</ul>';
            $group.append(menuHtml);

            // Klick auf Incomplete Toggle
            $group.on('click', '.ylf-toggle-incomplete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var isEnabled = localStorage.getItem('ylf_show_incomplete') === 'true';
                isEnabled = !isEnabled;
                localStorage.setItem('ylf_show_incomplete', isEnabled ? 'true' : 'false');
                
                var $icon = $(this).find('.fa');
                if (isEnabled) {
                    $icon.removeClass('fa-square-o').addClass('fa-check-square-o');
                } else {
                    $icon.removeClass('fa-check-square-o').addClass('fa-square-o');
                }
                self.applyIncompleteHighlighting();
            });

            // Klick auf Menüeintrag
            $group.on('click', 'a[data-ylf-switch]', function(e) {
                e.preventDefault();
                var newId = parseInt($(this).attr('data-ylf-switch'), 10);
                localStorage.setItem('ylf_active_clang', String(newId));

                // Button-Badge aktualisieren
                var newCode = $(this).find('strong').text();
                $trigger.find('.ylf-clang-badge').text(newCode);

                // Aktive Markierung im Menü setzen
                $group.find('.ylf-clang-menu li').removeClass('ylf-clang-current');
                $(this).closest('li').addClass('ylf-clang-current');

                self.applyActiveLang(newId);
            });

            // Initiale Anzeige
            self.applyActiveLang(activeClang.id);
            self.applyIncompleteHighlighting();
        },

        applyIncompleteHighlighting: function() {
            var isEnabled = localStorage.getItem('ylf_show_incomplete') === 'true';
            if (isEnabled) {
                $('body').addClass('ylf-show-incomplete');
            } else {
                $('body').removeClass('ylf-show-incomplete');
            }
        },

        /**
         * Blendet alle .ylf-list-clang-Spans aus und zeigt nur den aktiven.
         * Ohne Parameter wird activeId aus localStorage gelesen.
         */
        applyActiveLang: function(activeId) {
            if (activeId === undefined) {
                activeId = parseInt(localStorage.getItem('ylf_active_clang') || '0', 10);
            }

            $('.ylf-list-entry').each(function() {
                var $entry = $(this);
                var $spans = $entry.find('.ylf-list-clang');

                // JS ist aktiv: CSS-Fallback deaktivieren
                $entry.addClass('ylf-js-active');

                if (activeId > 0) {
                    var $target = $spans.filter('[data-ylf-clang="' + activeId + '"]');
                    if ($target.length > 0) {
                        $spans.not($target).hide();
                        $target.css('display', 'inline-flex');
                        return;
                    }
                }

                // Fallback: Standardsprache des Eintrags zeigen
                var defaultId = parseInt($entry.attr('data-ylf-default') || '0', 10);
                if (defaultId > 0) {
                    var $def = $spans.filter('[data-ylf-clang="' + defaultId + '"]');
                    $spans.not($def).hide();
                    $def.css('display', 'inline-flex');
                } else {
                    // Alles einblenden wenn kein Match
                    $spans.css('display', 'inline-flex');
                }
            });
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
        YformLangFields.initLangSwitch();
    });

    // Make globally available
    window.YformLangFields = YformLangFields;

})(jQuery);
