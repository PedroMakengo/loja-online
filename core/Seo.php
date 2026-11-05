<?php
// =====================================================================
// core/Seo.php — Metatags por página: title, description, OG,
// canonical, Twitter e JSON-LD (Schema.org)
// =====================================================================

namespace Core;

class Seo
{
    public static function montar(array $seo): array
    {
        $caminhoActual = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

        $titulo = $seo['titulo'] ?? null;
        return [
            'titulo'    => $titulo ? $titulo . ' | ' . NOME_SITE : NOME_SITE . ' — Loja de Tecnologia em Angola',
            'descricao' => mb_strimwidth($seo['descricao'] ?? 'Smartphones, computadores, TVs, gaming e electrodomésticos com garantia oficial. Pague com Multicaixa Express e receba em todo o país.', 0, 160, '…'),
            'imagem'    => $seo['imagem'] ?? URL_BASE . '/assets/img/og-diotekno.png',
            'canonical' => $seo['canonical'] ?? URL_ORIGEM . $caminhoActual,
            'tipo'      => $seo['tipo'] ?? 'website',
            'jsonld'    => $seo['jsonld'] ?? [],
            'noindex'   => $seo['noindex'] ?? false,
        ];
    }

    /** Breadcrumb em JSON-LD a partir de [['nome','url'], ...] */
    public static function breadcrumb(array $itens): array
    {
        $lista = [];
        foreach ($itens as $i => [$nome, $caminho]) {
            $lista[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $nome,
                'item'     => URL_BASE . $caminho,
            ];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $lista];
    }
}
