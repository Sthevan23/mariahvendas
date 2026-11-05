<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View não encontrada: ' . htmlspecialchars($view);
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = dirname(__DIR__) . '/Views/' . $layout . '.php';
        require $layoutFile;
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
