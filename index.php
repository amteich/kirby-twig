<?php

@include_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';

use Kirby\Cms\App;

Kirby::plugin('amteich/twig', [
    'options' => [
        'usephp' => true
    ],
    'components' => [
        'template' => function (App $kirby, string $name, string $contentType = 'html', string $defaultType = 'html') {
            return new amteich\Twig\Template($name, $contentType, $defaultType);
        }
    ]
]);
