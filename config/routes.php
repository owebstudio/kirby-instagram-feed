<?php

use Kirby\Http\Response;
use Oweb\InstagramFeed;

return [
    [
        'pattern' => InstagramFeed::mediaPrefix('plugins/owebstudio/instagram-feed/(:all)'),
        'env' => 'media',
        'action' => function ($path) {
            return Response::file(InstagramFeed::mediaPrefix('/plugins/owebstudio/instagram-feed/' . $path));
        }
    ]
];
