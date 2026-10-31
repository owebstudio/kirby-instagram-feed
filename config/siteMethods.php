<?php

use Kirby\Cms\App as Kirby;
use Oweb\InstagramFeed;

return [
    'instagramFeed' => function () {
        $fromCache = false;
        $kirby = Kirby::instance();
        $options = $kirby->option('owebstudio.instagram-feed');

        try {
            if (empty($options['accessToken']) === true) {
                throw new Exception('Instagram API access token required!');
            }

            InstagramFeed::refreshToken();

            // check check
            if ($options['cache'] === true) {
                $pluginCache = $kirby->cache('owebstudio.instagram-feed');

                // feed
                $feedCacheData = $pluginCache->get('feed');

                if ($feedCacheData !== null) {
                    $fromCache = true;
                    $feed = json_decode($feedCacheData, true) ?? [];
                } else {
                    $feed = InstagramFeed::fetchFeed();
                    $pluginCache->set('feed', json_encode($feed), $options['expire']);
                }

                // cached pages with the feed expire together with the feed
                if ($expires = $pluginCache->expires('feed')) {
                    $kirby->response()->expires($expires);
                }
            } else {
                $feed = InstagramFeed::fetchFeed();
            }
        } catch (Exception $e) {
            if ($options['debug'] === true) {
                throw $e;
            }

            $feed = [];
        }

        return InstagramFeed::feedToCollection($feed, $fromCache);
    }
];
