<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Banner;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Produto;

class HomeController extends Controller
{
    public function index(): void
    {
        $produto = new Produto();
        $ofertas = $produto->ofertasDoDia(10);

        // A contagem das promoções do dia termina na oferta que acaba primeiro
        $fimOfertas = $ofertas ? date('c', strtotime(min(array_column($ofertas, 'oferta_ate')))) : null;

        $this->view('home/index', [
            'banners'            => (new Banner())->activos(),
            'categoriasDestaque' => (new Categoria())->destaque(),
            'maisVendidos'       => $produto->maisVendidos(10),
            'ofertas'            => $ofertas,
            'fimOfertas'         => $fimOfertas,
            'novidades'          => $produto->novidades(10),
            'marcas'             => (new Marca())->destaque(),
            'seo' => [
                'jsonld' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type'    => 'OnlineStore',
                        'name'     => NOME_SITE,
                        'url'      => URL_BASE . '/',
                        'logo'     => URL_BASE . '/assets/img/logo.png',
                        'email'    => EMAIL_LOJA,
                        'telephone'=> TELEFONE_LOJA,
                        'address'  => ['@type' => 'PostalAddress', 'streetAddress' => MORADA_LOJA, 'addressLocality' => 'Luanda', 'addressCountry' => 'AO'],
                        'currenciesAccepted' => 'AOA',
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type'    => 'WebSite',
                        'name'     => NOME_SITE,
                        'url'      => URL_BASE . '/',
                        'potentialAction' => [
                            '@type'       => 'SearchAction',
                            'target'      => URL_BASE . '/pesquisa?q={search_term_string}',
                            'query-input' => 'required name=search_term_string',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
