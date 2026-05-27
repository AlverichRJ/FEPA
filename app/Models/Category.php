<?php

namespace App\Models;

use App\Core\Database;

final class Category
{
    public static function active(): array
    {
        $statement = Database::connection()->query(
            'SELECT id, name, slug, description, color
             FROM categories
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );

        return $statement->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, slug, description, color
             FROM categories
             WHERE slug = :slug AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $category = $statement->fetch();

        return $category ?: null;
    }
}
