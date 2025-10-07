<?php

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
}