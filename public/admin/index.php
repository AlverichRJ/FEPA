<?php $app = require __DIR__ . '/../../config/app.php'; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel administrativo | FEPA Veterinaria</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        body{min-height:100vh;display:grid;place-items:center;background:#0E2F38}.admin-card{width:min(460px,calc(100% - 32px));background:#fff;border-radius:20px;padding:34px;box-shadow:0 24px 70px rgba(0,0,0,.22)}.admin-card h1{margin:0 0 8px;color:#0E2F38}.admin-card p{color:#627179}.admin-card label{display:block;font-weight:800;margin-top:16px}.admin-card input{width:100%;padding:13px;border:1px solid #dce3e6;border-radius:10px;margin-top:6px}.admin-card button{width:100%;border:0;background:#E4B34F;color:#fff;font-weight:900;border-radius:999px;padding:13px;margin-top:20px}.admin-note{background:#fff7e5;border-left:4px solid #E4B34F;padding:12px;border-radius:10px;margin-top:18px;font-size:.9rem}
    </style>
</head>
<body>
    <section class="admin-card">
        <a class="brand" href="/" style="color:#0E2F38"><span class="brand-mark">🐾</span><span><strong><?= htmlspecialchars($app['app_name']) ?></strong><small style="color:#607078">Panel administrativo</small></span></a>
        <h1>Acceso privado</h1>
        <p>Esta pantalla es una base inicial. En la siguiente etapa se conectará el login seguro, gestión de notas, categorías, banners y configuración SEO.</p>
        <form>
            <label>Correo</label><input type="email" placeholder="admin@tudominio.com" disabled>
            <label>Contraseña</label><input type="password" placeholder="••••••••" disabled>
            <button type="button">Próximamente</button>
        </form>
        <div class="admin-note">Preparado para sesiones PHP seguras y futura integración con Google OAuth cuando el dominio final esté definido.</div>
    </section>
</body>
</html>
