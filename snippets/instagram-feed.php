<?php foreach (site()->instagramFeed() as $feed): ?>
    <?php if ($feed->is_image() === true && $src = $feed->media_url()): ?>
        <a href="<?= $feed->permalink() ?>" target="_blank">
            <img title="<?= $feed->caption() ?>" src="<?= $src ?>">
        </a>
    <?php elseif ($feed->is_video() === true && $src = $feed->media_url()): ?>
        <a href="<?= $feed->permalink() ?>" target="_blank">
            <video width="100%" height="100%" autoplay preload muted loop poster="<?= $feed->thumbnail_url() ?>">
                <source src="<?= $src ?>" type="video/mp4">
            </video>
        </a>
    <?php elseif ($feed->is_album() === true && $children = $feed->children()): ?>
        <?php foreach ($children as $child): ?>
            <?php if ($child->is_image() === true && $src = $child->media_url()): ?>
                <a href="<?= $child->permalink() ?>" target="_blank">
                    <img title="<?= $child->caption() ?>" src="<?= $src ?>">
                </a>
            <?php elseif ($child->is_video() === true && $src = $child->media_url()): ?>
                <a href="<?= $child->permalink() ?>" target="_blank">
                    <video width="100%" height="100%" autoplay preload muted loop poster="<?= $child->thumbnail_url() ?>">
                        <source src="<?= $src ?>" type="video/mp4">
                    </video>
                </a>
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>
<?php endforeach ?>
