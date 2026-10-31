<?php

/**
 * @var int|null $limit Maximum number of posts
 * @var array|null $types Types of posts to show: `image`, `video` and `album`
 */
$types ??= ['image', 'video', 'album'];
$posts = site()->instagramFeed()->filter(fn ($post) =>
    ($post->is_image() === true && in_array('image', $types, true) === true) ||
    ($post->is_video() === true && in_array('video', $types, true) === true) ||
    ($post->is_album() === true && in_array('album', $types, true) === true)
);

if (empty($limit) === false) {
    $posts = $posts->limit((int)$limit);
}
?>
<?php foreach ($posts as $post): ?>
    <?php foreach ($post->is_album() === true ? $post->children() ?? [] : [$post] as $media): ?>
        <?php snippet('instagram-feed/media', ['media' => $media, 'post' => $post]) ?>
    <?php endforeach ?>
<?php endforeach ?>
