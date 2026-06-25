<?php
namespace App\Models;
use App\Core\Model;
use App\Core\Auth;

class ActivityLog extends Model
{
    protected static string $table = 'activity_log';

    public static function record(string $action, string $entity, $entityId = null, ?string $detail = null): void
    {
        self::insert([
            'user_id'   => Auth::id(),
            'action'    => $action,
            'entity'    => $entity,
            'entity_id' => $entityId,
            'detail'    => $detail,
        ]);
    }

    public static function recent(int $limit = 8): array
    {
        return self::query("SELECT a.*, u.name AS user_name FROM activity_log a
            LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT $limit");
    }
}
