<?php

use Kirby\Cms\App as Kirby;
use Kirby\Toolkit\Collection;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

return [
    'instagramFeed' => function () {
        $fromCache = false;
        $options = Kirby::instance()->option('owebstudio.instagram-feed');

        try {
            if (empty($options['accessToken']) === true) {
                throw new Exception('Instagram API access token required!');
            }

            // check check
            if ($options['cache'] === true) {
                $pluginCache = Kirby::instance()->cache('owebstudio.instagram-feed');

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
                    $fromCache = true;
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
            $item['asset'] = null;

            if (Kirby::instance()->option('owebstudio.instagram-feed.store', false) === true) {
                $instagramUrl = $item['media_url'];

                $filename = basename(parse_url($instagramUrl, PHP_URL_PATH));
                $directory = 'plugins/owebstudio/instagram-feed';
                $relativeFilepath = $directory . '/' . $filename;

                $mediaPath = Kirby::instance()->root('media') . '/' . $directory;
                $filePath = $mediaPath . '/' . $filename;

                Dir::make($mediaPath);

                if ($fromCache === true) {
                    if (F::exists($mediaPath) === false) {
                        if ($remoteMedia = F::read($instagramUrl)) {
                            F::write($filePath, $remoteMedia);

                            if ($asset = asset(mediaPrefix($relativeFilepath))) {
                                $item['asset'] = $asset;
                            }
                        }
                    }
                } else {
                    // if cache disabled, overwrite always
                    if ($remoteMedia = F::read($instagramUrl)) {
                        F::write($filePath, $remoteMedia);

                        if ($asset = asset(mediaPrefix($relativeFilepath) )) {
                            $item['asset'] = $asset;
                        }
                    }
                }
            }

            $collection->append(new Collection($item));
        }

        return $collection;
    }
];
