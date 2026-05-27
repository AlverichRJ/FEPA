<?php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\AdminContent;

$app = require BASE_PATH . '/config/app.php';
date_default_timezone_set($app['timezone']);
Auth::start();

$section = $_GET['section'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$notice = $_SESSION['admin_notice'] ?? null;
$error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_notice'], $_SESSION['admin_error']);

function adminRedirect(string $section = 'dashboard', string $action = 'index', ?int $id = null): void
{
    $url = '/admin/?section=' . rawurlencode($section);
    if ($action !== 'index') {
        $url .= '&action=' . rawurlencode($action);
    }
    if ($id !== null) {
        $url .= '&id=' . $id;
    }
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'notice'): void
{
    $_SESSION[$type === 'error' ? 'admin_error' : 'admin_notice'] = $message;
}

function requireCsrf(): void
{
    if (!Csrf::validate($_POST['_csrf'] ?? null)) {
        flash('La sesión del formulario expiró. Intenta nuevamente.', 'error');
        adminRedirect();
    }
}

function activeNav(string $current, string $target): string
{
    return $current === $target ? 'active' : '';
}

function value($source, string $key, string $default = ''): string
{
    return e($source[$key] ?? $default);
}

try {
    $hasUsers = Auth::hasUsers();
} catch (Throwable $exception) {
    $hasUsers = true;
    $error = 'No fue posible conectar con la base de datos. Revisa tu archivo .env y que MySQL esté activo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['_form'] ?? '';

    try {
        if ($form === 'first_admin') {
            requireCsrf();
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                throw new InvalidArgumentException('Captura nombre, correo válido y contraseña mínima de 8 caracteres.');
            }

            $adminId = Auth::createFirstAdmin($name, $email, $password);
            Auth::login($email, $password);
            flash('Administrador inicial creado correctamente.');
            adminRedirect();
        }

        if ($form === 'login') {
            requireCsrf();
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (!Auth::login($email, $password)) {
                throw new InvalidArgumentException('Correo o contraseña incorrectos.');
            }

            flash('Sesión iniciada correctamente.');
            adminRedirect();
        }

        if ($form === 'logout') {
            requireCsrf();
            Auth::logout();
            header('Location: /admin/');
            exit;
        }

        $user = Auth::user();
        if (!$user) {
            throw new RuntimeException('Debes iniciar sesión para realizar esta acción.');
        }

        requireCsrf();

        if ($form === 'post_save') {
            AdminContent::savePost($_POST, (int) $user['id']);
            flash('La nota se guardó correctamente.');
            adminRedirect('posts');
        }

        if ($form === 'post_delete') {
            AdminContent::deletePost((int) ($_POST['id'] ?? 0));
            flash('La nota fue eliminada.');
            adminRedirect('posts');
        }

        if ($form === 'category_save') {
            AdminContent::saveCategory($_POST);
            flash('La categoría se guardó correctamente.');
            adminRedirect('categories');
        }

        if ($form === 'category_delete') {
            AdminContent::deleteCategory((int) ($_POST['id'] ?? 0));
            flash('La categoría fue eliminada.');
            adminRedirect('categories');
        }

        if ($form === 'banner_save') {
            AdminContent::saveBanner($_POST);
            flash('El banner se guardó correctamente.');
            adminRedirect('banners');
        }

        if ($form === 'banner_delete') {
            AdminContent::deleteBanner((int) ($_POST['id'] ?? 0));
            flash('El banner fue eliminado.');
            adminRedirect('banners');
        }
    } catch (Throwable $exception) {
        flash($exception->getMessage(), 'error');
        adminRedirect($section, $action, $id);
    }
}

$user = null;
if ($hasUsers) {
    try {
        $user = Auth::user();
    } catch (Throwable $exception) {
        $error = 'No fue posible validar la sesión administrativa.';
    }
}

function renderAdminHead(string $title, array $app): void
{
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | FEPA Veterinaria</title>
        <link rel="stylesheet" href="/assets/css/styles.css">
        <style>
            body.admin-body{margin:0;min-height:100vh;background:#f3f6f7;color:#102a33}.admin-auth{min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#0E2F38,#123f4a)}.admin-card{width:min(480px,calc(100% - 32px));background:#fff;border-radius:22px;padding:34px;box-shadow:0 24px 70px rgba(0,0,0,.24)}.admin-card h1{margin:18px 0 8px;color:#0E2F38}.admin-card p{color:#627179}.admin-form label{display:block;font-weight:800;margin-top:16px}.admin-form input,.admin-form textarea,.admin-form select{width:100%;padding:12px 13px;border:1px solid #dce3e6;border-radius:11px;margin-top:6px;font:inherit}.admin-form textarea{min-height:190px;resize:vertical}.admin-form button,.admin-button{border:0;background:#E4B34F;color:#fff;font-weight:900;border-radius:999px;padding:12px 18px;display:inline-flex;align-items:center;gap:8px;cursor:pointer}.admin-form button{margin-top:18px}.admin-button.secondary{background:#0E2F38}.admin-button.danger{background:#b64132}.admin-shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh}.admin-sidebar{background:#0E2F38;color:#fff;padding:24px;position:sticky;top:0;height:100vh}.admin-sidebar .brand{color:#fff;margin-bottom:28px}.admin-nav{display:grid;gap:8px}.admin-nav a{padding:12px 14px;border-radius:12px;color:#dbe7ea;font-weight:800}.admin-nav a.active,.admin-nav a:hover{background:rgba(228,179,79,.18);color:#fff}.admin-main{padding:28px}.admin-topbar{display:flex;justify-content:space-between;gap:18px;align-items:center;margin-bottom:24px}.admin-topbar h1{margin:0;color:#0E2F38}.admin-grid{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:14px;margin-bottom:24px}.stat-card,.admin-panel{background:#fff;border:1px solid #e6eaec;border-radius:18px;box-shadow:0 16px 45px rgba(14,47,56,.07);padding:20px}.stat-card strong{display:block;font-size:30px;color:#0E2F38}.stat-card span{color:#6d7b82;font-weight:800}.admin-table{width:100%;border-collapse:collapse;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 16px 45px rgba(14,47,56,.07)}.admin-table th,.admin-table td{text-align:left;padding:13px;border-bottom:1px solid #eef2f3;vertical-align:top}.admin-table th{background:#0E2F38;color:#fff}.admin-actions{display:flex;gap:8px;flex-wrap:wrap}.admin-actions form{display:inline}.badge{display:inline-flex;border-radius:999px;padding:4px 10px;font-weight:900;font-size:12px;background:#eef2f3;color:#0E2F38}.badge.ok{background:#e8f5ee;color:#247346}.badge.warn{background:#fff5db;color:#9b6a00}.admin-alert{padding:14px 16px;border-radius:14px;margin-bottom:16px;font-weight:800}.admin-alert.notice{background:#e8f5ee;color:#247346}.admin-alert.error{background:#fdeceb;color:#9c2f23}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-full{grid-column:1/-1}.checkbox-row{display:flex;align-items:center;gap:10px;margin-top:18px;font-weight:800}.checkbox-row input{width:auto;margin:0}.admin-help{background:#fff7e5;border-left:4px solid #E4B34F;padding:12px;border-radius:10px;color:#6d5120}.responsive-table{overflow:auto}@media(max-width:900px){.admin-shell{grid-template-columns:1fr}.admin-sidebar{position:static;height:auto}.admin-grid{grid-template-columns:repeat(2,1fr)}.form-grid{grid-template-columns:1fr}.admin-topbar{align-items:flex-start;flex-direction:column}}@media(max-width:560px){.admin-grid{grid-template-columns:1fr}.admin-main{padding:18px}.admin-card{padding:24px}}
        </style>
    </head>
    <body class="admin-body">
    <?php
}

function renderAuthScreen(bool $hasUsers, ?string $notice, ?string $error, array $app): void
{
    renderAdminHead($hasUsers ? 'Acceso administrativo' : 'Crear primer administrador', $app);
    ?>
    <main class="admin-auth">
        <section class="admin-card">
            <a class="brand" href="/" style="color:#0E2F38"><span class="brand-mark">🐾</span><span><strong><?= e($app['app_name']) ?></strong><small style="color:#607078">Panel administrativo</small></span></a>
            <?php if ($notice): ?><div class="admin-alert notice"><?= e($notice) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="admin-alert error"><?= e($error) ?></div><?php endif; ?>
            <?php if (!$hasUsers): ?>
                <h1>Crear primer administrador</h1>
                <p>La base no tiene usuarios activos. Crea el primer acceso para administrar notas, categorías y banners.</p>
                <form class="admin-form" method="post">
                    <?= Csrf::field() ?><input type="hidden" name="_form" value="first_admin">
                    <label>Nombre</label><input name="name" required autocomplete="name">
                    <label>Correo</label><input type="email" name="email" required autocomplete="email">
                    <label>Contraseña</label><input type="password" name="password" required minlength="8" autocomplete="new-password">
                    <button>Crear administrador</button>
                </form>
            <?php else: ?>
                <h1>Acceso privado</h1>
                <p>Ingresa con el usuario administrador creado en la tabla <strong>users</strong>.</p>
                <form class="admin-form" method="post">
                    <?= Csrf::field() ?><input type="hidden" name="_form" value="login">
                    <label>Correo</label><input type="email" name="email" required autocomplete="email">
                    <label>Contraseña</label><input type="password" name="password" required autocomplete="current-password">
                    <button>Entrar al panel</button>
                </form>
            <?php endif; ?>
            <div class="admin-help" style="margin-top:18px">Este panel usa sesiones PHP, PDO y contraseñas cifradas con `password_hash`.</div>
        </section>
    </main></body></html>
    <?php
}

function renderShellStart(string $section, array $user, ?string $notice, ?string $error, array $app): void
{
    renderAdminHead('Panel administrativo', $app);
    ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="brand" href="/admin/"><span class="brand-mark">🐾</span><span><strong><?= e($app['app_name']) ?></strong><small>Administración</small></span></a>
            <nav class="admin-nav">
                <a class="<?= activeNav($section, 'dashboard') ?>" href="/admin/?section=dashboard">Dashboard</a>
                <a class="<?= activeNav($section, 'posts') ?>" href="/admin/?section=posts">Notas</a>
                <a class="<?= activeNav($section, 'categories') ?>" href="/admin/?section=categories">Categorías</a>
                <a class="<?= activeNav($section, 'banners') ?>" href="/admin/?section=banners">Banners</a>
                <a href="/" target="_blank" rel="noopener">Ver sitio</a>
            </nav>
        </aside>
        <main class="admin-main">
            <div class="admin-topbar"><div><h1>Panel administrativo</h1><p>Sesión: <?= e($user['name']) ?> · <?= e($user['role']) ?></p></div><form method="post"><?= Csrf::field() ?><input type="hidden" name="_form" value="logout"><button class="admin-button secondary">Cerrar sesión</button></form></div>
            <?php if ($notice): ?><div class="admin-alert notice"><?= e($notice) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="admin-alert error"><?= e($error) ?></div><?php endif; ?>
    <?php
}

function renderShellEnd(): void
{
    echo '</main></div></body></html>';
}

function renderDashboard(): void
{
    $stats = AdminContent::stats();
    ?>
    <section class="admin-grid">
        <div class="stat-card"><strong><?= $stats['posts'] ?></strong><span>Total notas</span></div>
        <div class="stat-card"><strong><?= $stats['published'] ?></strong><span>Publicadas</span></div>
        <div class="stat-card"><strong><?= $stats['drafts'] ?></strong><span>Borradores</span></div>
        <div class="stat-card"><strong><?= $stats['categories'] ?></strong><span>Categorías</span></div>
        <div class="stat-card"><strong><?= $stats['banners'] ?></strong><span>Banners</span></div>
    </section>
    <section class="admin-panel"><h2>Acciones rápidas</h2><p>Desde aquí puedes crear contenido editorial, ordenar categorías y configurar espacios publicitarios sin editar código.</p><div class="admin-actions"><a class="admin-button" href="/admin/?section=posts&action=edit">Nueva nota</a><a class="admin-button secondary" href="/admin/?section=categories&action=edit">Nueva categoría</a><a class="admin-button secondary" href="/admin/?section=banners&action=edit">Nuevo banner</a></div></section>
    <?php
}

function renderPosts(string $action, ?int $id): void
{
    $categories = AdminContent::categories();
    $post = $action === 'edit' ? (AdminContent::post($id) ?? []) : [];

    if ($action === 'edit') {
        ?>
        <section class="admin-panel"><h2><?= $post ? 'Editar nota' : 'Nueva nota' ?></h2><form class="admin-form" method="post"><?= Csrf::field() ?><input type="hidden" name="_form" value="post_save"><input type="hidden" name="id" value="<?= value($post, 'id') ?>"><div class="form-grid"><label class="form-full">Título<input name="title" value="<?= value($post, 'title') ?>" required></label><label>Slug<input name="slug" value="<?= value($post, 'slug') ?>" placeholder="se-genera-automaticamente"></label><label>Categoría<select name="category_id"><option value="">Sin categoría</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= ((string)($post['category_id'] ?? '') === (string)$category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label><label>Estado<select name="status"><?php foreach (['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado'] as $key => $label): ?><option value="<?= $key ?>" <?= (($post['status'] ?? 'draft') === $key) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>Fecha de publicación<input type="datetime-local" name="published_at" value="<?= !empty($post['published_at']) ? e(date('Y-m-d\TH:i', strtotime($post['published_at']))) : '' ?>"></label><label class="form-full">Imagen destacada URL o ruta<input name="featured_image" value="<?= value($post, 'featured_image') ?>" placeholder="https://... o /assets/images/..."></label><label class="form-full">Extracto<textarea name="excerpt" rows="3"><?= value($post, 'excerpt') ?></textarea></label><label class="form-full">Contenido<textarea name="content" required><?= value($post, 'content') ?></textarea></label><label class="checkbox-row form-full"><input type="checkbox" name="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?>> Marcar como destacada</label></div><div class="admin-actions"><button>Guardar nota</button><a class="admin-button secondary" href="/admin/?section=posts">Cancelar</a></div></form></section>
        <?php
        return;
    }

    $posts = AdminContent::posts();
    ?>
    <section class="admin-panel"><div class="admin-topbar" style="margin-bottom:12px"><h2>Notas</h2><a class="admin-button" href="/admin/?section=posts&action=edit">Nueva nota</a></div><div class="responsive-table"><table class="admin-table"><thead><tr><th>Título</th><th>Categoría</th><th>Estado</th><th>Vistas</th><th>Actualización</th><th>Acciones</th></tr></thead><tbody><?php foreach ($posts as $item): ?><tr><td><strong><?= e($item['title']) ?></strong><br><small><?= e($item['slug']) ?></small></td><td><?= e($item['category'] ?: 'Sin categoría') ?></td><td><span class="badge <?= $item['status'] === 'published' ? 'ok' : 'warn' ?>"><?= e($item['status']) ?></span><?= !empty($item['is_featured']) ? ' <span class="badge">Destacada</span>' : '' ?></td><td><?= (int) $item['views'] ?></td><td><?= e($item['updated_at']) ?></td><td><div class="admin-actions"><a class="admin-button secondary" href="/admin/?section=posts&action=edit&id=<?= (int) $item['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar esta nota?')"><?= Csrf::field() ?><input type="hidden" name="_form" value="post_delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-button danger">Eliminar</button></form></div></td></tr><?php endforeach; ?><?php if (!$posts): ?><tr><td colspan="6">Aún no hay notas registradas.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
}

function renderCategories(string $action, ?int $id): void
{
    $category = $action === 'edit' ? (AdminContent::category($id) ?? []) : [];
    if ($action === 'edit') {
        ?>
        <section class="admin-panel"><h2><?= $category ? 'Editar categoría' : 'Nueva categoría' ?></h2><form class="admin-form" method="post"><?= Csrf::field() ?><input type="hidden" name="_form" value="category_save"><input type="hidden" name="id" value="<?= value($category, 'id') ?>"><div class="form-grid"><label>Nombre<input name="name" value="<?= value($category, 'name') ?>" required></label><label>Slug<input name="slug" value="<?= value($category, 'slug') ?>" placeholder="se-genera-automaticamente"></label><label>Color<input name="color" value="<?= value($category, 'color', '#E4B34F') ?>"></label><label>Orden<input type="number" name="sort_order" value="<?= value($category, 'sort_order', '0') ?>"></label><label class="form-full">Descripción<textarea name="description" rows="4"><?= value($category, 'description') ?></textarea></label><label class="checkbox-row form-full"><input type="checkbox" name="is_active" value="1" <?= !isset($category['is_active']) || !empty($category['is_active']) ? 'checked' : '' ?>> Categoría activa</label></div><div class="admin-actions"><button>Guardar categoría</button><a class="admin-button secondary" href="/admin/?section=categories">Cancelar</a></div></form></section>
        <?php
        return;
    }
    $categories = AdminContent::categories();
    ?>
    <section class="admin-panel"><div class="admin-topbar" style="margin-bottom:12px"><h2>Categorías</h2><a class="admin-button" href="/admin/?section=categories&action=edit">Nueva categoría</a></div><div class="responsive-table"><table class="admin-table"><thead><tr><th>Nombre</th><th>Slug</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach ($categories as $item): ?><tr><td><strong><?= e($item['name']) ?></strong><br><small><?= e($item['description']) ?></small></td><td><?= e($item['slug']) ?></td><td><?= (int) $item['sort_order'] ?></td><td><span class="badge <?= !empty($item['is_active']) ? 'ok' : 'warn' ?>"><?= !empty($item['is_active']) ? 'Activa' : 'Inactiva' ?></span></td><td><div class="admin-actions"><a class="admin-button secondary" href="/admin/?section=categories&action=edit&id=<?= (int) $item['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar esta categoría? Si tiene notas relacionadas, MySQL puede impedir la eliminación.')"><?= Csrf::field() ?><input type="hidden" name="_form" value="category_delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-button danger">Eliminar</button></form></div></td></tr><?php endforeach; ?><?php if (!$categories): ?><tr><td colspan="5">Aún no hay categorías registradas.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
}

function renderBanners(string $action, ?int $id): void
{
    $banner = $action === 'edit' ? (AdminContent::banner($id) ?? []) : [];
    $placements = ['top_728x90' => 'Superior 728x90', 'sidebar_top_300x250' => 'Sidebar superior 300x250', 'sidebar_bottom_300x250' => 'Sidebar inferior 300x250'];
    if ($action === 'edit') {
        ?>
        <section class="admin-panel"><h2><?= $banner ? 'Editar banner' : 'Nuevo banner' ?></h2><form class="admin-form" method="post"><?= Csrf::field() ?><input type="hidden" name="_form" value="banner_save"><input type="hidden" name="id" value="<?= value($banner, 'id') ?>"><div class="form-grid"><label>Nombre<input name="name" value="<?= value($banner, 'name') ?>" required></label><label>Ubicación<select name="placement"><?php foreach ($placements as $key => $label): ?><option value="<?= e($key) ?>" <?= (($banner['placement'] ?? '') === $key) ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Tipo<select name="type"><?php foreach (['image' => 'Imagen', 'html' => 'HTML', 'adsense' => 'AdSense'] as $key => $label): ?><option value="<?= $key ?>" <?= (($banner['type'] ?? 'image') === $key) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>URL destino<input name="target_url" value="<?= value($banner, 'target_url') ?>" placeholder="https://..."></label><label class="form-full">Ruta o URL de imagen<input name="image_path" value="<?= value($banner, 'image_path') ?>" placeholder="/assets/images/banner.jpg o https://..."></label><label>Inicio de vigencia<input type="datetime-local" name="starts_at" value="<?= !empty($banner['starts_at']) ? e(date('Y-m-d\TH:i', strtotime($banner['starts_at']))) : '' ?>"></label><label>Fin de vigencia<input type="datetime-local" name="ends_at" value="<?= !empty($banner['ends_at']) ? e(date('Y-m-d\TH:i', strtotime($banner['ends_at']))) : '' ?>"></label><label class="form-full">Código HTML o AdSense<textarea name="html_code" rows="5"><?= value($banner, 'html_code') ?></textarea></label><label class="checkbox-row form-full"><input type="checkbox" name="is_active" value="1" <?= !isset($banner['is_active']) || !empty($banner['is_active']) ? 'checked' : '' ?>> Banner activo</label></div><div class="admin-actions"><button>Guardar banner</button><a class="admin-button secondary" href="/admin/?section=banners">Cancelar</a></div></form><p class="admin-help">El front público ya consume estas ubicaciones: <strong>top_728x90</strong>, <strong>sidebar_top_300x250</strong> y <strong>sidebar_bottom_300x250</strong>.</p></section>
        <?php
        return;
    }
    $banners = AdminContent::banners();
    ?>
    <section class="admin-panel"><div class="admin-topbar" style="margin-bottom:12px"><h2>Banners</h2><a class="admin-button" href="/admin/?section=banners&action=edit">Nuevo banner</a></div><div class="responsive-table"><table class="admin-table"><thead><tr><th>Nombre</th><th>Ubicación</th><th>Tipo</th><th>Estado</th><th>Vigencia</th><th>Acciones</th></tr></thead><tbody><?php foreach ($banners as $item): ?><tr><td><strong><?= e($item['name']) ?></strong></td><td><?= e($item['placement']) ?></td><td><?= e($item['type']) ?></td><td><span class="badge <?= !empty($item['is_active']) ? 'ok' : 'warn' ?>"><?= !empty($item['is_active']) ? 'Activo' : 'Inactivo' ?></span></td><td><small><?= e($item['starts_at'] ?: 'Sin inicio') ?> / <?= e($item['ends_at'] ?: 'Sin fin') ?></small></td><td><div class="admin-actions"><a class="admin-button secondary" href="/admin/?section=banners&action=edit&id=<?= (int) $item['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar este banner?')"><?= Csrf::field() ?><input type="hidden" name="_form" value="banner_delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-button danger">Eliminar</button></form></div></td></tr><?php endforeach; ?><?php if (!$banners): ?><tr><td colspan="6">Aún no hay banners registrados.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
}

if (!$user) {
    renderAuthScreen($hasUsers, $notice, $error, $app);
    exit;
}

try {
    renderShellStart($section, $user, $notice, $error, $app);
    if ($section === 'posts') {
        renderPosts($action, $id);
    } elseif ($section === 'categories') {
        renderCategories($action, $id);
    } elseif ($section === 'banners') {
        renderBanners($action, $id);
    } else {
        renderDashboard();
    }
    renderShellEnd();
} catch (Throwable $exception) {
    renderShellStart($section, $user, $notice, $exception->getMessage(), $app);
    echo '<section class="admin-panel"><h2>No fue posible cargar esta sección</h2><p>' . e($exception->getMessage()) . '</p></section>';
    renderShellEnd();
}
