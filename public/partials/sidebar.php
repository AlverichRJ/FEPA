<aside class="sidebar">
    <div class="ad-box"><?= renderBanner($sidebarTopBanner ?? null, '') ?></div>

    <section class="side-block">
        <h3>★ Notas populares</h3>
        <?php if (!empty($popular)): ?>
            <?php foreach ($popular as $index => $item): ?>
                <a class="side-item" href="<?= articleUrl($item['slug']) ?>"><span><?= $index + 1 ?></span><img src="<?= e($item['image']) ?>" alt=""><strong><?= e($item['title']) ?></strong><small><?= e($item['date']) ?></small></a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="side-empty">Aún no hay notas populares.</p>
        <?php endif; ?>
    </section>

    <section class="side-block">
        <h3>🔥 Notas virales</h3>
        <?php if (!empty($viral)): ?>
            <?php foreach ($viral as $item): ?>
                <a class="side-item" href="<?= articleUrl($item['slug']) ?>"><img src="<?= e($item['image']) ?>" alt=""><strong><?= e($item['title']) ?></strong><small><?= e($item['date']) ?></small></a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="side-empty">Aún no hay notas virales.</p>
        <?php endif; ?>
    </section>

    <div class="ad-box"><?= renderBanner($sidebarBottomBanner ?? null, '') ?></div>
</aside>
