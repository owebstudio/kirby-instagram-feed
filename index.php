<?php

use Kirby\Cms\App as Kirby;
use Oweb\InstagramFeed\License\License;

require_once __DIR__ . '/lib/license/autoload.php';

load([
    'oweb\\instagramfeed' => __DIR__ . '/src/InstagramFeed.php'
]);

$license = new License(
    plugin: 'owebstudio/instagram-feed',
    products: [15537],
    name: 'Instagram Feed',
    url: 'https://owebstudio.com/kirby/plugins/instagram-feed'
);

Kirby::plugin('owebstudio/instagram-feed', $license->extend([
    'options' => include __DIR__ . '/config/options.php',
    'routes' => include __DIR__ . '/config/routes.php',
    'siteMethods' => include __DIR__ . '/config/siteMethods.php',
    'snippets' => include __DIR__ . '/config/snippets.php'
]), ...$license->args());
