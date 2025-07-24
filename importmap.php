<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'bootstrap' => [
        'version' => '5.3.7',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.7',
        'type' => 'css',
    ],
    'clipboard' => [
        'version' => '2.0.11',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom' => [
        'version' => '0.4.1',
    ],
    'rx' => [
        'version' => '4.1.0',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    '@fortawesome/fontawesome-free/css/all.css' => [
        'version' => '7.0.0',
        'type' => 'css',
    ],
    '@fontsource-variable/roboto-condensed/index.min.css' => [
        'version' => '5.2.6',
        'type' => 'css',
    ],
    'axios' => [
        'version' => '1.10.0',
    ],
    'tinymce/tinymce' => [
        'version' => '7.9.1',
    ],
    'bs-custom-file-input' => [
        'version' => '1.3.4',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'datatables' => [
        'version' => '1.10.18',
    ],
    'tinymce' => [
        'version' => '7.9.1',
    ],
    'tinymce/themes/silver' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/link' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/code' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/table' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/lists' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/insertdatetime' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/preview' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/media' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/wordcount' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/autolink' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/autoresize' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/anchor' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/charmap' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/image' => [
        'version' => '7.9.1',
    ],
    'tinymce/models/dom' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/visualblocks' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/advlist' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/help' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/searchreplace' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/fullscreen' => [
        'version' => '7.9.1',
    ],
    'tinymce/skins/ui/oxide-dark/skin' => [
        'version' => '7.9.1',
    ],
    'tinymce/skins/ui/oxide/skin' => [
        'version' => '7.9.1',
    ],
    'tinymce/icons/default' => [
        'version' => '7.9.1',
    ],
    'tinymce/skins/ui/oxide-dark/content' => [
        'version' => '7.9.1',
    ],
    'tinymce/plugins/help/js/i18n/keynav/en' => [
        'version' => '7.9.1',
    ],
    'tinymce/skins/ui/oxide/content' => [
        'version' => '7.9.1',
    ],
    'tinymce/skins/content/dark/content' => [
        'version' => '7.9.1',
    ],
];
