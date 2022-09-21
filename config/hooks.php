<?php

use Kirby\Cms\App as Kirby;
use Kirby\Http\Route;
use Oweb\InstagramFeed;

return [
    'route:after' => function (Route $route, string $path, string $method, $result, bool $final) {
        try {
            if (
                Kirby::instance()->option('cache.pages.active', false) === true &&
                InstagramFeed::isCacheExpired() === true
            ) {
                InstagramFeed::flushPagesCache();
            }
        } catch (Exception $e) {
            //
        }

        return $result;
    }
];
