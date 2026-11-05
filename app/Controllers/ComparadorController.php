<?php

namespace App\Controllers;

use Core\Controller;
use App\Servicos\Comparador;

class ComparadorController extends Controller
{
    public function index(): void
    {
        $this->view('comparar/index', Comparador::montar() + [
            'seo' => ['titulo' => 'Comparar produtos', 'noindex' => true],
        ]);
    }

    public function alternar(): void
    {
        $r = Comparador::alternar((int) ($_POST['produto_id'] ?? 0));
        $this->json($r, $r['ok'] ? 200 : 422);
    }

    public function limpar(): void
    {
        Comparador::limpar();
        if (eAjax()) {
            $this->json(['ok' => true, 'total' => 0]);
        }
        $this->redirecionar('/produtos');
    }
}
