<?php

namespace App\Models;

use App\Core\Database;

final class Banner
{
    public static function activeByPlacement(string $placement): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, placement, type, image_path, target_url, html_code
             FROM banners
             WHERE placement = :placement
               AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY id DESC
             LIMIT 1'
        );
        $statement->execute(['placement' => $placement]);
        $banner = $statement->fetch();

        return $banner ?: null;
    }
}
