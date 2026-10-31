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
    url: 'https://owebstudio.lemonsqueezy.com/checkout/buy/29afd8d3-78d4-4cd5-a19b-a13c3d3b5e1b'
);

Kirby::plugin('owebstudio/instagram-feed', $license->extend([
    'commands' => include __DIR__ . '/config/commands.php',
    'options' => include __DIR__ . '/config/options.php',
    'siteMethods' => include __DIR__ . '/config/siteMethods.php',
    'snippets' => include __DIR__ . '/config/snippets.php'
]), ...$license->args());
