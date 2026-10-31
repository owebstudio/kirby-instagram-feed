<?php

namespace Oweb;

use Exception;
use Kirby\Cms\App as Kirby;
use Kirby\Data\Data;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\F;
use Kirby\Http\Remote;
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

    /**
     * Minutes until a failed request to the Instagram API is tried again
     */
    public const RETRY = 60;

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

    public static function options(): array
    {
        return Kirby::instance()->option('owebstudio.instagram-feed');
    }

    /**
     * Returns the feed, from the cache if possible
     */
    public static function feed(): Collection
    {
        $kirby = Kirby::instance();
        $options = static::options();

        try {
            if (empty($options['accessToken']) === true) {
                throw new Exception('Instagram API access token required!');
            }

            if ($options['cache'] === true) {
                $pluginCache = $kirby->cache('owebstudio.instagram-feed');
                $feed = $pluginCache->get('feed');

                if (is_array($feed) === false) {
                    static::refresh();
                    $feed = $pluginCache->get('feed', []);
                }

                // cached pages with the feed expire together with the feed
                if ($expires = $pluginCache->expires('feed')) {
                    $kirby->response()->expires($expires);
                }
            } else {
                static::refreshToken();
                $feed = static::fetchFeed() ?? [];
            }
        } catch (Exception $e) {
            if ($options['debug'] === true) {
                throw $e;
            }

            $feed = [];
        }

        return static::feedToCollection($feed);
    }

    /**
     * Fetches the feed and caches it. If the Instagram API fails,
     * the last feed stays in the cache and the API is tried again later.
     *
     * @return array|null The new feed or `null` if fetching failed
     */
    public static function refresh(): ?array
    {
        $options = static::options();
        $pluginCache = Kirby::instance()->cache('owebstudio.instagram-feed');

        static::refreshToken();

        if (($feed = static::fetchFeed()) !== null) {
            $pluginCache->set('feed', $feed, $options['expire']);
            $pluginCache->set('last', $feed);

            return $feed;
        }

        $last = $pluginCache->get('last');
        $pluginCache->set('feed', is_array($last) ? $last : [], min($options['expire'], static::RETRY));

        return null;
    }

    /**
     * Sends a request to the Instagram API
     *
     * @return array|null The response or `null` for errors
     */
    public static function request(string $path, array $query = []): ?array
    {
        $options = static::options();
        $query['access_token'] = static::token();

        try {
            $response = Remote::get('https://graph.instagram.com/' . $path . '?' . http_build_query($query))->json();

            if (empty($response) === true || empty($response['error']) === false) {
                throw new Exception($response['error']['message'] ?? 'Fetching Instagram API failed!');
            }
        } catch (Exception $e) {
            if ($options['debug'] === true) {
                throw $e;
            }

            return null;
        }

        return $response;
    }

    /**
     * Fetches the latest media, with the children of albums
     *
     * @return array|null The feed or `null` if fetching failed
     */
    public static function fetchFeed(): ?array
    {
        $options = static::options();

        $response = static::request('me/media', [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
            'limit' => $options['limit']
        ]);

        if ($response === null) {
            return null;
        }

        $feed = $response['data'] ?? [];

        foreach ($feed as $index => $item) {
            if (($item['media_type'] ?? null) === 'CAROUSEL_ALBUM' && empty($item['id']) === false) {
                $feed[$index]['children'] = static::fetchMediaChildren($item['id']);
            }
        }

        if ($options['store'] === true) {
            static::storeFeed($feed);
        }

        return $feed;
    }

    public static function fetchMediaChildren(string $id): array
    {
        $response = static::request($id . '/children', [
            'fields' => 'id,media_type,media_url,permalink,thumbnail_url,timestamp,username'
        ]);

        return $response['data'] ?? [];
    }

    /**
     * Returns the access token, the renewed one if there is one
     */
    public static function token(): ?string
    {
        return static::tokenData()['token'] ?? static::options()['accessToken'];
    }

    /**
     * File that keeps the renewed access token
     */
    public static function tokenFile(): string
    {
        return Kirby::instance()->root('config') . '/.instagram-feed-token';
    }

    /**
     * Returns the stored data of the renewed access token,
     * as long as the token in the config has not changed
     */
    public static function tokenData(): array
    {
        $data = Data::read(static::tokenFile(), 'json', fail: false);

        if (($data['source'] ?? null) !== static::tokenHash()) {
            return [];
        }

        return $data;
    }

    protected static function tokenHash(): string
    {
        return hash('sha256', (string)static::options()['accessToken']);
    }

    /**
     * Renews the access token one week before it expires
     */
    public static function refreshToken(): void
    {
        // live sites need a license key to renew the token
        if (License::for('owebstudio/instagram-feed')?->isLocked() === true) {
            return;
        }

        $data = static::tokenData();
        $now = time();

        if (($data['expires'] ?? 0) - (86400 * 7) > $now) {
            return;
        }

        // the last attempt failed, e.g. because the token is not 24 hours old yet
        if (($data['failed'] ?? 0) + (static::RETRY * 60) > $now) {
            return;
        }

        $response = static::request('refresh_access_token', [
            'grant_type' => 'ig_refresh_token'
        ]);

        if (empty($response['access_token']) === false && empty($response['expires_in']) === false) {
            $data = [
                'token' => $response['access_token'],
                'expires' => $now + (int)$response['expires_in'],
                'refreshed' => $now
            ];
        } else {
            $data['failed'] = $now;
        }

        $data['source'] = static::tokenHash();

        try {
            Data::write(static::tokenFile(), $data, 'json');
        } catch (Exception $e) {
            if (static::options()['debug'] === true) {
                throw $e;
            }
        }
    }

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
            if (static::options()['debug'] === true) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Stores the media files of the feed
     */
    public static function storeFeed(array $feed): void
    {
        foreach ($feed as $item) {
            foreach ([$item, ...$item['children'] ?? []] as $media) {
                foreach (['media_url', 'thumbnail_url'] as $field) {
                    static::storeMediaUrl($media[$field] ?? null);
                }
            }
        }
    }

    /**
     * Returns the stored file for a media URL if it exists
     */
    public static function storedMedia(?string $url = null): ?Asset
    {
        $asset = static::mediaFile($url);

        return $asset?->exists() === true ? $asset : null;
    }

    public static function feedToCollection(array $data = [], ?array $parent = null): Collection
    {
        $store = static::options()['store'] === true;
        $collection = new Collection();

        foreach ($data as $index => $item) {
            $item = array_merge(static::FIELDS, $item);

            $item['is_album'] = $item['media_type'] === 'CAROUSEL_ALBUM';
            $item['is_image'] = $item['media_type'] === 'IMAGE';
            $item['is_video'] = $item['media_type'] === 'VIDEO';
            $item['media'] = $store === true ? static::storedMedia($item['media_url']) : null;
            $item['thumbnail'] = $store === true ? static::storedMedia($item['thumbnail_url']) : null;

            // fix album permalink
            if ($parent !== null) {
                $item['permalink'] = $parent['permalink'] . '?img_index=' . ($index + 1);
            }

            $item['children'] = $item['is_album'] === true ? static::feedToCollection($item['children'] ?? [], $item) : null;

            $collection->append(new Collection($item));
        }

        return $collection;
    }
}
