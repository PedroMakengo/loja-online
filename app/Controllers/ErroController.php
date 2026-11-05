<?php

namespace App\Controllers;

use Core\Controller;

class ErroController extends Controller
{
    public function naoEncontrado(): void
    {
        http_response_code(404);
        if (eAjax()) {
            $this->json(['ok' => false, 'mensagem' => 'Recurso não encontrado.'], 404);
        }
        $this->view('erros/404', ['seo' => ['titulo' => 'Página não encontrada', 'noindex' => true]]);
    }
}
