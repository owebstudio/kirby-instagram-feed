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

            // check check
            if ($options['cache'] === true) {
                $pluginCache = $kirby->cache('owebstudio.instagram-feed');

                // refresh token
                $tokenCacheData = $pluginCache->get('token');

                if ($tokenCacheData === null) {
                    $expireToken = InstagramFeed::refreshToken();

                    if ($expireToken !== null) {
                        $pluginCache->set('token', date('Y-m-d H:i:s'), $expireToken);
                    }
                }

                // feed
                $feedCacheData = $pluginCache->get('feed');

                if ($feedCacheData !== null) {
                    $fromCache = true;
                    $feed = json_decode($feedCacheData, true) ?? [];
                } else {
                    $feed = InstagramFeed::fetchFeed();
                    $pluginCache->set('feed', json_encode($feed), $options['expire']);
                }
            } else {
                InstagramFeed::refreshToken();
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
