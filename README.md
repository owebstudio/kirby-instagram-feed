# Kirby Instagram Feed

Instagram feed on your website for Kirby 3, 4 & 5 (PHP 8.0 – 8.4).

⚠️ Client must have a business professional/creator account for this to work

## Instagram API

This plugin powered by the
new [Instagram API](https://developers.facebook.com/docs/instagram-platform).

### Rate Limits

There is an Instagram API
request
limit ([Instagram API rate limits](https://developers.facebook.com/docs/graph-api/overview/rate-limiting/#platform-rate-limits))
. Don't worry! The plugin caches it periodically for the time
you set from config.

If Kirby's [pages cache](https://getkirby.com/docs/guide/cache) is active, pages that show the feed are cached until the
feed expires, so they always show a fresh feed.

### Expire Token

Instagram access token expires after 2 months (60 days) by default. The plugin automatically refresh the token. If cache
is enabled the plugin will do it 1 week before, otherwise it will do it on every request and this is not recommended. So
you don't need to do anything.

## Installation

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

## Usage

You can use sample basic snippet of plugin:

```php
<?php snippet('instagram-feed') ?>
```

If you want to customize it, you can look the example usage below:

```php
<?php foreach (site()->instagramFeed() as $feed): ?>
    <?php if ($src = $feed->media_url()): ?>
        <a href="<?= $feed->permalink() ?>" target="_blank">
            <img title="<?= $feed->caption() ?>" src="<?= $src ?>">
        </a>
    <?php endif ?>
<?php endforeach ?>
```

### Available `$feed` methods

- $feed->id()
- $feed->caption()
- $feed->is_album()
- $feed->is_image()
- $feed->is_video()
- $feed->media() (Only available with `store` enabled option)
- $feed->media_type() (The Media's type. Can be `IMAGE`, `VIDEO` or `CAROUSEL_ALBUM`)
- $feed->media_url() (Can be `null`. Instagram omits it for albums without a
  cover and for media flagged for a copyright violation, so always check it
  before rendering)
- $feed->permalink()
- $feed->thumbnail() (Only available with `store` enabled option)
- $feed->thumbnail_url()
- $feed->timestamp()
- $feed->username()
- $feed->children() (Only available for the `CAROUSEL_ALBUM` media type)

Also `site()->instagramFeed()` and all items (`$feed`) in it are collection objects and have all collection methods.

### Showing album feeds

If the feed item is an album media type, it does not have a media element/url. Album media type is a collection of
multiple media. For this you should call the `$feed->children()` method. This method returns a media collection. Here is
an example of usage:

```php
<?php if ($feed->is_album() === true && $children = $feed->children()): ?>
    <?php foreach ($children as $child): ?>
        <?php if ($child->is_image() === true): ?>
            <a href="<?= $child->permalink() ?>" target="_blank">
                <img title="<?= $child->caption() ?>" src="<?= $child->media_url() ?>">
            </a>
        <?php elseif ($child->is_video() === true): ?>
            <a href="<?= $child->permalink() ?>" target="_blank">
                <video width="100%" height="100%" autoplay preload muted loop>
                    <source src="<?= $child->media_url() ?>" type="video/mp4">
                </video>
            </a>
        <?php endif ?>
    <?php endforeach ?>
<?php endif ?>
```

## Options

The default values of the package are:

| Option      | Type    | Default | Description                                                                                                                               |
|:------------|:--------|:--------|:------------------------------------------------------------------------------------------------------------------------------------------|
| accessToken | string  | null    | Access token for Instagram Basic Display API.                                                                                             |
| cache       | boolean | true    | Enable/disable caching feed and refresh token request. Highly recommended that the cache be enabled to avoid exceeding api request limit. |
| store       | boolean | false   | Enable/disable storing images or videos for this plugin.                                                                                  |
| debug       | boolean | false   | Enable/disable debugging for this plugin.                                                                                                 |
| expire      | int     | 1440    | Sets how long the feed items are cached as minutes. Default is one day.                                                                   |
| limit       | int     | 20      | Sets how many feed items are fetch from API.                                                                                              |

All the values can be updated in the `config.php` file and prefixed with `owebstudio.instagram-feed.`

## Sample options

```php
<?php

// /site/config/config.php
return [
    'owebstudio.instagram-feed' => [
        'accessToken' => 'INSTAGRAM_ACCESS_TOKEN',
        'cache' => true,
        'store' => true,
        'debug' => false,
        'expire' => 180, // 3 hours
        'limit' => 10 // fetch limit 
    ]
];
```

## Creating Instagram token

Follow the steps below to create a new Instagram token. Remember, client must have a business professional/creator account for this to work

1. Login to https://developers.facebook.com/
2. Create a new App
3. Make an App name
4. Choose "Other" for use case
5. Choose "Business" for app type
6. Confirm "Create App"
7. Add Instagram product to your App
8. Add client Instagram account to the Instagram API
9. Generate a new token
10. Add this token to the site/config.php or other config environment file
11. Delete media folder and site/cache
