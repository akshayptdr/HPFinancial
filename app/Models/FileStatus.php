<?php
namespace App\Models;
use App\Core\Model;

class FileStatus extends Model
{
    protected static string $table = 'file_statuses';

    public static function activeList(): array
    {
        return self::query("SELECT * FROM file_statuses WHERE status='active' ORDER BY sort_order");
    }
}
