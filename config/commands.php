<?php

use Oweb\InstagramFeed;

return [
    'instagram-feed:refresh' => [
        'description' => 'Fetches the Instagram feed and renews the access token when needed',
        'args' => [],
        'command' => static function ($cli): void {
            if (empty(InstagramFeed::options()['accessToken']) === true) {
                throw new Exception('Instagram API access token required!');
            }

            $feed = InstagramFeed::refresh();

            if ($feed === null) {
                throw new Exception('Fetching the Instagram feed failed. Enable the debug option to see the error.');
            }

            $cli->success('The Instagram feed has been refreshed (' . count($feed) . ' posts)');
        }
    ]
];
