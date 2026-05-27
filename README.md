# FEPA Veterinaria – Portal de notas

Proyecto web en **PHP + MySQL/MariaDB + HTML/CSS/JavaScript** preparado para hosting tradicional con **cPanel de GoDaddy** y SSL administrado por GoDaddy.

## Objetivo

Crear un portal profesional de notas veterinarias con diseño editorial, SEO avanzado, compatibilidad con Open Graph/Facebook, espacios publicitarios, panel administrativo y despliegue sencillo en cPanel.

## Estructura

| Ruta | Descripción |
|---|---|
| `public/` | Raíz pública que debe apuntar al dominio o subirse a `public_html`. |
| `public/index.php` | Entrada pública del sitio. |
| `public/admin/` | Panel administrativo privado. |
| `public/assets/` | CSS, JavaScript e imágenes públicas. |
| `app/` | Código interno PHP del sistema. |
| `config/` | Configuración del proyecto y base de datos. |
| `database/` | Scripts SQL de instalación. |
| `storage/uploads/` | Archivos e imágenes subidos desde el panel. |

## Requisitos del hosting

- PHP 8.1 o superior recomendado.
- MySQL 5.7+/MariaDB 10.4+.
- Apache con `.htaccess` habilitado.
- SSL activado desde GoDaddy/cPanel.

## Configuración local de base de datos

Para conectar el sitio con MySQL en desarrollo local, copia el archivo de ejemplo y ajusta la contraseña real de tu instalación local. El archivo `.env` está ignorado por Git y no debe subirse al repositorio.

```powershell
copy .env.example .env
notepad .env
```

Ejemplo de valores locales:

```env
DB_HOST=localhost
DB_DATABASE=fepa_veterinaria
DB_USERNAME=root
DB_PASSWORD=TU_CONTRASEÑA_LOCAL
APP_URL=http://127.0.0.1:8081
```

Después de guardar `.env`, levanta nuevamente el servidor local:

```powershell
php -S 127.0.0.1:8081 -t public
```

## Estado actual

Base inicial conectada a MySQL mediante PDO. El Home, las categorías, las notas, los listados populares/virales y los espacios de banners ya están preparados para leer datos desde la base `fepa_veterinaria`. Las siguientes etapas completarán panel administrativo, SEO dinámico avanzado y monetización.
