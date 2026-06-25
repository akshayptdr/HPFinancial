<?php
namespace App\Models;
use App\Core\Model;

class Service extends Model
{
    protected static string $table = 'services';

    public static function activeList(): array
    {
        return self::query("SELECT * FROM services WHERE status='active' ORDER BY id");
    }

    public static function byCode(string $code): ?array
    {
        return self::firstWhere('code', $code);
    }
}
