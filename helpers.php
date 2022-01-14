<?php

use Kirby\Http\Remote;

function fetchInstagramFeed(): array
{
    $options = option('clicktonext.instagram-feed');

    $url = 'https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username&limit=' . $options['limit'] . '&access_token=' . $options['accessToken'];
    $response = Remote::get($url)->json();

    if (empty($response) || empty($response['error']) === false) {
        if ($options['debug'] === true) {
            throw new Exception($response['error']['message'] ?? 'Fetching Instagram API failed!');
        }
    }

    return $response['data'] ?? [];
}

function refreshInstagramToken(): ?string
{
    $options = option('clicktonext.instagram-feed');

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
