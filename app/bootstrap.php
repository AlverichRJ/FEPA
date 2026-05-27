<?php

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/Env.php';

App\Core\Env::load(BASE_PATH . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function articleUrl(string $slug): string
{
    return '/nota/' . rawurlencode($slug);
}

function categoryUrl(string $slug): string
{
    return '/categoria/' . rawurlencode($slug);
}

function formatDateSpanish(?string $date): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    $months = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    return (int) date('j', $timestamp) . ' de ' . $months[(int) date('n', $timestamp)] . ' de ' . date('Y', $timestamp);
}

function readingTime(?string $content): int
{
    $words = str_word_count(strip_tags((string) $content));
    return max(1, (int) ceil($words / 220));
}
