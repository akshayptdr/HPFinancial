<?php
namespace App\Core;

class Auth
{
    private static ?array $user = null;
    private static ?array $permissions = null;

    public static function attempt(string $mobile, string $password): array
    {
        $mobile = preg_replace('/\s+/', '', $mobile);
        $st = Database::pdo()->prepare("SELECT * FROM users WHERE mobile = ? LIMIT 1");
        $st->execute([$mobile]);
        $user = $st->fetch();
        if (!$user) return ['ok' => false, 'error' => 'No account found for this mobile number.'];
        if ($user['status'] !== 'active') return ['ok' => false, 'error' => 'This account is disabled. Contact your administrator.'];
        if (!password_verify($password, $user['password_hash'])) return ['ok' => false, 'error' => 'Incorrect password.'];
        self::login($user);
        return ['ok' => true, 'user' => $user];
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Database::pdo()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        self::$user = null;
        Session::regenerate();
    }

    public static function check(): bool { return self::user() !== null; }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id ? (int)$id : null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) return self::$user;
        $id = Session::get('user_id');
        if (!$id) return null;
        $st = Database::pdo()->prepare("SELECT u.*, r.slug AS role_slug, r.name AS role_name
            FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1");
        $st->execute([$id]);
        self::$user = $st->fetch() ?: null;
        return self::$user;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u && $u['role_slug'] === 'admin';
    }

    public static function permissions(): array
    {
        if (self::$permissions !== null) return self::$permissions;
        $u = self::user();
        if (!$u) return self::$permissions = [];
        $st = Database::pdo()->prepare("SELECT p.slug FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?");
        $st->execute([$u['role_id']]);
        self::$permissions = array_column($st->fetchAll(), 'slug');
        return self::$permissions;
    }

    public static function can(string $perm): bool
    {
        return self::isAdmin() || in_array($perm, self::permissions(), true);
    }
}
