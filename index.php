<?php

use Kirby\Cms\App as Kirby;

@include_once __DIR__ . '/helpers.php';

Kirby::plugin('owebstudio/instagram-feed', [
    'options' => include __DIR__ . '/config/options.php',
    'routes' => include __DIR__ . '/config/routes.php',
    'siteMethods' => include __DIR__ . '/config/siteMethods.php',
    'snippets' => include __DIR__ . '/config/snippets.php'
]);
