<?php

namespace App\Controllers;

use Core\Controller;
use Core\Seo;
use App\Models\Avaliacao;
use App\Models\Produto;
use App\Servicos\Comparador;
use App\Servicos\Fidelidade;
use Core\Auth;
use App\Models\ZonaEntrega;

class ProdutoController extends Controller
{
    public function mostrar(string $slug): void
    {
        $modelo  = new Produto();
        $produto = $modelo->porSlug($slug);
        if (!$produto) {
            (new ErroController())->naoEncontrado();
            return;
        }
        $modelo->registarVisualizacao((int) $produto['id']);

        $breadcrumb = [
            ['Início', '/'],
            [$produto['categoria_nome'], '/categoria/' . $produto['categoria_slug']],
            [$produto['nome'], '/produto/' . $slug],
        ];

        $imagemAbsoluta = URL_ORIGEM . imagemProduto($produto);
        $jsonProduto = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $produto['nome'],
            'sku'         => $produto['sku'],
            'description' => $produto['resumo'] ?: strip_tags((string) $produto['descricao']),
            'image'       => [$imagemAbsoluta],
            'category'    => $produto['categoria_nome'],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => URL_BASE . '/produto/' . $slug,
                'priceCurrency' => 'AOA',
                'price'         => number_format((float) $produto['preco'], 2, '.', ''),
                'availability'  => (int) $produto['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller'        => ['@type' => 'Organization', 'name' => NOME_SITE],
            ],
        ];
        if ($produto['marca_nome']) {
            $jsonProduto['brand'] = ['@type' => 'Brand', 'name' => $produto['marca_nome']];
        }
        if ((int) $produto['total_avaliacoes'] > 0) {
            $jsonProduto['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $produto['avaliacao_media'],
                'reviewCount' => (int) $produto['total_avaliacoes'],
            ];
        }

        // Avaliações
        $aval = new Avaliacao();
        $avaliacoes = $aval->aprovadas((int) $produto['id'], 'recentes', 7);
        $elegibilidade = Auth::autenticado() ? $aval->elegibilidade((int) Auth::id(), (int) $produto['id']) : null;
        if ($avaliacoes) {
            $jsonProduto['review'] = array_map(fn ($a) => [
                '@type'         => 'Review',
                'reviewRating'  => ['@type' => 'Rating', 'ratingValue' => (int) $a['nota'], 'bestRating' => 5],
                'author'        => ['@type' => 'Person', 'name' => Avaliacao::nomePublico($a['nome'])],
                'datePublished' => date('Y-m-d', strtotime($a['criado_em'])),
                'reviewBody'    => $a['comentario'] ?: ($a['titulo'] ?: ''),
            ], array_slice($avaliacoes, 0, 5));
        }

        $luanda = array_values(array_filter((new ZonaEntrega())->paraProvincia('Luanda'), fn ($z) => !$z['expressa']));

        $this->view('produto/mostrar', [
            'produto'        => $produto,
            'imagens'        => $modelo->imagens((int) $produto['id']),
            'especificacoes' => $modelo->especificacoes((int) $produto['id']),
            'relacionados'   => $modelo->relacionados($produto),
            'breadcrumb'     => $breadcrumb,
            'entregaLuanda'  => $luanda[0] ?? null,
            'avaliacoes'     => array_slice($avaliacoes, 0, 6),
            'maisAvaliacoes' => count($avaliacoes) > 6,
            'distribuicao'   => $aval->distribuicao((int) $produto['id']),
            'elegibilidade'  => $elegibilidade,
            'naComparacao'   => in_array((int) $produto['id'], Comparador::ids(), true),
            'pontosGanhar'   => Fidelidade::pontosPorValor((float) $produto['preco']),
            'seo' => [
                'titulo'    => $produto['meta_titulo'] ?: $produto['nome'] . ' — ' . strip_tags(kz($produto['preco'])),
                'descricao' => $produto['meta_descricao'] ?: ($produto['resumo'] . ' Garantia oficial e entrega em Angola.'),
                'imagem'    => $imagemAbsoluta,
                'tipo'      => 'product',
                'jsonld'    => [$jsonProduto, Seo::breadcrumb($breadcrumb)],
            ],
        ]);
    }
}
