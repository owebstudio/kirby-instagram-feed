<?php

use Kirby\Cms\App as Kirby;
use Kirby\Toolkit\Collection;
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
                    $expireToken = InstagramFeed::refreshInstagramToken();

                    if ($expireToken !== null) {
                        $pluginCache->set('token', date('Y-m-d H:i:s'), $expireToken);
                    }
                }

                // feed
                $feedCacheData = $pluginCache->get('feed');

                if ($feedCacheData !== null) {
                    $fromCache = true;
                    $feed = json_decode($feedCacheData, true);
                } else {
                    $feed = InstagramFeed::fetchInstagramFeed();
                    $pluginCache->set('feed', json_encode($feed), $options['expire']);
                }
            } else {
                InstagramFeed::refreshInstagramToken();
                $feed = InstagramFeed::fetchInstagramFeed();
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
            $item['media'] = null;
            $item['thumbnail'] = null;
            $item['thumbnail_url'] ??= null;
            $item['is_album'] = $item['media_type'] === 'CAROUSEL_ALBUM';
            $item['is_image'] = $item['media_type'] === 'IMAGE';
            $item['is_video'] = $item['media_type'] === 'VIDEO';

            if ($kirby->option('owebstudio.instagram-feed.store', false) === true) {
                if ($media = InstagramFeed::storeMediaUrl($item['media_url'], !$fromCache)) {
                    $item['media'] = $media;
                }

                if ($thumbnail = InstagramFeed::storeMediaUrl($item['thumbnail_url'], !$fromCache)) {
                    $item['thumbnail'] = $thumbnail;
                }
            }

            $collection->append(new Collection($item));
        }

        return $collection;
    }
];
