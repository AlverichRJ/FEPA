<aside class="sidebar">
    <div class="ad-box">PUBLICIDAD<br>300 x 250</div>
    <section class="side-block"><h3>★ Notas populares</h3><?php foreach ($popular as $index => $item): ?><a class="side-item" href="<?= articleUrl($item['slug']) ?>"><span><?= $index + 1 ?></span><img src="<?= e($item['image']) ?>" alt=""><strong><?= e($item['title']) ?></strong><small><?= e($item['date']) ?></small></a><?php endforeach; ?></section>
    <section class="side-block"><h3>🔥 Notas virales</h3><?php foreach ($viral as $item): ?><a class="side-item" href="<?= articleUrl($item['slug']) ?>"><img src="<?= e($item['image']) ?>" alt=""><strong><?= e($item['title']) ?></strong><small><?= e($item['date']) ?></small></a><?php endforeach; ?></section>
    <div class="ad-box">PUBLICIDAD<br>300 x 250</div>
</aside>
