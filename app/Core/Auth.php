<?php

namespace App\Core;

use PDO;

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('FEPA_ADMIN_SESSION');
            session_start();
        }
    }

    public static function user(): ?array
    {
        self::start();

        if (empty($_SESSION['admin_user_id'])) {
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT id, name, email, role
             FROM users
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $_SESSION['admin_user_id']]);
        $user = $statement->fetch();

        if (!$user) {
            self::logout();
            return null;
        }

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(string $email, string $password): bool
    {
        self::start();

        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = :email AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_user_name'] = $user['name'];

        return true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public static function hasUsers(): bool
    {
        $statement = Database::connection()->query('SELECT COUNT(*) FROM users WHERE is_active = 1');

        return (int) $statement->fetchColumn() > 0;
    }

    public static function createFirstAdmin(string $name, string $email, string $password): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, 1)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
