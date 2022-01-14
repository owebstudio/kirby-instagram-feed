<?php foreach (site()->instagramFeed() as $feed): ?>
    <a href="<?= $feed->permalink() ?>" target="_blank">
        <img title="<?= $feed->caption() ?>" src="<?= $feed->media_url() ?>">
    </a>
<?php endforeach ?>
