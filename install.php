<?php

use KLXM\YformLangFields\LangHelper;

/**
 * Installationsskript für yform_lang_fields
 */

$this->setProperty('install', true);

// Prüfen ob YForm verfügbar ist
if (!rex_addon::get('yform')->isAvailable()) {
    $this->setProperty('installmsg', 'Das AddOn "YForm" muss installiert und aktiviert sein!');
    $this->setProperty('install', false);
    return;
}

// Prüfen ob Sprachen konfiguriert sind
$languages = rex_clang::getAll();
if (count($languages) < 2) {
    $this->setProperty('installmsg', 'Es müssen mindestens 2 Sprachen in REDAXO konfiguriert sein, um mehrsprachige Felder zu verwenden.');
    $this->setProperty('install', false);
    return;
}

