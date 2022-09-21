<?php

use Kirby\Cms\App as Kirby;

load([
    'oweb\\instagramfeed' => __DIR__ . '/src/InstagramFeed.php'
]);

Kirby::plugin('owebstudio/instagram-feed', [
    'options' => include __DIR__ . '/config/options.php',
    'routes' => include __DIR__ . '/config/routes.php',
    'siteMethods' => include __DIR__ . '/config/siteMethods.php',
    'snippets' => include __DIR__ . '/config/snippets.php'
]);
