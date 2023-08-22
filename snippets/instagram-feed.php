<?php snippet('header') ?>

<?php // snippet('instagram-feed') ?>

<?php foreach (site()->instagramFeed() as $feed): ?>
    <?php if ($feed->is_image() === true): ?>
        <div class="ig_post">
            <a href="<?= $feed->permalink() ?>" target="_blank">
                <div class="ig_tag">
                    <img src="assets/icons/instagram.svg" alt="Instagram" style="width: 24px">
                    <?php
                    $time = strtotime($feed->timestamp());
                    echo '' . date('j F, Y', $time) . '';
                    ?>
                </div>
                <?= $feed->media() ?>
                <img title="<?= $feed->caption() ?>" src="<?= $feed->media_url() ?>">
            </a>
        </div>
    <?php elseif ($feed->is_video() === true): ?>
        <div class="ig_post">
            <a href="<?= $feed->permalink() ?>" target="_blank">
                <div class="ig_tag">
                    <img src="assets/icons/instagram.svg" alt="Instagram" style="width: 24px">
                    <?php
                    $time = strtotime($feed->timestamp());
                    echo '' . date('j F, Y', $time) . '';
                    ?>
                </div>
                <video width="100%" height="100%" autoplay preload muted loop>
                    <source src="<?= $feed->media_url() ?>" type="video/mp4">
                </video>
            </a>
        </div>
    <?php elseif ($feed->is_album() === true && $children = $feed->children()): ?>
        <?php foreach ($children as $feed): ?>
            <?php if ($feed->is_image() === true): ?>
                <div class="ig_post">
                    <a href="<?= $feed->permalink() ?>" target="_blank">
                        <div class="ig_tag">
                            <img src="assets/icons/instagram.svg" alt="Instagram" style="width: 24px">
                            <?php
                            $time = strtotime($feed->timestamp());
                            echo '' . date('j F, Y', $time) . '';
                            ?>
                        </div>
                        <?= $feed->media() ?>
                        <img title="<?= $feed->caption() ?>" src="<?= $feed->media_url() ?>">
                    </a>
                </div>
            <?php elseif ($feed->is_video() === true): ?>
                <div class="ig_post">
                    <a href="<?= $feed->permalink() ?>" target="_blank">
                        <div class="ig_tag">
                            <img src="assets/icons/instagram.svg" alt="Instagram" style="width: 24px">
                            <?php
                            $time = strtotime($feed->timestamp());
                            echo '' . date('j F, Y', $time) . '';
                            ?>
                        </div>

                        <video width="100%" height="100%" autoplay preload muted loop>
                            <source src="<?= $feed->media_url() ?>" type="video/mp4">
                        </video>
                    </a>
                </div>
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>
<?php endforeach ?>

<?php snippet('footer') ?>
