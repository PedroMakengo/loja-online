<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Categoria;
use App\Models\Produto;

class PesquisaController extends Controller
{
    /** GET /api/pesquisa?q= — sugestões para o autocomplete (JSON). */
    public function sugestoes(): void
    {
        $q = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 80));
        if (mb_strlen($q) < 2) {
            $this->json(['produtos' => [], 'categorias' => []]);
        }

        $produtos = array_map(fn ($p) => [
            'nome'    => $p['nome'],
            'marca'   => $p['marca_nome'],
            'preco'   => kz($p['preco']),
            'anterior'=> $p['preco_anterior'] > $p['preco'] ? kz($p['preco_anterior']) : null,
            'imagem'  => imagemProduto($p),
            'url'     => url('/produto/' . $p['slug']),
        ], (new Produto())->sugestoes($q, 6));

        $qMin = mb_strtolower($q);
        $categorias = array_values(array_map(
            fn ($c) => ['nome' => $c['nome'], 'url' => url('/categoria/' . $c['slug'])],
            array_filter((new Categoria())->todasActivas(), fn ($c) => str_contains(mb_strtolower($c['nome']), $qMin))
        ));

        header('Cache-Control: private, max-age=60');
        $this->json([
            'produtos'   => $produtos,
            'categorias' => array_slice($categorias, 0, 3),
            'todos'      => url('/pesquisa?q=' . urlencode($q)),
        ]);
    }
}
