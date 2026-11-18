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
        $modelo = new Categoria();
        $categoria = $modelo->porSlug($slug);
        if (!$categoria) {
            (new ErroController())->naoEncontrado();
            return;
        }
        // Inclui os produtos das subcategorias (ex.: Informática mostra Desktops, Portáteis…)
        $this->listagem(
            ['categoria_ids' => $modelo->idsDoRamo((int) $categoria['id'])],
            $categoria['nome'],
            $categoria['descricao'],
            $modelo->breadcrumb((int) $categoria['id']),
            [
                'titulo'    => $categoria['meta_titulo'] ?: $categoria['nome'] . ' em Angola — preços em Kz',
                'descricao' => $categoria['meta_descricao'] ?: ($categoria['descricao'] ? rtrim($categoria['descricao'], '.') . '. ' : '') . 'Entrega em todo o país e pagamento com Multicaixa Express.',
            ],
            true,
            $categoria
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
            'categorias' => (new Categoria())->arvoreMenu(),
            'seo' => ['titulo' => 'Categorias', 'descricao' => 'Todas as categorias de produtos da DIOTEKNO: smartphones, computadores, TVs, gaming, electrodomésticos e mais.'],
        ]);
    }

    // ---------------------------------------------------------------------

    private function listagem(array $base, string $titulo, ?string $descricao, array $breadcrumb, array $seo = [], bool $mostrarMarcas = true, ?array $categoria = null): void
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
            // Navegação na árvore: subcategorias da actual, ou irmãs quando é a última do ramo
            'subcategorias' => $categoria ? $this->navegacaoCategoria($categoria) : [],
            'categoriaActual' => $categoria,
            'seo'           => $seo,
        ]);
    }

    /** Filhas da categoria; se não tiver, as irmãs (para saltar entre subcategorias do mesmo pai). */
    private function navegacaoCategoria(array $categoria): array
    {
        // Sem ramos vazios, mas a categoria actual aparece sempre (para ficar marcada)
        $semVazias = fn (array $nos) => array_values(array_filter($nos,
            fn ($n) => (int) $n['total_produtos'] > 0 || (int) $n['id'] === (int) $categoria['id']));

        if ($filhas = $semVazias($categoria['filhos'])) {
            return $filhas;
        }
        if ($categoria['parent_id']) {
            $pai = (new Categoria())->caminho((int) $categoria['parent_id']);
            $irmas = $pai ? $semVazias(end($pai)['filhos']) : [];
            return count($irmas) > 1 ? $irmas : [];
        }
        return [];
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
