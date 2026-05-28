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

        if ($form === 'site_logo_save') {
            $logoPath = trim((string) ($_POST['site_logo'] ?? ''));
            if ($logoPath !== '' && !str_starts_with($logoPath, '/') && !filter_var($logoPath, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('La ruta del logo debe iniciar con / o ser una URL completa https://.');
            }
            AdminContent::saveSetting('site_logo', $logoPath);
            flash('El logo del sitio se actualizó correctamente.');
            adminRedirect('settings');
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
            :root{--fepa-primary:#18363B;--fepa-primary-2:#24474C;--fepa-primary-3:#516A6C;--fepa-gold:#B99145;--fepa-gold-dark:#8F733E;--fepa-bg:#F1EEE6;--fepa-card:#FBFAF6;--fepa-line:#D8D2C4;--fepa-text:#1E3033;--fepa-muted:#6E7774;--fepa-success:#24474C;--fepa-danger:#9A554C;--fepa-shadow:0 12px 30px rgba(24,54,59,.08);--fepa-radius:18px}body.admin-body{margin:0;min-height:100vh;background:radial-gradient(circle at top left,rgba(228,179,79,.14),transparent 34%),linear-gradient(180deg,#fbfaf7,var(--fepa-bg));color:var(--fepa-text)}.admin-auth{min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 12% 18%,rgba(228,179,79,.32),transparent 30%),linear-gradient(135deg,var(--fepa-primary),var(--fepa-primary-2) 58%,#08242b)}.admin-card{width:min(500px,calc(100% - 32px));background:linear-gradient(180deg,#fff,#fffaf0);border:1px solid rgba(228,179,79,.32);border-top:5px solid var(--fepa-gold);border-radius:26px;padding:36px;box-shadow:0 28px 80px rgba(0,0,0,.26)}.admin-card h1{margin:20px 0 8px;color:var(--fepa-primary);letter-spacing:-.02em}.admin-card p{color:var(--fepa-muted);line-height:1.55}.admin-form label{display:block;font-weight:900;margin-top:16px;color:var(--fepa-primary)}.admin-form input,.admin-form textarea,.admin-form select{width:100%;padding:13px 14px;border:1px solid var(--fepa-line);border-radius:13px;margin-top:7px;font:inherit;background:#fff;color:var(--fepa-text);box-shadow:inset 0 1px 0 rgba(14,47,56,.03)}.admin-form input:focus,.admin-form textarea:focus,.admin-form select:focus{outline:3px solid rgba(228,179,79,.22);border-color:var(--fepa-gold)}.admin-form textarea{min-height:190px;resize:vertical}.admin-form button,.admin-button{border:0;background:linear-gradient(135deg,var(--fepa-gold),var(--fepa-gold-dark));color:#fff;font-weight:900;border-radius:999px;padding:12px 18px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;box-shadow:0 10px 24px rgba(201,151,50,.24);transition:transform .16s ease,box-shadow .16s ease,background .16s ease}.admin-form button:hover,.admin-button:hover{transform:translateY(-1px);box-shadow:0 14px 30px rgba(201,151,50,.32)}.admin-form button{margin-top:18px}.admin-button.secondary{background:linear-gradient(135deg,var(--fepa-primary),var(--fepa-primary-3));box-shadow:0 10px 24px rgba(14,47,56,.18)}.admin-button.danger{background:linear-gradient(135deg,var(--fepa-danger),#8f2f25);box-shadow:0 10px 24px rgba(182,65,50,.18)}.admin-shell{display:grid;grid-template-columns:280px 1fr;min-height:100vh}.admin-sidebar{background:linear-gradient(180deg,var(--fepa-primary),#09272f);color:#fff;padding:26px 22px;position:sticky;top:0;height:100vh;border-right:5px solid var(--fepa-gold);box-shadow:8px 0 34px rgba(14,47,56,.14)}.admin-sidebar .brand{color:#fff;margin-bottom:30px;padding:10px;border-radius:18px;background:rgba(255,255,255,.06)}.admin-sidebar .brand small{color:#d6e6e9}.admin-nav{display:grid;gap:10px}.admin-nav a{padding:13px 15px;border-radius:14px;color:#dbe7ea;font-weight:900;border:1px solid transparent}.admin-nav a.active,.admin-nav a:hover{background:rgba(228,179,79,.18);border-color:rgba(228,179,79,.32);color:#fff}.admin-main{padding:30px;min-width:0}.admin-topbar{display:flex;justify-content:space-between;gap:18px;align-items:center;margin-bottom:26px;background:linear-gradient(135deg,#fff,#fff8e8);border:1px solid var(--fepa-line);border-radius:var(--fepa-radius);padding:20px 22px;box-shadow:var(--fepa-shadow)}.admin-topbar h1{margin:0;color:var(--fepa-primary);letter-spacing:-.03em}.admin-topbar p{margin:.35rem 0 0;color:var(--fepa-muted);font-weight:700}.admin-grid{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:16px;margin-bottom:26px}.stat-card,.admin-panel{background:var(--fepa-card);border:1px solid var(--fepa-line);border-radius:var(--fepa-radius);box-shadow:var(--fepa-shadow);padding:21px}.stat-card{position:relative;overflow:hidden}.stat-card:before{content:"";position:absolute;inset:0 auto 0 0;width:6px;background:linear-gradient(180deg,var(--fepa-gold),var(--fepa-primary-3))}.stat-card strong{display:block;font-size:32px;color:var(--fepa-primary);letter-spacing:-.04em}.stat-card span{color:var(--fepa-muted);font-weight:900}.admin-panel h2{margin-top:0;color:var(--fepa-primary)}.admin-table{width:100%;border-collapse:collapse;background:#fff;border-radius:18px;overflow:hidden;box-shadow:var(--fepa-shadow);border:1px solid var(--fepa-line)}.admin-table th,.admin-table td{text-align:left;padding:14px;border-bottom:1px solid #efe8dd;vertical-align:top}.admin-table th{background:linear-gradient(135deg,var(--fepa-primary),var(--fepa-primary-2));color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.04em}.admin-table tr:hover td{background:#fffaf0}.admin-actions{display:flex;gap:8px;flex-wrap:wrap}.admin-actions form{display:inline}.badge{display:inline-flex;border-radius:999px;padding:5px 11px;font-weight:900;font-size:12px;background:#f0eadf;color:var(--fepa-primary);border:1px solid #e5dac9}.badge.ok{background:#edf3f4;color:var(--fepa-success);border-color:#cfdcdf}.badge.warn{background:#fff5db;color:#9b6a00;border-color:#f0ddb0}.admin-alert{padding:14px 16px;border-radius:15px;margin-bottom:16px;font-weight:900;border:1px solid transparent}.admin-alert.notice{background:#edf3f4;color:var(--fepa-success);border-color:#cfdcdf}.admin-alert.error{background:#fdeceb;color:#9c2f23;border-color:#f3cbc5}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-full{grid-column:1/-1}.checkbox-row{display:flex;align-items:center;gap:10px;margin-top:18px;font-weight:900;color:var(--fepa-primary)}.checkbox-row input{width:auto;margin:0;accent-color:var(--fepa-gold)}.admin-help{background:#fff7e5;border:1px solid #efdcae;border-left:5px solid var(--fepa-gold);padding:13px;border-radius:12px;color:#6d5120;line-height:1.5}.responsive-table{overflow:auto}@media(max-width:900px){.admin-shell{grid-template-columns:1fr}.admin-sidebar{position:static;height:auto;border-right:0;border-bottom:5px solid var(--fepa-gold)}.admin-grid{grid-template-columns:repeat(2,1fr)}.form-grid{grid-template-columns:1fr}.admin-topbar{align-items:flex-start;flex-direction:column}}@media(max-width:560px){.admin-grid{grid-template-columns:1fr}.admin-main{padding:18px}.admin-card{padding:24px}}
            .editor-page{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:18px;align-items:start}.editor-main{display:grid;gap:14px}.editor-title{width:100%;border:1px solid var(--fepa-line);border-radius:12px;background:#fff;padding:16px 14px;font-size:28px;line-height:1.18;font-weight:800;color:var(--fepa-primary);letter-spacing:-.03em}.editor-title:focus{outline:2px solid rgba(185,145,69,.18);border-color:var(--fepa-gold)}.editor-subtitle{width:100%;border:1px solid var(--fepa-line);border-radius:12px;background:#fff;padding:13px 14px;font-size:15px;color:var(--fepa-muted)}.editor-subtitle:focus{outline:2px solid rgba(185,145,69,.18);border-color:var(--fepa-gold)}.editor-hero-card,.editor-sidebar-card{background:#fbfaf6;border:1px solid var(--fepa-line);border-radius:var(--fepa-radius);box-shadow:var(--fepa-shadow);padding:18px}.editor-action-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.media-dropzone{border:1px dashed #cfc5ad;border-radius:14px;background:#f7f3e9;padding:16px;color:#625943}.media-dropzone strong{display:block;color:var(--fepa-primary);margin-bottom:5px}.editor-box{background:#fff;border:1px solid var(--fepa-line);border-radius:14px;overflow:hidden}.editor-toolbar{border-bottom:1px solid var(--fepa-line);background:#f4f1e9;padding:7px 8px;display:flex;gap:5px;flex-wrap:wrap;align-items:center}.editor-toolbar select{height:30px;min-width:96px;border:1px solid #cfc8bb;background:#fff;color:var(--fepa-primary);border-radius:4px;padding:4px 8px;font-size:12px;font-weight:700}.editor-toolbar button{height:30px;min-width:28px;border:1px solid transparent;background:transparent;color:var(--fepa-primary);border-radius:4px;padding:4px 7px;font-size:13px;font-weight:800;line-height:1;cursor:pointer}.editor-toolbar button:hover{background:#e7e1d4;border-color:#cfc8bb}.editor-toolbar .toolbar-separator{width:1px;height:23px;background:#cfc8bb;margin:0 2px}.editor-toolbar input[type=color]{width:28px;height:28px;border:1px solid #cfc8bb;border-radius:4px;background:#fff;padding:2px}.editor-tabs{display:flex;justify-content:flex-end;gap:5px;padding:8px;background:#fff;border-bottom:1px solid var(--fepa-line)}.editor-tabs button{border:1px solid #cfc8bb;background:#f8f6f0;color:var(--fepa-primary);border-radius:4px;padding:6px 9px;font-size:12px;font-weight:800;cursor:pointer}.editor-tabs button.active{background:var(--fepa-primary);color:#fff;border-color:var(--fepa-primary)}.visual-editor{min-height:520px;background:#fff;padding:22px;font-size:17px;line-height:1.7;color:var(--fepa-text);overflow:auto}.visual-editor:focus{outline:2px solid rgba(185,145,69,.16);outline-offset:-2px}.visual-editor:empty:before{content:attr(data-placeholder);color:#98a09d}.code-editor{min-height:520px;border:0;border-radius:0;font-family:Consolas,Monaco,monospace;font-size:15px}.editor-sidebar{display:grid;gap:14px;position:sticky;top:24px}.editor-sidebar-card{padding:0;overflow:hidden;background:#fbfaf6}.editor-sidebar-card summary{list-style:none;padding:15px 17px;font-weight:900;color:var(--fepa-primary);cursor:pointer;background:#f3efe4;border-bottom:1px solid var(--fepa-line)}.editor-sidebar-card summary::-webkit-details-marker{display:none}.editor-sidebar-card .panel-body{padding:16px 17px;display:grid;gap:13px}.publish-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.publish-actions .admin-button{justify-content:center;width:100%;min-height:46px;margin-top:0;padding:10px 8px;text-align:center;line-height:1.15;font-size:12px;white-space:normal}.publish-actions .publish-primary{background:linear-gradient(135deg,var(--fepa-primary),var(--fepa-primary-2));box-shadow:0 10px 24px rgba(14,47,56,.2)}.public-link-box{border:1px solid rgba(185,145,69,.34);border-radius:13px;background:#fffaf0;padding:12px}.public-link-box label{margin-top:0}.link-copy-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:end}.link-copy-row input{font-size:12px;color:var(--fepa-primary);background:#fff}.link-copy-row .admin-button{margin-top:7px;padding:12px 14px}.category-checklist{max-height:210px;overflow:auto;border:1px solid var(--fepa-line);border-radius:11px;padding:9px;background:#fff}.category-checklist label{display:flex;align-items:flex-start;justify-content:flex-start;gap:9px;margin:0;padding:8px;border-radius:8px;line-height:1.25;text-align:left}.category-checklist label:hover{background:#f4f1e9}.category-checklist input[type=radio]{width:auto;min-width:14px;margin:2px 0 0;padding:0;flex:0 0 auto;accent-color:var(--fepa-primary)}.tag-input-row{display:flex;gap:8px}.tag-input-row input{min-width:0}.featured-preview{border:1px dashed #cfc5ad;border-radius:13px;background:#f7f3e9;padding:12px;text-align:center;color:#625943}.featured-preview img{max-width:100%;border-radius:10px;display:block;margin:0 auto 10px}.format-options{display:grid;gap:8px}.format-options label{display:flex;gap:9px;align-items:center;justify-content:flex-start;margin:0;color:var(--fepa-text);line-height:1.25;text-align:left}.format-options input[type=radio]{width:auto;min-width:14px;margin:0;padding:0;flex:0 0 auto;accent-color:var(--fepa-primary)}.editor-note{font-size:13px;color:var(--fepa-muted);line-height:1.45}.is-hidden{display:none!important}@media(max-width:1100px){.editor-page{grid-template-columns:1fr}.editor-sidebar{position:static}.editor-title{font-size:27px}}@media(max-width:640px){.editor-title{font-size:24px}.publish-actions{grid-template-columns:1fr}.editor-toolbar{overflow:auto;flex-wrap:nowrap}.visual-editor,.code-editor{min-height:380px}}
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
                <a class="<?= activeNav($section, 'settings') ?>" href="/admin/?section=settings">Logo</a>
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
    ?>
        </main>
    </div>
    <script>
        (function () {
            const form = document.querySelector('.post-editor-form');
            if (!form) {
                return;
            }

            const visualEditor = document.getElementById('visualEditor');
            const codeEditor = document.getElementById('postContent');
            const modeButtons = document.querySelectorAll('[data-editor-mode]');
            const statusInput = document.getElementById('postStatus');
            const statusSelect = document.querySelector('[data-status-select]');
            const featuredInput = document.querySelector('input[name="featured_image"]');
            const featuredPreview = document.querySelector('[data-featured-preview]');
            const mediaButton = document.querySelector('[data-media-focus]');
            const mediaPicker = document.getElementById('mediaPicker');
            const mediaDropzone = document.getElementById('mediaDropzone');
            const mediaStorageNotice = document.getElementById('mediaStorageNotice');
            let currentMode = 'visual';

            function syncToVisual() {
                if (visualEditor && codeEditor) {
                    visualEditor.innerHTML = codeEditor.value || '';
                }
            }

            function syncToCode() {
                if (visualEditor && codeEditor && currentMode === 'visual') {
                    codeEditor.value = visualEditor.innerHTML.trim();
                }
            }

            function switchMode(mode) {
                if (!visualEditor || !codeEditor) {
                    return;
                }
                if (mode === 'code') {
                    syncToCode();
                    visualEditor.classList.add('is-hidden');
                    codeEditor.classList.remove('is-hidden');
                } else {
                    syncToVisual();
                    codeEditor.classList.add('is-hidden');
                    visualEditor.classList.remove('is-hidden');
                }
                currentMode = mode;
                modeButtons.forEach((button) => button.classList.toggle('active', button.dataset.editorMode === mode));
            }

            function runCommand(command, value) {
                if (currentMode !== 'visual') {
                    switchMode('visual');
                }
                visualEditor.focus();
                document.execCommand(command, false, value || null);
                syncToCode();
            }

            function updateFeaturedPreview() {
                if (!featuredInput || !featuredPreview) {
                    return;
                }
                const url = featuredInput.value.trim();
                featuredPreview.innerHTML = url ? '<img src="' + url.replace(/"/g, '&quot;') + '" alt="Vista previa"><span>Portada principal configurada</span>' : '<span>Sin imagen destacada</span>';
            }

            syncToVisual();

            modeButtons.forEach((button) => {
                button.addEventListener('click', () => switchMode(button.dataset.editorMode));
            });

            document.querySelectorAll('[data-command]').forEach((button) => {
                button.addEventListener('click', () => runCommand(button.dataset.command, button.dataset.value || null));
            });

            const blockSelect = document.querySelector('[data-editor-block]');
            if (blockSelect) {
                blockSelect.addEventListener('change', () => runCommand('formatBlock', blockSelect.value));
            }

            const fontSelect = document.querySelector('[data-editor-font]');
            if (fontSelect) {
                fontSelect.addEventListener('change', () => runCommand('fontName', fontSelect.value));
            }

            const sizeSelect = document.querySelector('[data-editor-size]');
            if (sizeSelect) {
                sizeSelect.addEventListener('change', () => runCommand('fontSize', sizeSelect.value));
            }

            const colorInput = document.querySelector('[data-editor-color]');
            if (colorInput) {
                colorInput.addEventListener('input', () => runCommand('foreColor', colorInput.value));
            }

            const bgInput = document.querySelector('[data-editor-bg]');
            if (bgInput) {
                bgInput.addEventListener('input', () => runCommand('hiliteColor', bgInput.value));
            }

            const linkButton = document.querySelector('[data-link]');
            if (linkButton) {
                linkButton.addEventListener('click', () => {
                    const url = window.prompt('Pega la URL del enlace');
                    if (url) {
                        runCommand('createLink', url);
                    }
                });
            }

            document.querySelectorAll('[data-save-status]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (statusInput) {
                        statusInput.value = button.dataset.saveStatus;
                    }
                    if (statusSelect) {
                        statusSelect.value = button.dataset.saveStatus;
                    }
                    syncToCode();
                });
            });

            if (statusSelect && statusInput) {
                statusSelect.addEventListener('change', () => {
                    statusInput.value = statusSelect.value;
                });
            }

            if (visualEditor) {
                visualEditor.addEventListener('input', syncToCode);
                visualEditor.addEventListener('blur', syncToCode);
            }

            if (codeEditor) {
                codeEditor.addEventListener('input', () => {
                    if (currentMode === 'code') {
                        syncToVisual();
                        switchMode('code');
                    }
                });
            }

            if (featuredInput) {
                featuredInput.addEventListener('input', updateFeaturedPreview);
            }

            if (mediaButton && mediaPicker) {
                mediaButton.addEventListener('click', () => mediaPicker.click());
            }

            if (mediaPicker && mediaDropzone) {
                mediaPicker.addEventListener('change', () => {
                    const count = mediaPicker.files ? mediaPicker.files.length : 0;
                    if (count) {
                        const storagePath = window.prompt('¿Dónde se van a alojar estos archivos multimedia? Ejemplo: /assets/uploads/ o una URL CDN https://...', '/assets/uploads/');
                        mediaDropzone.querySelector('strong').textContent = count + ' archivo(s) seleccionados para referencia visual';
                        if (mediaStorageNotice) {
                            mediaStorageNotice.textContent = storagePath ? 'Alojamiento indicado: ' + storagePath : 'No se indicó alojamiento. Sube los archivos a cPanel y usa su ruta pública en la nota.';
                        }
                    }
                });
            }

            const copyPublicLinkButton = document.querySelector('[data-copy-public-link]');
            const publicLinkInput = document.getElementById('publicLinkInput');
            if (copyPublicLinkButton && publicLinkInput) {
                copyPublicLinkButton.addEventListener('click', () => {
                    publicLinkInput.select();
                    publicLinkInput.setSelectionRange(0, publicLinkInput.value.length);
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(publicLinkInput.value);
                    } else {
                        document.execCommand('copy');
                    }
                    copyPublicLinkButton.textContent = 'Copiado';
                    window.setTimeout(() => { copyPublicLinkButton.textContent = 'Copiar'; }, 1400);
                });
            }

            form.addEventListener('submit', syncToCode);
        })();
    </script>
    </body></html>
    <?php
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
    <section class="admin-panel"><h2>Acciones rápidas</h2><p>Desde aquí puedes crear contenido editorial, ordenar categorías, actualizar el logo y configurar espacios publicitarios sin editar código.</p><div class="admin-actions"><a class="admin-button" href="/admin/?section=posts&action=edit">Nueva nota</a><a class="admin-button secondary" href="/admin/?section=categories&action=edit">Nueva categoría</a><a class="admin-button secondary" href="/admin/?section=banners&action=edit">Nuevo banner</a><a class="admin-button secondary" href="/admin/?section=settings">Actualizar logo</a></div></section>
    <?php
}

function renderPosts(string $action, ?int $id): void
{
    global $app;

    $categories = AdminContent::categories();
    $post = $action === 'edit' ? (AdminContent::post($id) ?? []) : [];
    $publicPostUrl = '';
    if (!empty($post['slug']) && ($post['status'] ?? '') === 'published') {
        $baseUrl = rtrim((string) ($app['app_url'] ?? ''), '/');
        $publicPostUrl = ($baseUrl !== '' ? $baseUrl : '') . '/nota/' . ltrim((string) $post['slug'], '/');
    }

    if ($action === 'edit') {
        ?>
        <form class="admin-form post-editor-form" method="post"><?= Csrf::field() ?><input type="hidden" name="_form" value="post_save"><input type="hidden" name="id" value="<?= value($post, 'id') ?>"><input type="hidden" id="postStatus" name="status" value="<?= value($post, 'status', 'draft') ?>">
            <div class="editor-page">
                <section class="editor-main">
                    <div class="editor-hero-card">
                        <input class="editor-title" name="title" value="<?= value($post, 'title') ?>" required placeholder="Escribe un título" autocomplete="off">
                        <input class="editor-subtitle" name="excerpt" value="<?= value($post, 'excerpt') ?>" placeholder="Add sub title here" autocomplete="off">
                    </div>

                    <div class="editor-hero-card">
                        <div class="editor-action-row">
                            <button type="button" class="admin-button secondary" data-media-focus>＋ Añadir medios</button>
                        </div>
                    </div>

                    <div class="media-dropzone" id="mediaDropzone">
                        <strong>Añadir medios</strong>
                        <span>Antes de usar imágenes o videos en una nota, indica dónde se van a alojar. Recomendado para cPanel: <code>/public/assets/uploads/</code>; en el editor se usa la ruta pública, por ejemplo <code>/assets/uploads/mi-imagen.jpg</code>. Si usas CDN externo, pega la URL completa <code>https://...</code>.</span>
                        <p class="editor-note" id="mediaStorageNotice">Al seleccionar archivos, el panel te preguntará la ubicación de alojamiento.</p>
                        <input type="file" id="mediaPicker" accept="image/*,video/*" multiple style="display:none">
                    </div>

                    <section class="editor-box">
                        <div class="editor-tabs">
                            <button type="button" class="active" data-editor-mode="visual">Visual</button>
                            <button type="button" data-editor-mode="code">Código</button>
                        </div>
                        <div class="editor-toolbar" aria-label="Herramientas del editor">
                            <select data-editor-font><option value="Arial">Arial</option><option value="Georgia">Georgia</option><option value="Verdana">Verdana</option><option value="Tahoma">Tahoma</option><option value="Trebuchet MS">Trebuchet</option></select>
                            <select data-editor-size><option value="3">14px</option><option value="4">16px</option><option value="5">18px</option><option value="6">22px</option></select>
                            <select data-editor-block><option value="P">Párrafo</option><option value="H2">Título H2</option><option value="H3">Título H3</option><option value="H4">Título H4</option></select>
                            <span class="toolbar-separator"></span>
                            <button type="button" title="Negrita" data-command="bold"><strong>B</strong></button><button type="button" title="Cursiva" data-command="italic"><em>I</em></button><button type="button" title="Lista" data-command="insertUnorderedList">☰</button><button type="button" title="Lista numerada" data-command="insertOrderedList">≡</button>
                            <button type="button" title="Cita" data-command="formatBlock" data-value="BLOCKQUOTE">❝</button><button type="button" title="Alinear izquierda" data-command="justifyLeft">☰</button><button type="button" title="Centrar" data-command="justifyCenter">≡</button><button type="button" title="Alinear derecha" data-command="justifyRight">☷</button><button type="button" title="Justificar" data-command="justifyFull">▤</button>
                            <button type="button" title="Enlace" data-link>⛓</button><button type="button" title="Línea" data-command="insertHorizontalRule">─</button>
                            <span class="toolbar-separator"></span>
                            <button type="button" title="Subrayado" data-command="underline"><u>U</u></button><button type="button" title="Tachado" data-command="strikeThrough"><s>abc</s></button>
                            <input type="color" value="#1E3033" title="Color de texto" data-editor-color><input type="color" value="#F1EEE6" title="Color de fondo" data-editor-bg>
                            <button type="button" title="Deshacer" data-command="undo">↶</button><button type="button" title="Rehacer" data-command="redo">↷</button>
                        </div>
                        <div id="visualEditor" class="visual-editor" contenteditable="true" data-placeholder="Empieza a escribir el contenido de la nota..."></div>
                        <textarea id="postContent" class="code-editor is-hidden" name="content" required><?= value($post, 'content') ?></textarea>
                    </section>
                </section>

                <aside class="editor-sidebar">
                    <details class="editor-sidebar-card" open><summary>Publicar</summary><div class="panel-body">
                        <div class="publish-actions"><button type="submit" class="admin-button secondary" data-save-status="draft">Guardar borrador</button><a class="admin-button secondary" href="<?= e($publicPostUrl ?: '/') ?>" target="_blank" rel="noopener">Vista previa</a><button type="submit" class="admin-button publish-primary" data-save-status="published">Publicar / Actualizar</button></div>
                        <?php if ($publicPostUrl !== ''): ?><div class="public-link-box"><label>Enlace público</label><div class="link-copy-row"><input type="text" readonly value="<?= e($publicPostUrl) ?>" id="publicLinkInput"><button type="button" class="admin-button secondary" data-copy-public-link>Copiar</button></div></div><?php endif; ?>
                        <label>Estado<select data-status-select><?php foreach (['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado'] as $key => $label): ?><option value="<?= $key ?>" <?= (($post['status'] ?? 'draft') === $key) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
                        <label>Visibilidad<select><option>Pública</option><option>Privada futura</option></select></label>
                        <label>Fecha de publicación<input type="datetime-local" name="published_at" value="<?= !empty($post['published_at']) ? e(date('Y-m-d\TH:i', strtotime($post['published_at']))) : '' ?>"></label>
                    </div></details>

                    <details class="editor-sidebar-card" open><summary>Formato</summary><div class="panel-body format-options">
                        <?php foreach (['standard' => 'Estándar', 'gallery' => 'Galería', 'video' => 'Video', 'audio' => 'Audio', 'link' => 'Enlace', 'quote' => 'Cita'] as $formatKey => $formatLabel): ?><label><input type="radio" name="content_format" value="<?= e($formatKey) ?>" <?= $formatKey === 'standard' ? 'checked' : '' ?>> <?= e($formatLabel) ?></label><?php endforeach; ?>
                    </div></details>

                    <details class="editor-sidebar-card" open><summary>Categorías</summary><div class="panel-body">
                        <div class="editor-action-row"><span class="badge ok">Todas</span><span class="badge">Más utilizadas</span></div>
                        <div class="category-checklist"><label><input type="radio" name="category_id" value="" <?= empty($post['category_id']) ? 'checked' : '' ?>> Sin categoría</label><?php foreach ($categories as $category): ?><label><input type="radio" name="category_id" value="<?= (int) $category['id'] ?>" <?= ((string)($post['category_id'] ?? '') === (string)$category['id']) ? 'checked' : '' ?>> <?= e($category['name']) ?></label><?php endforeach; ?></div>
                        <a class="admin-button secondary" href="/admin/?section=categories&action=edit">＋ Añadir categoría</a>
                    </div></details>

                    <details class="editor-sidebar-card"><summary>Etiquetas</summary><div class="panel-body">
                        <div class="tag-input-row"><input name="tags" placeholder="salud, mascotas, adopción"><button type="button" class="admin-button secondary">Añadir</button></div>
                        <p class="editor-note">Las etiquetas quedan preparadas visualmente; si luego quieres buscador por tags, agregamos su tabla y relación.</p>
                    </div></details>

                    <details class="editor-sidebar-card" open><summary>Imagen destacada</summary><div class="panel-body">
                        <div class="featured-preview" data-featured-preview><?php if (!empty($post['featured_image'])): ?><img src="<?= value($post, 'featured_image') ?>" alt="Vista previa"><span>Cambiar portada principal</span><?php else: ?><span>Sin imagen destacada</span><?php endif; ?></div>
                        <label>URL o ruta de portada<input name="featured_image" value="<?= value($post, 'featured_image') ?>" placeholder="https://... o /assets/images/..."></label>
                        <p class="editor-note">Esta imagen se usará en home, SEO, Open Graph, recomendados y miniaturas.</p>
                    </div></details>

                    <details class="editor-sidebar-card"><summary>Slug y destacado</summary><div class="panel-body">
                        <label>Slug<input name="slug" value="<?= value($post, 'slug') ?>" placeholder="se-genera-automaticamente"></label>
                        <label class="checkbox-row"><input type="checkbox" name="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?>> Marcar como destacada</label>
                        <a class="admin-button secondary" href="/admin/?section=posts">Cancelar</a>
                    </div></details>
                </aside>
            </div>
        </form>
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


function renderSettings(): void
{
    $settings = AdminContent::settings();
    $siteLogo = (string) ($settings['site_logo'] ?? '');
    ?>
    <section class="admin-panel">
        <div class="admin-topbar" style="margin-bottom:16px">
            <div>
                <h2>Logo del sitio</h2>
                <p>Actualiza la imagen que aparece en el encabezado y el pie de página.</p>
            </div>
            <a class="admin-button secondary" href="/" target="_blank" rel="noopener">Ver home</a>
        </div>
        <form class="admin-form" method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="_form" value="site_logo_save">
            <div class="form-grid">
                <label class="form-full">Ruta o URL del logo
                    <input name="site_logo" value="<?= e($siteLogo) ?>" placeholder="/assets/uploads/logo-fepa.png o https://...">
                </label>
            </div>
            <p class="admin-help">Aloja primero el archivo en tu cPanel, idealmente en <strong>/public/assets/uploads/</strong>, y coloca aquí la ruta pública como <strong>/assets/uploads/logo-fepa.png</strong>. Si dejas este campo vacío, el sitio mostrará la marca actual con el ícono de huella.</p>
            <div class="admin-actions"><button>Actualizar logo</button><a class="admin-button secondary" href="/admin/">Cancelar</a></div>
        </form>
    </section>
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
    } elseif ($section === 'settings') {
        renderSettings();
    } else {
        renderDashboard();
    }
    renderShellEnd();
} catch (Throwable $exception) {
    renderShellStart($section, $user, $notice, $exception->getMessage(), $app);
    echo '<section class="admin-panel"><h2>No fue posible cargar esta sección</h2><p>' . e($exception->getMessage()) . '</p></section>';
    renderShellEnd();
}
