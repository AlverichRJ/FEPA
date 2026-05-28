<?php

namespace App\Models;

use App\Core\Database;
use PDO;

final class AdminContent
{
    public static function stats(): array
    {
        $db = Database::connection();

        return [
            'posts' => (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
            'published' => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
            'drafts' => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
            'categories' => (int) $db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'banners' => (int) $db->query('SELECT COUNT(*) FROM banners')->fetchColumn(),
        ];
    }

    public static function posts(): array
    {
        $statement = Database::connection()->query(
            'SELECT p.id, p.title, p.slug, p.status, p.is_featured, p.views, p.published_at, p.updated_at,
                    c.name AS category, u.name AS author
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.author_id
             ORDER BY p.updated_at DESC, p.id DESC'
        );

        return $statement->fetchAll();
    }

    public static function post(?int $id): ?array
    {
        if (!$id) {
            return null;
        }

        $statement = Database::connection()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $post = $statement->fetch();

        return $post ?: null;
    }

    public static function savePost(array $data, int $authorId): int
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
        $publishedAt = self::normalizeDateTime($data['published_at'] ?? null);
        $payload = [
            'category_id' => self::nullableInt($data['category_id'] ?? null),
            'author_id' => $authorId,
            'title' => trim((string) ($data['title'] ?? '')),
            'slug' => self::uniqueSlug('posts', self::slugify($data['slug'] ?: $data['title']), $id),
            'excerpt' => self::nullableString($data['excerpt'] ?? null),
            'content' => trim((string) ($data['content'] ?? '')),
            'featured_image' => self::nullableString($data['featured_image'] ?? null),
            'status' => in_array(($data['status'] ?? 'draft'), ['draft', 'published', 'archived'], true) ? $data['status'] : 'draft',
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'published_at' => $publishedAt,
        ];

        if ($payload['title'] === '' || $payload['content'] === '') {
            throw new \InvalidArgumentException('El título y el contenido son obligatorios.');
        }

        if ($payload['status'] === 'published' && !$payload['published_at']) {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        if ($id) {
            $payload['id'] = $id;
            $statement = Database::connection()->prepare(
                'UPDATE posts
                 SET category_id = :category_id, author_id = :author_id, title = :title, slug = :slug, excerpt = :excerpt,
                     content = :content, featured_image = :featured_image, status = :status, is_featured = :is_featured,
                     published_at = :published_at
                 WHERE id = :id'
            );
            $statement->execute($payload);

            return $id;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO posts (category_id, author_id, title, slug, excerpt, content, featured_image, status, is_featured, published_at)
             VALUES (:category_id, :author_id, :title, :slug, :excerpt, :content, :featured_image, :status, :is_featured, :published_at)'
        );
        $statement->execute($payload);

        return (int) Database::connection()->lastInsertId();
    }

    public static function deletePost(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM posts WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public static function categories(): array
    {
        return Database::connection()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
    }

    public static function category(?int $id): ?array
    {
        if (!$id) {
            return null;
        }

        $statement = Database::connection()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $category = $statement->fetch();

        return $category ?: null;
    }

    public static function saveCategory(array $data): int
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'slug' => self::uniqueSlug('categories', self::slugify($data['slug'] ?: $data['name']), $id),
            'description' => self::nullableString($data['description'] ?? null),
            'color' => trim((string) ($data['color'] ?? '#E4B34F')) ?: '#E4B34F',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($payload['name'] === '') {
            throw new \InvalidArgumentException('El nombre de la categoría es obligatorio.');
        }

        if ($id) {
            $payload['id'] = $id;
            $statement = Database::connection()->prepare(
                'UPDATE categories
                 SET name = :name, slug = :slug, description = :description, color = :color, sort_order = :sort_order, is_active = :is_active
                 WHERE id = :id'
            );
            $statement->execute($payload);

            return $id;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO categories (name, slug, description, color, sort_order, is_active)
             VALUES (:name, :slug, :description, :color, :sort_order, :is_active)'
        );
        $statement->execute($payload);

        return (int) Database::connection()->lastInsertId();
    }

    public static function deleteCategory(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public static function banners(): array
    {
        return Database::connection()->query('SELECT * FROM banners ORDER BY created_at DESC, id DESC')->fetchAll();
    }

    public static function banner(?int $id): ?array
    {
        if (!$id) {
            return null;
        }

        $statement = Database::connection()->prepare('SELECT * FROM banners WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $banner = $statement->fetch();

        return $banner ?: null;
    }

    public static function saveBanner(array $data): int
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;
        $type = in_array(($data['type'] ?? 'image'), ['image', 'html', 'adsense'], true) ? $data['type'] : 'image';
        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'placement' => trim((string) ($data['placement'] ?? '')),
            'type' => $type,
            'image_path' => self::nullableString($data['image_path'] ?? null),
            'target_url' => self::nullableString($data['target_url'] ?? null),
            'html_code' => self::nullableString($data['html_code'] ?? null),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'starts_at' => self::normalizeDateTime($data['starts_at'] ?? null),
            'ends_at' => self::normalizeDateTime($data['ends_at'] ?? null),
        ];

        if ($payload['name'] === '' || $payload['placement'] === '') {
            throw new \InvalidArgumentException('El nombre y la ubicación del banner son obligatorios.');
        }

        if ($id) {
            $payload['id'] = $id;
            $statement = Database::connection()->prepare(
                'UPDATE banners
                 SET name = :name, placement = :placement, type = :type, image_path = :image_path, target_url = :target_url,
                     html_code = :html_code, is_active = :is_active, starts_at = :starts_at, ends_at = :ends_at
                 WHERE id = :id'
            );
            $statement->execute($payload);

            return $id;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO banners (name, placement, type, image_path, target_url, html_code, is_active, starts_at, ends_at)
             VALUES (:name, :placement, :type, :image_path, :target_url, :html_code, :is_active, :starts_at, :ends_at)'
        );
        $statement->execute($payload);

        return (int) Database::connection()->lastInsertId();
    }

    public static function deleteBanner(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM banners WHERE id = :id');
        $statement->execute(['id' => $id]);
    }


    public static function settings(): array
    {
        $statement = Database::connection()->query('SELECT setting_key, setting_value FROM settings');
        $settings = [];

        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public static function setting(string $key, string $default = ''): string
    {
        $statement = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();

        return $value === false || $value === null ? $default : (string) $value;
    }

    public static function saveSetting(string $key, ?string $value): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $statement->execute([
            'setting_key' => $key,
            'setting_value' => self::nullableString($value),
        ]);
    }

    private static function uniqueSlug(string $table, string $slug, ?int $ignoreId = null): string
    {
        $slug = $slug ?: 'item';
        $base = $slug;
        $counter = 2;

        while (self::slugExists($table, $slug, $ignoreId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private static function slugExists(string $table, string $slug, ?int $ignoreId): bool
    {
        if (!in_array($table, ['posts', 'categories'], true)) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public static function slugify(string $text): string
    {
        $text = trim($text);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted !== false ? $converted : $text;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text ?: 'item';
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private static function normalizeDateTime($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
