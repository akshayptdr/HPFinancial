<?php
namespace App\Models;
use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function forUser(int $uid, int $limit = 50): array
    {
        return self::query("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT $limit", [$uid]);
    }

    public static function unreadCount(int $uid): int
    {
        return (int) self::scalar("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$uid]);
    }

    public static function push(int $uid, string $type, string $title, ?string $msg = null, ?string $link = null): void
    {
        self::insert(['user_id'=>$uid,'type'=>$type,'title'=>$title,'message'=>$msg,'link'=>$link]);
    }
}
