<?php
namespace App\Core;

class Request
{
    public static function method(): string
    {
        $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($m === 'POST' && isset($_POST['_method'])) {
            $m = strtoupper($_POST['_method']);
        }
        return $m;
    }

    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // strip the base subdir (…/public)
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script !== '/' && $script !== '' && strpos($uri, $script) === 0) {
            $uri = substr($uri, strlen($script));
        }
        $uri = '/' . ltrim($uri, '/');
        return rtrim($uri, '/') ?: '/';
    }

    public static function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function all(): array { return array_merge($_GET, $_POST); }

    public static function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) { $out[$k] = self::input($k); }
        return $out;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function isPost(): bool { return self::method() === 'POST'; }
}
