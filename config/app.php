<?php
return [
    'app_name' => 'FEPA Veterinaria',
    'tagline' => 'Noticias y notas de salud animal',
    'base_url' => getenv('APP_URL') ?: '',
    'timezone' => 'America/Mexico_City',
    'admin_path' => 'admin-fepa',
    'brand' => [
        'primary' => '#0E2F38',
        'accent' => '#E4B34F',
        'background' => '#F4F6F7',
        'text' => '#102A33',
    ],
    'seo' => [
        'default_title' => 'FEPA Veterinaria | Notas y consejos de salud animal',
        'default_description' => 'Portal veterinario con consejos, noticias y recomendaciones para el cuidado de perros, gatos y otras mascotas.',
        'default_og_image' => '/assets/img/og-default.jpg',
    ],
];
