<?php

namespace App\Controllers;

use App\Services\MediaStore;

class MediaController
{
    public function show(string $name): void
    {
        $media = MediaStore::read($name);
        if ($media === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Não encontrado';
            return;
        }
        [$body, $mime] = $media;
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($body));
        header_remove('Pragma');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        echo $body;
    }
}
