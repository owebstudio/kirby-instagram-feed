<?php

use Kirby\Http\Response;

return [
    [
        'pattern' => mediaPrefix('plugins/owebstudio/instagram-feed/(:all)'),
        'env' => 'media',
        'action' => function ($path) {
            return Response::file(mediaPrefix('/plugins/owebstudio/instagram-feed/' . $path));
        }
    ]
];
