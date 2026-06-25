<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, string $handler, array $opts = []): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'handler' => $handler,
            'auth'    => $opts['auth'] ?? true,       // require login by default
            'perm'    => $opts['perm'] ?? null,       // required permission
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) continue;
            $pattern = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $r['path']) . '$#';
            if (!preg_match($pattern, $uri, $m)) continue;
            array_shift($m);

            // CSRF on writes
            if ($method === 'POST') Csrf::check();

            // Auth
            if ($r['auth'] && !Auth::check()) {
                Session::set('intended', $uri);
                $this->go('/login');
            }
            // Force password change
            if ($r['auth'] && Auth::check()) {
                $u = Auth::user();
                if ((int)$u['must_change_password'] === 1
                    && !in_array($r['path'], ['/password/change', '/logout'], true)) {
                    $this->go('/password/change');
                }
            }
            // Permission
            if ($r['perm'] && !Auth::can($r['perm'])) {
                http_response_code(403);
                (new class extends Controller {})->view('errors/403', [], 'app');
                return;
            }

            [$class, $action] = explode('@', $r['handler']);
            $fqcn = 'App\\Controllers\\' . $class;
            (new $fqcn)->$action(...$m);
            return;
        }
        http_response_code(404);
        if (Auth::check()) {
            (new class extends Controller {})->view('errors/404', [], 'app');
        } else {
            echo '404 Not Found';
        }
    }

    private function go(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}
