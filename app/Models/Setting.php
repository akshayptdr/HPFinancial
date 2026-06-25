<?php
namespace App\Models;
use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, $default = null)
    {
        $v = self::scalar("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $v === false ? $default : $v;
    }

    public static function put(string $key, $value): void
    {
        \App\Core\Database::pdo()->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        )->execute([$key, (string)$value]);
    }
}
