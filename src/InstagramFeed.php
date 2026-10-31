<?php

namespace Oweb;

use Exception;
use Kirby\Cms\App as Kirby;
use Kirby\Http\Remote;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\Str;
use Oweb\InstagramFeed\License\License;

class InstagramFeed
{
    /**
     * Folder for stored media files inside the media folder
     */
    public const MEDIA = 'instagram-feed';

    /**
     * File extensions of media files that can be stored
     */
    public const EXTENSIONS = ['gif', 'jpeg', 'jpg', 'mov', 'mp4', 'png', 'webp'];

    public static function mediaAsset(?string $uri = null): Asset
    {
        return new Asset(static::mediaPrefix($uri));
    }

    public static function mediaPrefix(?string $uri = null): string
    {
        $index = Kirby::instance()->url();
        $media = Kirby::instance()->url('media');

        if (Str::startsWith($media, $index) === true) {
            $media = Str::after($media, $index);
        } else {
            // media URL is outside of the site, we can't make routing work;
            // fall back to the standard media route
            $media = 'media';
        }

        $media = ltrim($media, '/');

        if ($uri !== null) {
            $media = $media . '/' . trim($uri, '/');
        }

        return $media;
    }

    public static function fetchFeed(): array
    {
        $options = Kirby::instance()->option('owebstudio.instagram-feed');

        $fields = 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username';
        $url = 'https://graph.instagram.com/me/media?fields=' . $fields . '&limit=' . $options['limit'] . '&access_token=' . $options['accessToken'];
        $response = Remote::get($url)->json();

        if (empty($response) || empty($response['error']) === false) {
            if ($options['debug'] === true) {
                throw new Exception($response['error']['message'] ?? 'Fetching Instagram API failed!');
            }
        }

        return $response['data'] ?? [];
    }

    public static function fetchMediaChildren($id): array
    {
        $options = Kirby::instance()->option('owebstudio.instagram-feed');

        $fields = 'id,media_type,media_url,permalink,thumbnail_url,timestamp,username';
        $url = 'https://graph.instagram.com/' . $id . '/children?fields=' . $fields . '&access_token=' . $options['accessToken'];
        $response = Remote::get($url)->json();

        if (empty($response) || empty($response['error']) === false) {
            if ($options['debug'] === true) {
                throw new Exception($response['error']['message'] ?? 'Fetching Instagram API failed!');
            }
        }

        return $response['data'] ?? [];
    }

    public static function refreshToken(): ?int
    {
        // live sites need a license key to renew the token
        if (License::for('owebstudio/instagram-feed')?->isLocked() === true) {
            return null;
        }

        $options = Kirby::instance()->option('owebstudio.instagram-feed');

        $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . $options['accessToken'];
        $response = Remote::get($url)->json();

        if (empty($response) || empty($response['error']) === false) {
            if ($options['debug'] === true) {
                throw new Exception($response['error']['message'] ?? 'Fetching Instagram API failed!');
            }
        }

        // renew token expire 1 week before
        if (empty($response['expires_in']) === false) {
            // return as minutes for cache
            return (int)round(((int)$response['expires_in'] - (86400 * 7)) / 60);
        }

        return null;
    }

    /**
     * Returns the stored file for a media URL of the API
     * (the file might not exist yet)
     */
    public static function mediaFile(?string $url = null): ?Asset
    {
        $path = empty($url) === false ? parse_url($url, PHP_URL_PATH) : null;

        if (empty($path) === true) {
            return null;
        }

        $filename = F::safeName(basename($path));

        if (in_array(F::extension($filename), static::EXTENSIONS, true) === false) {
            return null;
        }

        return static::mediaAsset(static::MEDIA . '/' . $filename);
    }

    /**
     * Downloads a media file unless it has been stored before
     */
    public static function storeMediaUrl(?string $url = null): ?Asset
    {
        if (!$asset = static::mediaFile($url)) {
            return null;
        }

        if ($asset->exists() === true) {
            return $asset;
        }

        try {
            $response = Remote::get($url, ['timeout' => 60]);

            if ($response->code() === 200 && $response->content() !== '') {
                F::write($asset->root(), $response->content());

                return $asset;
            }
        } catch (Exception $e) {
            if (Kirby::instance()->option('owebstudio.instagram-feed.debug') === true) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Default values for every requested API field
     *
     * The API omits fields it has no value for, e.g. `caption` for media
     * without a caption or `media_url` for media that has been flagged for
     * a copyright violation.
     */
    public const FIELDS = [
        'id' => null,
        'caption' => null,
        'media_type' => null,
        'media_url' => null,
        'permalink' => null,
        'thumbnail_url' => null,
        'timestamp' => null,
        'username' => null
    ];

    public static function feedToCollection(array $data = [], bool $cache = false, ?array $parent = null): Collection
    {
        $kirby = Kirby::instance();
        $options = $kirby->option('owebstudio.instagram-feed');
        $collection = new Collection();

        foreach ($data as $index => $item) {
            $item = array_merge(static::FIELDS, $item);

            $item['media'] = null;
            $item['thumbnail'] = null;
            $item['is_album'] = $item['media_type'] === 'CAROUSEL_ALBUM';
            $item['is_image'] = $item['media_type'] === 'IMAGE';
            $item['is_video'] = $item['media_type'] === 'VIDEO';
            $item['children'] = null;

            // fix album permalink
            if ($parent !== null) {
                $item['permalink'] = $parent['permalink'] . '?img_index=' . ($index + 1);
            }

            if ($item['is_album'] === true && empty($item['id']) === false) {
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
                    $feedCacheData = $pluginCache->get('feed.' . $item['id']);

                    if ($feedCacheData !== null) {
                        $children = json_decode($feedCacheData, true) ?? [];
                    } else {
                        $children = InstagramFeed::fetchMediaChildren($item['id']);
                        $pluginCache->set('feed.' . $item['id'], json_encode($children), $options['expire']);
                    }
                } else {
                    $children = InstagramFeed::fetchMediaChildren($item['id']);
                }

                $item['children'] = InstagramFeed::feedToCollection($children, $cache, $item);
            } else if ($kirby->option('owebstudio.instagram-feed.store', false) === true) {
                if ($media = InstagramFeed::storeMediaUrl($item['media_url'])) {
                    $item['media'] = $media;
                }

                if ($thumbnail = InstagramFeed::storeMediaUrl($item['thumbnail_url'])) {
                    $item['thumbnail'] = $thumbnail;
                }
            }

            $collection->append(new Collection($item));
        }

        return $collection;
    }
}
