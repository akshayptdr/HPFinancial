<?php
namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name(env('SESSION_NAME', 'hpf_sess'));
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }

    public static function get(string $k, $d = null) { return $_SESSION[$k] ?? $d; }
    public static function set(string $k, $v): void { $_SESSION[$k] = $v; }
    public static function forget(string $k): void { unset($_SESSION[$k]); }
    public static function regenerate(): void { session_regenerate_id(true); }

    public static function flash(string $type, ?string $msg = null)
    {
        if ($msg === null) {
            $m = $_SESSION['_flash'][$type] ?? null;
            unset($_SESSION['_flash'][$type]);
            return $m;
        }
        $_SESSION['_flash'][$type] = $msg;
        return null;
    }
}
