# Diseño técnico del panel administrativo FEPA

El panel administrativo se implementará dentro de `public/admin/index.php` para mantener compatibilidad con hosting tradicional en **cPanel**, evitando dependencias de frameworks, procesos persistentes o compilación. La aplicación reutilizará `app/bootstrap.php`, la conexión PDO centralizada y sesiones PHP nativas.

| Área | Decisión técnica |
|---|---|
| Autenticación | Login con correo y contraseña usando `users.password_hash`, `password_verify()` y sesiones PHP. |
| Primer acceso | Si no existen usuarios activos, se mostrará un formulario seguro para crear el primer administrador. |
| Seguridad | Se usará token CSRF por sesión, consultas preparadas PDO, salida escapada con `e()` y regeneración de sesión al iniciar sesión. |
| Rutas | El panel funcionará con `?section=...&action=...` para evitar reglas de servidor adicionales. |
| Notas | CRUD de `posts` con categoría, autor, slug, extracto, contenido, imagen destacada, estado, destacado y fecha de publicación. |
| Categorías | CRUD básico de `categories`, con slug único, color, orden y estado activo. |
| Banners | CRUD de `banners`, con placement, tipo, imagen, URL destino, HTML/AdSense, estado y vigencia. |
| Compatibilidad | Todo se ejecutará en PHP 8.1+ y MySQL/MariaDB, sin Node, Composer ni tareas en background. |

El alcance de esta etapa prioriza que el usuario pueda administrar contenido editorial y publicitario desde el navegador. La gestión avanzada de roles, subida física de archivos y SEO granular por nota podrán quedar para una etapa posterior si el usuario lo solicita.
