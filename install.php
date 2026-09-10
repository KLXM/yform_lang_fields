<?php

/** @var rex_addon $this */

/**
 * Installationsskript für yform_lang_fields.
 */
$this->setProperty('install', true);

// Prüfen ob YForm verfügbar ist
if (!rex_addon::get('yform')->isAvailable()) {
    $this->setProperty('installmsg', 'Das AddOn "YForm" muss installiert und aktiviert sein!');
    $this->setProperty('install', false);
    return;
}

// Hinweis, falls (noch) nur eine Sprache konfiguriert ist. Kein Installationsabbruch,
// da die Felder bereits mit einer Sprache funktionieren und sich das Addon so schon
// vor dem Anlegen weiterer Sprachen installieren lässt.
if (count(rex_clang::getAll()) < 2) {
    $this->setProperty('installmsg', 'Hinweis: Es ist aktuell nur eine Sprache in REDAXO konfiguriert. Die mehrsprachigen Felder funktionieren erst mit mindestens zwei Sprachen sinnvoll.');
}

