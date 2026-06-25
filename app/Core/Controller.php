<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);
        $content = (function () use ($template, $data) {
            extract($data, EXTR_SKIP);
            ob_start();
            require APP_PATH . '/Views/' . $template . '.php';
            return ob_get_clean();
        })();
        require APP_PATH . '/Views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $ref);
        exit;
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function authorize(string $perm): void
    {
        if (!Auth::can($perm)) {
            http_response_code(403);
            $this->view('errors/403', [], 'app');
            exit;
        }
    }

    protected function user(): ?array { return Auth::user(); }
}
