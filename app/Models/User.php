<?php
namespace App\Models;
use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function withRoles(): array
    {
        return self::query("SELECT u.*, r.name AS role_name, r.slug AS role_slug
            FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id");
    }

    public static function activeList(): array
    {
        return self::query("SELECT id, name FROM users WHERE status='active' ORDER BY name");
    }
}
