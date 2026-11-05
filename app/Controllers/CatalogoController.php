<?php
// =====================================================================
// CatalogoController — todas as listagens partilham a mesma view e os
// mesmos filtros: /produtos, /categoria/{slug}, /marca/{slug},
// /promocoes, /novidades e /pesquisa
// =====================================================================

namespace App\Controllers;

use Core\Controller;
use Core\Seo;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Produto;

class CatalogoController extends Controller
{
    public function produtos(): void
    {
        $this->listagem([], 'Todos os produtos', 'Smartphones, computadores, TVs, gaming e electrodomésticos com garantia oficial.', [['Início', '/'], ['Produtos', '/produtos']]);
    }

    public function categoria(string $slug): void
    {
        $categoria = (new Categoria())->porSlug($slug);
        if (!$categoria) {
            (new ErroController())->naoEncontrado();
            return;
        }
        $this->listagem(
            ['categoria_id' => $categoria['id']],
            $categoria['nome'],
            $categoria['descricao'],
            [['Início', '/'], ['Produtos', '/produtos'], [$categoria['nome'], '/categoria/' . $slug]],
            [
                'titulo'    => $categoria['meta_titulo'] ?: $categoria['nome'] . ' em Angola — preços em Kz',
                'descricao' => $categoria['meta_descricao'] ?: $categoria['descricao'] . '. Entrega em todo o país e pagamento com Multicaixa Express.',
            ]
        );
    }

    public function marca(string $slug): void
    {
        $marca = (new Marca())->porSlug($slug);
        if (!$marca) {
            (new ErroController())->naoEncontrado();
            return;
        }
        $this->listagem(
            ['marca_id' => $marca['id']],
            $marca['nome'],
            'Produtos ' . $marca['nome'] . ' novos, com garantia oficial em Angola.',
            [['Início', '/'], ['Marcas', '/marcas'], [$marca['nome'], '/marca/' . $slug]],
            ['titulo' => $marca['nome'] . ' Angola — loja oficial online'],
            false
        );
    }

    public function promocoes(): void
    {
        $this->listagem(
            ['promo' => 1, '_fixo_promo' => true, 'ordem' => $_GET['ordem'] ?? 'desconto'],
            'Promoções',
            'Preços reduzidos por tempo limitado, enquanto houver stock.',
            [['Início', '/'], ['Promoções', '/promocoes']]
        );
    }

    public function novidades(): void
    {
        $this->listagem(
            ['novidade' => 1, '_fixo_novidade' => true],
            'Novidades',
            'Os lançamentos mais recentes que chegaram à loja.',
            [['Início', '/'], ['Novidades', '/novidades']]
        );
    }

    public function pesquisa(): void
    {
        $q = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 80));
        $this->listagem(
            ['q' => $q],
            $q !== '' ? 'Resultados para "' . $q . '"' : 'Pesquisa',
            null,
            [['Início', '/'], ['Pesquisa', '/pesquisa']],
            ['titulo' => $q !== '' ? 'Pesquisa: ' . $q : 'Pesquisa', 'noindex' => true]
        );
    }

    /** Lista todas as categorias (usada no menu inferior do mobile). */
    public function categorias(): void
    {
        $this->view('paginas/categorias', [
            'categorias' => (new Categoria())->todasActivas(),
            'seo' => ['titulo' => 'Categorias', 'descricao' => 'Todas as categorias de produtos da DIOTEKNO: smartphones, computadores, TVs, gaming, electrodomésticos e mais.'],
        ]);
    }

    // ---------------------------------------------------------------------

    private function listagem(array $base, string $titulo, ?string $descricao, array $breadcrumb, array $seo = [], bool $mostrarMarcas = true): void
    {
        $filtros = array_merge($this->filtrosDoPedido(), $base);
        $produto = new Produto();

        $resultado = $produto->listar($filtros);

        // Filtros aplicados ou páginas > 1 não devem ser indexados (evita conteúdo duplicado)
        $temFiltros = !empty($_GET['marca']) || !empty($_GET['min']) || !empty($_GET['max']) || !empty($_GET['stock']) || !empty($_GET['ordem']);
        if ($temFiltros || $resultado['pagina'] > 1) {
            $seo['noindex'] = true;
        }

        $seo['titulo']    ??= $titulo;
        $seo['descricao'] ??= $descricao;
        $seo['canonical'] = URL_ORIGEM . strtok($_SERVER['REQUEST_URI'], '?');
        $seo['jsonld'][]  = Seo::breadcrumb($breadcrumb);
        $seo['jsonld'][]  = [
            '@context' => 'https://schema.org',
            '@type'    => 'ItemList',
            'itemListElement' => array_map(fn ($p, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'url' => URL_BASE . '/produto/' . $p['slug'],
            ], $resultado['itens'], array_keys($resultado['itens'])),
        ];

        $this->view('catalogo/lista', [
            'titulo'        => $titulo,
            'descricao'     => $descricao,
            'breadcrumb'    => $breadcrumb,
            'resultado'     => $resultado,
            'filtros'       => $filtros,
            'facetaMarcas'  => $mostrarMarcas ? $produto->facetaMarcas($filtros) : [],
            'facetaPreco'   => $produto->facetaPreco($filtros),
            'mostrarMarcas' => $mostrarMarcas,
            'seo'           => $seo,
        ]);
    }

    /** Lê e higieniza os filtros da query string. */
    private function filtrosDoPedido(): array
    {
        $marcas = array_filter(
            (array) ($_GET['marca'] ?? []),
            fn ($m) => is_string($m) && preg_match('/^[a-z0-9-]{1,100}$/', $m)
        );
        $ordem = $_GET['ordem'] ?? 'relevancia';

        return [
            'marcas'   => array_values($marcas),
            'min'      => max(0, (int) ($_GET['min'] ?? 0)) ?: null,
            'max'      => max(0, (int) ($_GET['max'] ?? 0)) ?: null,
            'promo'    => !empty($_GET['promo']),
            'novidade' => !empty($_GET['novidade']),
            'stock'    => !empty($_GET['stock']),
            'ordem'    => array_key_exists($ordem, Produto::ORDENS) ? $ordem : 'relevancia',
            'pagina'   => max(1, (int) ($_GET['pagina'] ?? 1)),
        ];
    }
}
