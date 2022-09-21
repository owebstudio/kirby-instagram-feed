<?php

namespace Oweb;

use Exception;
use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Toolkit\Str;
use Kirby\Filesystem\Asset;

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
                $tokenFeedData = $pluginCache->get('feed');
                return $tokenFeedData === null;
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
    public static function flushPageCache(): void
    {
        $kirby = Kirby::instance();

        if (
            $kirby->option('cache.pages.active', false) === true &&
            $cache = $kirby->cache('pages')
        ) {
            $page = $kirby->site()->page();

            if ($page && $page->isCacheable() === true) {
                $cacheId = static::cacheId($page);

                if ($cache->exists($cacheId) === true) {
                    $cache->remove($cacheId);
                }
            }
        }
    }

    /**
     * Get page cache id
     *
     * @param Page $page
     * @return string
     */
    public static function cacheId(Page $page): string
    {
        $cacheId = [$page->id()];

        if ($page->kirby()->multilang() === true) {
            $cacheId[] = $page->kirby()->language()->code();
        }

        $cacheId[] = 'html';

        return implode('.', $cacheId);
    }
}
