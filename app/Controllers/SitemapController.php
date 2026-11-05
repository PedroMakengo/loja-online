<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Produto;

class SitemapController extends Controller
{
    public function index(): void
    {
        $urls = [
            ['/', '1.0', 'daily'],
            ['/produtos', '0.8', 'daily'],
            ['/promocoes', '0.9', 'daily'],
            ['/novidades', '0.8', 'weekly'],
            ['/categorias', '0.6', 'monthly'],
            ['/marcas', '0.6', 'monthly'],
            ['/entregas', '0.4', 'monthly'],
            ['/sobre', '0.3', 'yearly'],
            ['/contactos', '0.3', 'yearly'],
            ['/faq', '0.4', 'monthly'],
            ['/privacidade', '0.1', 'yearly'],
            ['/blog', '0.7', 'weekly'],
            ['/termos', '0.1', 'yearly'],
        ];
        foreach ((new Categoria())->todasActivas() as $c) {
            $urls[] = ['/categoria/' . $c['slug'], '0.8', 'daily'];
        }
        foreach ((new Marca())->todasActivas() as $m) {
            $urls[] = ['/marca/' . $m['slug'], '0.6', 'weekly'];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$caminho, $prioridade, $freq]) {
            echo "  <url><loc>" . htmlspecialchars(URL_BASE . $caminho) . "</loc><changefreq>$freq</changefreq><priority>$prioridade</priority></url>\n";
        }
        foreach ((new Produto())->paraSitemap() as $p) {
            echo "  <url><loc>" . htmlspecialchars(URL_BASE . '/produto/' . $p['slug']) . "</loc><lastmod>" . date('Y-m-d', strtotime($p['actualizado_em'])) . "</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
        }
        foreach ((new \App\Models\Blog())->paraSitemap() as $b) {
            echo "  <url><loc>" . htmlspecialchars(URL_BASE . '/blog/' . $b['slug']) . "</loc><lastmod>" . date('Y-m-d', strtotime($b['data'])) . "</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /carrinho\nDisallow: /checkout\nDisallow: /conta\nDisallow: /favoritos\nDisallow: /entrar\nDisallow: /registar\nDisallow: /recuperar-senha\nDisallow: /redefinir-senha\nDisallow: /api/\nDisallow: /pesquisa\nDisallow: /admin\nDisallow: /comparar\n";
        echo "Disallow: /*?*marca\nDisallow: /*?*ordem=\n\n";
        echo "Sitemap: " . URL_BASE . "/sitemap.xml\n";
        exit;
    }
}
