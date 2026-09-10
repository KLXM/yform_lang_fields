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

// URL-Addon: Lang-Feld-JSON in SEO-Tags der URL-Profile auflösen
rex_extension::register('PACKAGES_INCLUDED', static function () {
    if (!rex::isFrontend() || !rex_addon::get('url')->isAvailable()) {
        return;
    }

    rex_extension::register('URL_SEO_TAGS', static function (rex_extension_point $ep) {
        $manager = \Url\Url::resolveCurrent();
        if (!$manager) {
            return;
        }

        $tags = $ep->getSubject();
        $clangId = rex_clang::getCurrentId();

        $normalize = static function (string $value): string {
            $value = rex_escape(strip_tags($value));
            return str_replace(["\n", "\r"], [' ', ''], $value);
        };

        $raw = $manager->getSeoTitle();
        if (is_string($raw) && [] !== \KLXM\YformLangFields\LangHelper::normalizeLanguageData($raw)) {
            $value = \KLXM\YformLangFields\LangHelper::getValueForLanguage($raw, $clangId);
            if ('' !== trim($value)) {
                $title = $normalize($value);
                $tags['title'] = '<title>' . $title . '</title>';
                $tags['og:title'] = '<meta property="og:title" content="' . $title . '" />';
                $tags['twitter:title'] = '<meta name="twitter:title" content="' . $title . '" />';
            } else {
                unset($tags['title'], $tags['og:title'], $tags['twitter:title']);
            }
        }

        $raw = $manager->getSeoDescription();
        if (is_string($raw) && [] !== \KLXM\YformLangFields\LangHelper::normalizeLanguageData($raw)) {
            $value = \KLXM\YformLangFields\LangHelper::getValueForLanguage($raw, $clangId);
            if ('' !== trim($value)) {
                $description = $normalize($value);
                $tags['description'] = '<meta name="description" content="' . $description . '" />';
                $tags['og:description'] = '<meta property="og:description" content="' . $description . '" />';
                $tags['twitter:description'] = '<meta name="twitter:description" content="' . $description . '" />';
            } else {
                unset($tags['description'], $tags['og:description'], $tags['twitter:description']);
            }
        }

        // lang_media als SEO-Bild: Url\Seo hat für JSON-Werte keine Bild-Tags
        // erzeugt (rex_media::get(JSON) schlägt fehl), daher hier neu aufbauen
        $raw = $manager->getSeoImage();
        if (is_string($raw) && [] !== \KLXM\YformLangFields\LangHelper::normalizeLanguageData($raw)) {
            $value = \KLXM\YformLangFields\LangHelper::getValueForLanguage($raw, $clangId);
            $images = explode(',', $value);
            $media = rex_media::get(trim((string) array_shift($images)));
            if ($media) {
                $url = $manager->getUrl();
                $url->withSolvedScheme();
                $mediaUrl = $url->getSchemeAndHttpHost() . $media->getUrl();

                $tags['image'] = '<meta name="image" content="' . $mediaUrl . '" />';
                $tags['og:image'] = '<meta property="og:image" content="' . $mediaUrl . '" />';
                if ($media->getWidth()) {
                    $tags['og:image:width'] = '<meta property="og:image:width" content="' . $media->getWidth() . '" />';
                }
                if ($media->getHeight()) {
                    $tags['og:image:height'] = '<meta property="og:image:height" content="' . $media->getHeight() . '" />';
                }
                $tags['twitter:image'] = '<meta name="twitter:image" content="' . $mediaUrl . '" />';
                $tags['twitter:card'] = '<meta name="twitter:card" content="summary_large_image" />';
            }
        }

        $ep->setSubject($tags);
    }, rex_extension::EARLY);
});