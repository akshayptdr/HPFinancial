<?php
namespace App\Models;
use App\Core\Model;

class Role extends Model
{
    protected static string $table = 'roles';

    public static function permissionIds(int $roleId): array
    {
        return array_map('intval', array_column(
            self::query("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$roleId]),
            'permission_id'
        ));
    }

    public static function setPermissions(int $roleId, array $permIds): void
    {
        $db = self::query("SELECT 1", []); // noop to ensure connection
        \App\Core\Database::pdo()->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);
        $ins = \App\Core\Database::pdo()->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permIds as $pid) { $ins->execute([$roleId, (int)$pid]); }
    }
}
