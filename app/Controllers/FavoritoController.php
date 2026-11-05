<?php

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use App\Models\Favorito;

class FavoritoController extends Controller
{
    /** POST /favoritos/alternar — AJAX. Sem sessão devolve 401 com o link de entrada. */
    public function alternar(): void
    {
        if (!Auth::autenticado()) {
            $this->json([
                'ok'       => false,
                'mensagem' => 'Entre na sua conta para guardar favoritos.',
                'entrar'   => url('/entrar?voltar=' . urlencode(caminhoSeguro($_POST['voltar'] ?? null, '/favoritos'))),
            ], 401);
        }
        $modelo = new Favorito();
        $activo = $modelo->alternar((int) Auth::id(), (int) ($_POST['produto_id'] ?? 0));
        $this->json([
            'ok'       => true,
            'favorito' => $activo,
            'total'    => count($modelo->ids((int) Auth::id())),
            'mensagem' => $activo ? 'Guardado nos favoritos.' : 'Removido dos favoritos.',
        ]);
    }
}
