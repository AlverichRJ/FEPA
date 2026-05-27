<?php
$app = require __DIR__ . '/../config/app.php';
date_default_timezone_set($app['timezone']);

$articles = [
    [
        'title' => 'Chequeos preventivos: clave para una vida larga',
        'slug' => 'chequeos-preventivos-clave-vida-larga',
        'category' => 'Salud Animal',
        'date' => '18 de mayo de 2025',
        'image' => 'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=900&q=80',
        'excerpt' => 'La prevención es la mejor herramienta para detectar a tiempo cualquier problema de salud.',
        'tags' => ['Prevención', 'Salud'],
    ],
    [
        'title' => 'Cómo manejar el estrés en gatos',
        'slug' => 'como-manejar-estres-gatos',
        'category' => 'Consejos',
        'date' => '17 de mayo de 2025',
        'image' => 'https://images.unsplash.com/photo-1571566882372-1598d88abd90?auto=format&fit=crop&w=900&q=80',
        'excerpt' => 'Técnicas efectivas para reducir el estrés y mejorar la convivencia con tu felino.',
        'tags' => ['Gatos', 'Comportamiento'],
    ],
    [
        'title' => 'Ejercicio diario: cuánto y cómo hacerlo bien',
        'slug' => 'ejercicio-diario-mascotas',
        'category' => 'Ejercicio',
        'date' => '16 de mayo de 2025',
        'image' => 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=900&q=80',
        'excerpt' => 'Cantidad de ejercicio recomendada según edad, raza y tamaño de tu perro.',
        'tags' => ['Perros', 'Bienestar'],
    ],
    [
        'title' => 'Problemas urinarios en gatos: causas y cuidados',
        'slug' => 'problemas-urinarios-gatos',
        'category' => 'Enfermedades',
        'date' => '15 de mayo de 2025',
        'image' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?auto=format&fit=crop&w=900&q=80',
        'excerpt' => 'Síntomas, causas comunes y cuándo acudir al veterinario.',
        'tags' => ['Gatos', 'Salud'],
    ],
    [
        'title' => 'Alimentación adecuada para conejos',
        'slug' => 'alimentacion-adecuada-conejos',
        'category' => 'Nutrición',
        'date' => '14 de mayo de 2025',
        'image' => 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=900&q=80',
        'excerpt' => 'Una dieta balanceada es esencial para la salud digestiva y dental.',
        'tags' => ['Conejos', 'Nutrición'],
    ],
    [
        'title' => 'Vacunas y prevención: protege su futuro',
        'slug' => 'vacunas-prevencion-mascotas',
        'category' => 'Vacunas',
        'date' => '13 de mayo de 2025',
        'image' => 'https://images.unsplash.com/photo-1581888227599-779811939961?auto=format&fit=crop&w=900&q=80',
        'excerpt' => 'Conoce el calendario de vacunas esencial para cachorros y adultos.',
        'tags' => ['Cachorros', 'Prevención'],
    ],
];

$popular = array_slice($articles, 0, 3);
$viral = array_slice(array_reverse($articles), 0, 3);
$route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$isArticle = str_starts_with($route, 'nota/');
$slug = $isArticle ? basename($route) : null;
$current = $articles[0];
foreach ($articles as $article) {
    if ($article['slug'] === $slug) {
        $current = $article;
        break;
    }
}

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function asset($path) { return '/assets/' . ltrim($path, '/'); }
function articleUrl($slug) { return '/nota/' . rawurlencode($slug); }

$pageTitle = $isArticle ? $current['title'] . ' | FEPA Veterinaria' : $app['seo']['default_title'];
$pageDescription = $isArticle ? $current['excerpt'] : $app['seo']['default_description'];
$pageImage = $isArticle ? $current['image'] : $app['seo']['default_og_image'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e(($app['base_url'] ?: '') . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:type" content="<?= $isArticle ? 'article' : 'website' ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image" content="<?= e($pageImage) ?>">
    <meta property="og:url" content="<?= e(($app['base_url'] ?: '') . $_SERVER['REQUEST_URI']) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
</head>
<body>
    <div class="top-ad"><span>PUBLICIDAD 728 x 90</span></div>
    <header class="site-header">
        <div class="container nav-shell">
            <a class="brand" href="/" aria-label="Inicio FEPA Veterinaria">
                <span class="brand-mark">🐾</span>
                <span><strong><?= e($app['app_name']) ?></strong><small><?= e($app['tagline']) ?></small></span>
            </a>
            <nav class="main-nav" aria-label="Menú principal">
                <a class="active" href="/">Inicio</a>
                <a href="/categoria/salud-animal">Salud Animal</a>
                <a href="/categoria/consejos">Consejos</a>
                <a href="/categoria/adopcion">Adopción</a>
                <a href="/categoria/nutricion">Nutrición</a>
                <a href="/categoria/urgencias">Urgencias</a>
            </nav>
            <button class="search-button" aria-label="Buscar">⌕</button>
        </div>
    </header>

    <?php if ($isArticle): ?>
        <main class="container article-layout">
            <article class="article-page">
                <div class="breadcrumb">Inicio / <?= e($current['category']) ?> / <?= e($current['title']) ?></div>
                <span class="category-pill"><?= e($current['category']) ?></span>
                <h1><?= e($current['title']) ?></h1>
                <p class="lead"><?= e($current['excerpt']) ?></p>
                <div class="article-meta">
                    <span>Por Dra. Laura Méndez</span><span><?= e($current['date']) ?></span><span>6 min de lectura</span>
                </div>
                <div class="share-row"><a href="#">Facebook</a><a href="#">Twitter</a><a href="#">Compartir</a></div>
                <img class="article-cover" src="<?= e($current['image']) ?>" alt="<?= e($current['title']) ?>" loading="lazy">
                <p>El bienestar de nuestras mascotas depende de pequeños hábitos diarios y decisiones informadas. En FEPA Veterinaria compartimos recomendaciones prácticas para mantener a perros, gatos y otras mascotas sanas, activas y felices.</p>
                <h2>Revisión preventiva</h2>
                <p>Las revisiones veterinarias periódicas permiten detectar a tiempo cambios en peso, piel, dientes, digestión o comportamiento. Una consulta preventiva puede evitar complicaciones y mejorar la calidad de vida de cada mascota.</p>
                <blockquote>La prevención es la mejor herramienta para garantizar una vida larga y saludable.</blockquote>
                <h2>Alimentación y rutina</h2>
                <p>Una alimentación balanceada, agua fresca, descanso suficiente y actividad física adecuada ayudan a fortalecer el sistema inmunológico. Cada especie, edad y condición requiere una rutina específica.</p>
                <div class="inline-ad">PUBLICIDAD</div>
                <h2>Señales de alerta</h2>
                <p>Presta atención a cambios en el apetito, energía, respiración, movilidad, sueño o comportamiento. Ante cualquier duda, consulta con un veterinario de confianza antes de automedicar.</p>
                <div class="tag-list"><?php foreach ($current['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div>
                <section class="related-section"><h2>Artículos relacionados</h2><div class="related-grid"><?php foreach (array_slice($articles, 1, 3) as $article): ?><a class="related-card" href="<?= articleUrl($article['slug']) ?>"><img src="<?= e($article['image']) ?>" alt="" loading="lazy"><strong><?= e($article['title']) ?></strong></a><?php endforeach; ?></div></section>
            </article>
            <?php include __DIR__ . '/partials/sidebar.php'; ?>
        </main>
    <?php else: ?>
        <main class="container home-layout">
            <section class="hero-grid">
                <a class="hero-card" href="<?= articleUrl($articles[0]['slug']) ?>">
                    <img src="<?= e($articles[0]['image']) ?>" alt="<?= e($articles[0]['title']) ?>">
                    <div class="hero-overlay"><span class="category-pill">Nota destacada</span><h1>Cuidados esenciales para tu mascota esta semana</h1><p>Consejos prácticos y recomendaciones de nuestros veterinarios para mantener a perros y gatos sanos, felices y protegidos.</p><button>Leer nota completa →</button></div>
                </a>
                <div class="hero-side"><?php foreach (array_slice($articles, 1, 3) as $article): ?><a class="mini-card" href="<?= articleUrl($article['slug']) ?>"><img src="<?= e($article['image']) ?>" alt=""><span><?= e($article['category']) ?></span><strong><?= e($article['title']) ?></strong><small><?= e($article['date']) ?></small></a><?php endforeach; ?></div>
            </section>
            <div class="content-with-sidebar">
                <section class="recent-section"><h2>Artículos recientes</h2><div class="article-grid"><?php foreach ($articles as $article): ?><a class="article-card" href="<?= articleUrl($article['slug']) ?>"><img src="<?= e($article['image']) ?>" alt="<?= e($article['title']) ?>" loading="lazy"><span class="category-pill"><?= e($article['category']) ?></span><h3><?= e($article['title']) ?></h3><small><?= e($article['date']) ?></small><p><?= e($article['excerpt']) ?></p><div class="tag-list"><?php foreach ($article['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div></a><?php endforeach; ?></div></section>
                <?php include __DIR__ . '/partials/sidebar.php'; ?>
            </div>
        </main>
    <?php endif; ?>

    <footer class="site-footer"><div class="container footer-shell"><a class="brand" href="/"><span class="brand-mark">🐾</span><span><strong><?= e($app['app_name']) ?></strong><small><?= e($app['tagline']) ?></small></span></a><div class="footer-social">f · ig · ▶</div><div><a href="/aviso-de-privacidad">Aviso de privacidad</a><small>© <?= date('Y') ?> FEPA Veterinaria. Todos los derechos reservados.</small></div></div></footer>
    <script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
