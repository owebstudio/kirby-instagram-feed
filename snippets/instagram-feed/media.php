<?php

/**
 * @var \Kirby\Toolkit\Collection $media An image or video of the post
 * @var \Kirby\Toolkit\Collection $post The post (the album for album media)
 */

// stored media files are served from the own server only
if (option('owebstudio.instagram-feed.store') === true) {
    $src = $media->media()?->url();
    $poster = $media->thumbnail()?->url();
} else {
    $src = $media->media_url();
    $poster = $media->thumbnail_url();
}
?>
<?php if ($media->is_image() === true && empty($src) === false): ?>
    <a href="<?= esc($media->permalink() ?? '') ?>" target="_blank" rel="noopener">
        <img src="<?= esc($src) ?>" alt="<?= esc($post->caption() ?? '') ?>" loading="lazy">
    </a>
<?php elseif ($media->is_video() === true && empty($src) === false): ?>
    <a href="<?= esc($media->permalink() ?? '') ?>" target="_blank" rel="noopener">
        <video width="100%" height="100%" autoplay preload muted loop playsinline<?php if (empty($poster) === false): ?> poster="<?= esc($poster) ?>"<?php endif ?>>
            <source src="<?= esc($src) ?>" type="video/mp4">
        </video>
    </a>
<?php endif ?>
