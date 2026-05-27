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

## Estado actual

Base inicial creada con Home y página interna de nota usando datos de demostración. Las siguientes etapas completarán panel administrativo, base de datos, SEO dinámico y monetización.
