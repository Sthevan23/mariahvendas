<?php

namespace App\Controllers;

use App\Services\Analytics;

class TrackController
{
    /** Recebe o aviso do rastreador do navegador (tempo na página e cliques). */
    public function ping(): void
    {
        ignore_user_abort(true);
        http_response_code(204);
        header('Cache-Control: no-store');
        $raw = (string) file_get_contents('php://input', false, null, 0, 20000);
        $data = json_decode($raw, true);
        // Libera o navegador antes de consultar a cidade (pode demorar uns segundos).
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if (is_array($data)) {
            Analytics::ping($data);
        }
    }
}
