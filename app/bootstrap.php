<?php
/**
 * HP Financial — bootstrap. Loads env, paths, autoloader, helpers.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');

// --- minimal .env loader ---
(function () {
    $file = BASE_PATH . '/.env';
    if (!is_file($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k); $v = trim($v);
        if ($k !== '') { $_ENV[$k] = $v; putenv("$k=$v"); }
    }
})();

function env(string $key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// --- PSR-4-ish autoloader: App\ => app/ ---
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = APP_PATH . '/' . $rel . '.php';
    if (is_file($file)) require $file;
});

require APP_PATH . '/Helpers/functions.php';

// error handling
error_reporting(E_ALL);
ini_set('display_errors', env('APP_ENV') === 'local' ? '1' : '0');
ini_set('log_errors', '1');
@ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
date_default_timezone_set('Asia/Kolkata');
