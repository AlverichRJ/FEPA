<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;

$app = require BASE_PATH . '/config/app.php';
date_default_timezone_set($app['timezone']);

$route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$route = $route === '' ? 'inicio' : $route;
$isArticle = str_starts_with($route, 'nota/');
$isCategory = str_starts_with($route, 'categoria/');
$slug = ($isArticle || $isCategory) ? basename($route) : null;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$categories = [];
$articles = [];
$popular = [];
$viral = [];
$featured = null;
$current = null;
$currentCategory = null;
$related = [];
$topBanner = null;
$sidebarTopBanner = null;
$sidebarBottomBanner = null;
$databaseError = null;

try {
    $categories = Category::active();
    $popular = Post::popular(3);
    $viral = Post::viral(3);
    $topBanner = Banner::activeByPlacement('top_728x90');
    $sidebarTopBanner = Banner::activeByPlacement('sidebar_top_300x250');
    $sidebarBottomBanner = Banner::activeByPlacement('sidebar_bottom_300x250');

    if ($isArticle && $slug) {
        $current = Post::findBySlug($slug);

        if ($current) {
            Post::incrementViews($current['id']);
            $related = Post::related($current['id'], $current['category_id'], 3);
        } else {
            http_response_code(404);
        }
    } elseif ($isCategory && $slug) {
        $currentCategory = Category::findBySlug($slug);

        if ($currentCategory) {
            $articles = Post::byCategory((int) $currentCategory['id'], $perPage, $offset);
            $featured = $articles[0] ?? null;
        } else {
            http_response_code(404);
        }
    } else {
        $featured = Post::featured();
        $articles = Post::latest($perPage, $offset);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    $databaseError = $exception->getMessage();
    error_log($exception->getMessage());
}

function renderBanner(?array $banner, string $fallback): string
{
    if (!$banner) {
        return '<span>' . $fallback . '</span>';
    }

    if ($banner['type'] === 'html' || $banner['type'] === 'adsense') {
        return (string) $banner['html_code'];
    }

    if ($banner['type'] === 'image' && !empty($banner['image_path'])) {
        $image = e($banner['image_path']);
        $name = e($banner['name']);
        $target = !empty($banner['target_url']) ? e($banner['target_url']) : '';
        $imageTag = '<img src="' . $image . '" alt="' . $name . '" loading="lazy">';

        return $target !== '' ? '<a href="' . $target . '" target="_blank" rel="noopener">' . $imageTag . '</a>' : $imageTag;
    }

    return '<span>' . e($fallback) . '</span>';
}

function renderArticleContent(string $content): string
{
    $trimmed = trim($content);

    if ($trimmed === '') {
        return '';
    }

    if ($trimmed !== strip_tags($trimmed)) {
        return $trimmed;
    }

    return '<p>' . nl2br(e($trimmed)) . '</p>';
}

$pageTitle = $app['seo']['default_title'];
$pageDescription = $app['seo']['default_description'];
$pageImage = $app['seo']['default_og_image'];

if ($isArticle && $current) {
    $pageTitle = $current['title'] . ' | FEPA Veterinaria';
    $pageDescription = $current['excerpt'];
    $pageImage = $current['image'];
} elseif ($isCategory && $currentCategory) {
    $pageTitle = $currentCategory['name'] . ' | FEPA Veterinaria';
    $pageDescription = $currentCategory['description'] ?: 'Notas de ' . $currentCategory['name'] . ' en FEPA Veterinaria.';
}

$canonicalUrl = rtrim($app['base_url'] ?: '', '/') . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="<?= $isArticle ? 'article' : 'website' ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image" content="<?= e($pageImage) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
</head>
<body>
    <div class="top-ad"><?= renderBanner($topBanner, 'PUBLICIDAD 728 x 90') ?></div>
    <header class="site-header">
        <div class="container nav-shell">
            <a class="brand" href="/" aria-label="Inicio FEPA Veterinaria">
                <span class="brand-mark">🐾</span>
                <span><strong><?= e($app['app_name']) ?></strong><small><?= e($app['tagline']) ?></small></span>
            </a>
            <nav class="main-nav" aria-label="Menú principal">
                <a class="<?= $route === 'inicio' ? 'active' : '' ?>" href="/">Inicio</a>
                <?php foreach ($categories as $category): ?>
                    <a class="<?= $isCategory && $slug === $category['slug'] ? 'active' : '' ?>" href="<?= categoryUrl($category['slug']) ?>"><?= e($category['name']) ?></a>
                <?php endforeach; ?>
            </nav>
            <button class="search-button" aria-label="Buscar">⌕</button>
        </div>
    </header>

    <?php if ($databaseError): ?>
        <main class="container home-layout">
            <section class="empty-state">
                <h1>No fue posible conectar con la base de datos</h1>
                <p>Verifica que MySQL esté activo y que el archivo <strong>.env</strong> local tenga las credenciales correctas de <strong>fepa_veterinaria</strong>.</p>
            </section>
        </main>
    <?php elseif ($isArticle): ?>
        <main class="container article-layout">
            <?php if (!$current): ?>
                <section class="empty-state"><h1>Nota no encontrada</h1><p>La nota solicitada no existe o todavía no está publicada.</p></section>
            <?php else: ?>
                <article class="article-page">
                    <div class="breadcrumb">Inicio / <?= e($current['category']) ?> / <?= e($current['title']) ?></div>
                    <span class="category-pill"><?= e($current['category']) ?></span>
                    <h1><?= e($current['title']) ?></h1>
                    <p class="lead"><?= e($current['excerpt']) ?></p>
                    <div class="article-meta">
                        <span>Por <?= e($current['author']) ?></span><span><?= e($current['date']) ?></span><span><?= e($current['reading_time']) ?> min de lectura</span>
                    </div>
                    <div class="share-row"><a href="#">Facebook</a><a href="#">Twitter</a><a href="#">Compartir</a></div>
                    <img class="article-cover" src="<?= e($current['image']) ?>" alt="<?= e($current['title']) ?>" loading="lazy">
                    <?= renderArticleContent($current['content']) ?>
                    <div class="inline-ad">PUBLICIDAD</div>
                    <?php if (!empty($current['tags'])): ?><div class="tag-list"><?php foreach ($current['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div><?php endif; ?>
                    <?php if ($related): ?><section class="related-section"><h2>Artículos relacionados</h2><div class="related-grid"><?php foreach ($related as $article): ?><a class="related-card" href="<?= articleUrl($article['slug']) ?>"><img src="<?= e($article['image']) ?>" alt="" loading="lazy"><strong><?= e($article['title']) ?></strong></a><?php endforeach; ?></div></section><?php endif; ?>
                </article>
                <?php include __DIR__ . '/partials/sidebar.php'; ?>
            <?php endif; ?>
        </main>
    <?php else: ?>
        <main class="container home-layout">
            <?php if ($isCategory && !$currentCategory): ?>
                <section class="empty-state"><h1>Categoría no encontrada</h1><p>La categoría solicitada no existe o no está activa.</p></section>
            <?php else: ?>
                <?php if ($isCategory && $currentCategory): ?>
                    <section class="section-heading"><h1><?= e($currentCategory['name']) ?></h1><p><?= e($currentCategory['description']) ?></p></section>
                <?php endif; ?>

                <?php if ($featured): ?>
                    <section class="hero-grid">
                        <a class="hero-card" href="<?= articleUrl($featured['slug']) ?>">
                            <img src="<?= e($featured['image']) ?>" alt="<?= e($featured['title']) ?>">
                            <div class="hero-overlay"><span class="category-pill">Nota destacada</span><h1><?= e($featured['title']) ?></h1><p><?= e($featured['excerpt']) ?></p><button>Leer nota completa →</button></div>
                        </a>
                        <div class="hero-side"><?php foreach (array_slice($articles, 0, 3) as $article): ?><a class="mini-card" href="<?= articleUrl($article['slug']) ?>"><img src="<?= e($article['image']) ?>" alt=""><span><?= e($article['category']) ?></span><strong><?= e($article['title']) ?></strong><small><?= e($article['date']) ?></small></a><?php endforeach; ?></div>
                    </section>
                <?php endif; ?>

                <div class="content-with-sidebar">
                    <section class="recent-section"><h2><?= $isCategory ? 'Notas de la categoría' : 'Artículos recientes' ?></h2>
                        <?php if (!$articles): ?>
                            <div class="empty-state"><h3>Todavía no hay notas publicadas</h3><p>Cuando se publiquen notas desde el panel administrativo aparecerán automáticamente en esta sección.</p></div>
                        <?php else: ?>
                            <div class="article-grid"><?php foreach ($articles as $article): ?><a class="article-card" href="<?= articleUrl($article['slug']) ?>"><img src="<?= e($article['image']) ?>" alt="<?= e($article['title']) ?>" loading="lazy"><span class="category-pill"><?= e($article['category']) ?></span><h3><?= e($article['title']) ?></h3><small><?= e($article['date']) ?></small><p><?= e($article['excerpt']) ?></p><?php if (!empty($article['tags'])): ?><div class="tag-list"><?php foreach ($article['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div><?php endif; ?></a><?php endforeach; ?></div>
                        <?php endif; ?>
                    </section>
                    <?php include __DIR__ . '/partials/sidebar.php'; ?>
                </div>
            <?php endif; ?>
        </main>
    <?php endif; ?>

    <footer class="site-footer"><div class="container footer-shell"><a class="brand" href="/"><span class="brand-mark">🐾</span><span><strong><?= e($app['app_name']) ?></strong><small><?= e($app['tagline']) ?></small></span></a><div class="footer-social">f · ig · ▶</div><div><a href="/aviso-de-privacidad">Aviso de privacidad</a><small>© <?= date('Y') ?> FEPA Veterinaria. Todos los derechos reservados.</small></div></div></footer>
    <script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
