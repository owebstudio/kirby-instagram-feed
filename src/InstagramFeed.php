<?php

namespace Oweb;

use Exception;
use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Toolkit\Str;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

class InstagramFeed
{
    /**
     * @param string|null $uri
     * @return Asset
     */
    public static function mediaAsset(string $uri = null): Asset
    {
        return new Asset(static::mediaPrefix($uri));
    }

    /**
     * @param string|null $uri
     * @return string
     */
    public static function mediaPrefix(string $uri = null): string
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
     * @return array
     * @throws \Kirby\Exception\InvalidArgumentException
     */
    public static function fetchInstagramFeed(): array
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

    public static function refreshInstagramToken(): ?string
    {
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
            return round(((int)$response['expires_in'] - (86400 * 7)) / 60);
        }

        return null;
    }

    /**
     * Checks plugin api cache expiring
     *
     * @return bool
     * @throws \Kirby\Exception\InvalidArgumentException
     */
    public static function isCacheExpired(): bool
    {
        $kirby = Kirby::instance();

        if ($kirby->option('owebstudio.instagram-feed.cache', false) === true) {
            if ($pluginCache = $kirby->cache('owebstudio.instagram-feed')) {
                return $pluginCache->get('feed') === null;
            }
        }

        return false;
    }

    /**
     * Removes current page cache
     *
     * @return void
     * @throws \Kirby\Exception\InvalidArgumentException
     */
    public static function flushPagesCache(): void
    {
        $kirby = Kirby::instance();

        if (
            $kirby->option('cache.pages.active', false) === true &&
            $cache = $kirby->cache('pages')
        ) {
            // flush all pages cache
            $cache->flush();

            // refresh instagram feed
            static::fetchInstagramFeed();
        }
    }

    /**
     * @param string|null $url
     * @param bool $overwrite
     * @return Asset|null
     * @throws Exception
     */
    public static function storeMediaUrl(string $url = null, bool $overwrite = false): ?Asset
    {
        if (empty($url) === true) {
            return null;
        }

        $kirby = Kirby::instance();

        $filename = basename(parse_url($url, PHP_URL_PATH));
        $directory = 'plugins/owebstudio/instagram-feed';
        $relativeFilepath = $directory . '/' . $filename;

        $mediaPath = $kirby->root('media') . '/' . $directory;
        $filePath = $mediaPath . '/' . $filename;

        Dir::make($mediaPath);

        if ($overwrite === false && F::exists($filePath) === true) {
            return InstagramFeed::mediaAsset($relativeFilepath);
        }

        if ($remoteMedia = F::read($url)) {
            F::write($filePath, $remoteMedia);

            return InstagramFeed::mediaAsset($relativeFilepath);
        }

        return null;
    }
}
