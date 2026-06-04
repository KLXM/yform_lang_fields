<?php

/** @var rex_addon $this */

// Autoloader für Namespace
rex_autoload::addDirectory(rex_path::addon('yform_lang_fields', 'lib'));

// YForm-Feldklassen laden (im globalen Namespace für YForm-Erkennung)
require_once rex_path::addon('yform_lang_fields', 'lib/rex_yform_value_lang_text.php');
require_once rex_path::addon('yform_lang_fields', 'lib/rex_yform_value_lang_textarea.php');
require_once rex_path::addon('yform_lang_fields', 'lib/rex_yform_value_lang_media.php');

// Extension Points registrieren
rex_extension::register('PACKAGES_INCLUDED', function() {
    // Templates registrieren
    rex_yform::addTemplatePath(rex_path::addon('yform_lang_fields', 'ytemplates'));
});

// Assets für Backend einbinden
if (rex::isBackend()) {
    rex_view::addCssFile($this->getAssetsUrl('lang-fields.css'));
    rex_view::addJsFile($this->getAssetsUrl('lang-fields.js'));

    // Sprachswitch-Dropdown in YForm-Listenansicht einfügen,
    // wenn die Tabelle mindestens ein lang_*-Feld besitzt.
    rex_extension::register('YFORM_DATA_LIST_LINKS', static function (rex_extension_point $ep): void {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }

        $fields = $table->getFields();
        if (!\KLXM\YformLangFields\LangHelper::tableHasLangFields($fields)) {
            return;
        }

        // Alle aktiven Sprachen als JSON für JS bereitstellen
        $clangs = [];
        foreach (rex_clang::getAll() as $clang) {
            if ($clang->isOnline()) {
                $clangs[] = [
                    'id'   => $clang->getId(),
                    'code' => $clang->getCode(),
                    'name' => $clang->getName(),
                ];
            }
        }

        if (count($clangs) < 2) {
            return; // Nur eine Sprache → kein Dropdown nötig
        }

        $clangJson = json_encode($clangs, JSON_THROW_ON_ERROR);

        // Button als dataset_link einhängen (HTML-String)
        $subject = $ep->getSubject();
        $datasetLinks = $subject['dataset_links'];
        $datasetLinks[] = [
            'label'      => '<i class="rex-icon fa-language"></i>',
            'url'        => '#',
            'attributes' => [
                'class'           => ['btn-default', 'ylf-clang-switch-trigger'],
                'data-ylf-clangs' => $clangJson,
                'title'           => 'Sprache in Listenansicht',
                'id'              => 'ylf-clang-switch-trigger',
            ],
        ];
        $subject['dataset_links'] = $datasetLinks;
        $ep->setSubject($subject);
    });
}