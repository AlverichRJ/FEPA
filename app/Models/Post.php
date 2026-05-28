<?php

namespace App\Models;

use App\Core\Database;
use PDO;

final class Post
{
    private const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=900&q=80';

    public static function latest(int $limit = 6, int $offset = 0): array
    {
        $statement = Database::connection()->prepare(self::baseQuery() . '
            WHERE ' . self::publishedCondition() . '
            GROUP BY p.id
            ORDER BY p.published_at DESC, p.id DESC
            LIMIT :limit OFFSET :offset'
        );
        self::bindPublishedCondition($statement);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map([self::class, 'normalize'], $statement->fetchAll());
    }

    public static function featured(): ?array
    {
        $statement = Database::connection()->prepare(self::baseQuery() . '
            WHERE ' . self::publishedCondition() . '
            GROUP BY p.id
            ORDER BY p.is_featured DESC, p.published_at DESC, p.id DESC
            LIMIT 1'
        );
        self::bindPublishedCondition($statement);
        $statement->execute();
        $post = $statement->fetch();

        return $post ? self::normalize($post) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $statement = Database::connection()->prepare(self::baseQuery() . '
            WHERE p.slug = :slug AND ' . self::publishedCondition() . '
            GROUP BY p.id
            LIMIT 1'
        );
        $statement->bindValue('slug', $slug);
        self::bindPublishedCondition($statement);
        $statement->execute();
        $post = $statement->fetch();

        return $post ? self::normalize($post) : null;
    }

    public static function byCategory(int $categoryId, int $limit = 6, int $offset = 0): array
    {
        $statement = Database::connection()->prepare(self::baseQuery() . '
            WHERE p.category_id = :category_id AND ' . self::publishedCondition() . '
            GROUP BY p.id
            ORDER BY p.published_at DESC, p.id DESC
            LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        self::bindPublishedCondition($statement);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map([self::class, 'normalize'], $statement->fetchAll());
    }

    public static function popular(int $limit = 3): array
    {
        $statement = Database::connection()->prepare(self::baseQuery() . '
            WHERE ' . self::publishedCondition() . '
            GROUP BY p.id
            ORDER BY p.views DESC, p.published_at DESC
            LIMIT :limit'
        );
        self::bindPublishedCondition($statement);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([self::class, 'normalize'], $statement->fetchAll());
    }

    public static function viral(int $limit = 3): array
    {
        $statement = Database::connection()->prepare(self::baseQuery() . '
            WHERE ' . self::publishedCondition() . '
            GROUP BY p.id
            ORDER BY (p.views + (CASE WHEN p.is_featured = 1 THEN 100 ELSE 0 END)) DESC, p.updated_at DESC
            LIMIT :limit'
        );
        self::bindPublishedCondition($statement);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([self::class, 'normalize'], $statement->fetchAll());
    }

    public static function related(int $postId, ?int $categoryId, int $limit = 3): array
    {
        $sql = self::baseQuery() . '
            WHERE p.id <> :post_id
              AND ' . self::publishedCondition();

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
        }

        $sql .= '
            GROUP BY p.id
            ORDER BY p.published_at DESC, p.id DESC
            LIMIT :limit';

        $statement = Database::connection()->prepare($sql);
        $statement->bindValue('post_id', $postId, PDO::PARAM_INT);
        self::bindPublishedCondition($statement);
        if ($categoryId !== null) {
            $statement->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        }
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([self::class, 'normalize'], $statement->fetchAll());
    }

    public static function incrementViews(int $postId): void
    {
        $statement = Database::connection()->prepare('UPDATE posts SET views = views + 1 WHERE id = :id');
        $statement->execute(['id' => $postId]);
    }

    private static function publishedCondition(): string
    {
        return 'p.status = :status AND (p.published_at IS NULL OR p.published_at <= :published_now)';
    }

    private static function bindPublishedCondition($statement): void
    {
        $statement->bindValue('status', 'published');
        $statement->bindValue('published_now', date('Y-m-d H:i:s'));
    }

    private static function baseQuery(): string
    {
        return "SELECT
                p.id,
                p.category_id,
                p.author_id,
                p.title,
                p.slug,
                p.excerpt,
                p.content,
                p.featured_image,
                p.is_featured,
                p.views,
                p.published_at,
                p.created_at,
                p.updated_at,
                c.name AS category,
                c.slug AS category_slug,
                u.name AS author,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags
            FROM posts p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN users u ON u.id = p.author_id
            LEFT JOIN post_tags pt ON pt.post_id = p.id
            LEFT JOIN tags t ON t.id = pt.tag_id";
    }

    private static function normalize(array $post): array
    {
        $tags = [];
        if (!empty($post['tags'])) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $post['tags']))));
        }

        return [
            'id' => (int) $post['id'],
            'category_id' => $post['category_id'] !== null ? (int) $post['category_id'] : null,
            'title' => $post['title'],
            'slug' => $post['slug'],
            'category' => $post['category'] ?: 'Sin categoría',
            'category_slug' => $post['category_slug'] ?: '',
            'date' => formatDateSpanish($post['published_at'] ?: $post['created_at']),
            'image' => $post['featured_image'] ?: self::DEFAULT_IMAGE,
            'excerpt' => $post['excerpt'] ?: self::makeExcerpt($post['content']),
            'content' => $post['content'],
            'author' => $post['author'] ?: 'FEPA Veterinaria',
            'reading_time' => readingTime($post['content']),
            'views' => (int) $post['views'],
            'tags' => $tags,
        ];
    }

    private static function makeExcerpt(string $content): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');

        if (strlen($plain) <= 160) {
            return $plain;
        }

        return substr($plain, 0, 157) . '...';
    }
}
