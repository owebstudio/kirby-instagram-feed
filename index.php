<?php

use Kirby\Cms\App as Kirby;
use Kirby\Toolkit\Collection;

@include_once __DIR__ . '/helpers.php';

Kirby::plugin('clicktonext/instagram-feed', [
    'options' => [
        'accessToken' => null,
        'cache' => true,
        'debug' => false,
        'expire' => 1440,
        'limit' => 20
    ],
    'siteMethods' => [
        'instagramFeed' => function () {
            $options = option('clicktonext.instagram-feed');

            try {
                if (empty($options['accessToken']) === true) {
                    throw new Exception('Instagram API access token required!');
                }

                // check check
                if ($options['cache'] === true) {
                    $pluginCache = kirby()->cache('clicktonext.instagram-feed');

                    // refresh token
                    $tokenCacheData = $pluginCache->get('token');

                    if ($tokenCacheData === null) {
                        $expireToken = refreshInstagramToken();

                        if ($expireToken !== null) {
                            $pluginCache->set('token', date('Y-m-d H:i:s'), $expireToken);
                        }
                    }

                    // feed
                    $feedCacheData = $pluginCache->get('feed');

                    if ($feedCacheData !== null) {
                        $feed = json_decode($feedCacheData, true);
                    } else {
                        $feed = fetchInstagramFeed();
                        $pluginCache->set('feed', json_encode($feed), $options['expire']);
                    }
                } else {
                    refreshInstagramToken();
                    $feed = fetchInstagramFeed();
                }
            } catch (Exception $e) {
                if ($options['debug'] === true) {
                    throw $e;
                }

                $feed = [];
            }

            // feed collection
            $collection = new Collection();

            foreach ($feed as $item) {
                $collection->append(new Collection($item));
            }

            return $collection;
        }
    ],
    'snippets' => [
        'instagram-feed' => __DIR__ . '/snippets/instagram-feed.php'
    ]
]);
