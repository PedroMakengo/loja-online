<?php

namespace App\Controllers;

use Core\AdminAuth;
use Core\Controller;
use Core\Seo;
use App\Models\Blog;
use App\Models\Produto;

class BlogController extends Controller
{
    public function index(): void
    {
        $this->listagem(null);
    }

    public function categoria(string $slug): void
    {
        $cat = (new Blog())->categoriaPorSlug($slug);
        if (!$cat) {
            (new ErroController())->naoEncontrado();
            return;
        }
        $this->listagem($cat);
    }

    private function listagem(?array $categoria): void
    {
        $blog = new Blog();
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        // Na primeira página da listagem geral, o artigo em destaque sai da grelha
        $destaque = !$categoria && $pagina === 1 ? $blog->destaque() : null;
        $lista = $blog->publicados($categoria['id'] ?? null, $pagina, 9, $destaque['id'] ?? null);

        $titulo = $categoria ? $categoria['nome'] : 'Blog DIOTEKNO';
        $breadcrumb = [['Início', '/'], ['Blog', '/blog']];
        if ($categoria) {
            $breadcrumb[] = [$categoria['nome'], '/blog/categoria/' . $categoria['slug']];
        }

        $this->view('blog/index', [
            'categoria'  => $categoria,
            'categorias' => $blog->categorias(),
            'destaque'   => $destaque,
            'lista'      => $lista,
            'breadcrumb' => $breadcrumb,
            'seo' => [
                'titulo'    => $categoria ? $categoria['nome'] . ' | Blog' : 'Blog: guias de compra e dicas de tecnologia',
                'descricao' => $categoria['descricao'] ?? 'Guias para escolher telemóvel, portátil, TV ou electrodomésticos em Angola, com preços em Kwanzas e dicas práticas.',
                'noindex'   => $pagina > 1,
                'jsonld'    => [Seo::breadcrumb($breadcrumb)],
            ],
        ]);
    }

    public function mostrar(string $slug): void
    {
        $blog = new Blog();
        $preVisualizar = !empty($_GET['pre']) && AdminAuth::admin();
        $post = $blog->porSlug($slug, $preVisualizar);
        if (!$post) {
            (new ErroController())->naoEncontrado();
            return;
        }
        if (!$preVisualizar) {
            $blog->registarVisualizacao((int) $post['id']);
        }

        [$html, $indice, $citados] = Blog::processar((string) $post['conteudo'],
            fn ($p) => $this->parcial('blog/_produto', ['p' => $p]));

        // "Produtos neste artigo": os escolhidos no painel; se não houver, os citados no texto
        $ids = array_filter(array_map('intval', explode(',', (string) $post['produtos_ids'])));
        if ($ids) {
            $porId = array_column((new Produto())->porIds($ids), null, 'id');
            $produtos = array_values(array_filter(array_map(fn ($id) => $porId[$id] ?? null, $ids)));
        } else {
            $produtos = $citados;
        }

        $breadcrumb = [['Início', '/'], ['Blog', '/blog'], [$post['categoria_nome'], '/blog/categoria/' . $post['categoria_slug']], [$post['titulo'], '/blog/' . $slug]];
        $imagem = $post['imagem'] ? URL_ORIGEM . asset('img/' . $post['imagem']) : null;

        $this->view('blog/mostrar', [
            'post'        => $post,
            'html'        => $html,
            'indice'      => $indice,
            'produtos'    => array_slice($produtos, 0, 4),
            'relacionados'=> $blog->relacionados($post),
            'minutos'     => Blog::minutosLeitura((string) $post['conteudo']),
            'breadcrumb'  => $breadcrumb,
            'preVisualizar' => $preVisualizar,
            'seo' => [
                'titulo'    => $post['meta_titulo'] ?: $post['titulo'],
                'descricao' => $post['meta_descricao'] ?: ($post['resumo'] ?: mb_substr(strip_tags((string) $post['conteudo']), 0, 155)),
                'imagem'    => $imagem ?? URL_BASE . '/assets/img/og-diotekno.png',
                'tipo'      => 'article',
                'noindex'   => $preVisualizar,
                'jsonld'    => [
                    array_filter([
                        '@context'      => 'https://schema.org',
                        '@type'         => 'BlogPosting',
                        'headline'      => $post['titulo'],
                        'description'   => $post['resumo'],
                        'image'         => $imagem ? [$imagem] : null,
                        'datePublished' => date('c', strtotime((string) $post['publicado_em'])),
                        'dateModified'  => date('c', strtotime((string) $post['actualizado_em'])),
                        'author'        => ['@type' => 'Organization', 'name' => NOME_SITE],
                        'publisher'     => ['@type' => 'Organization', 'name' => NOME_SITE, 'logo' => ['@type' => 'ImageObject', 'url' => URL_BASE . '/assets/img/logo.png']],
                        'mainEntityOfPage' => URL_BASE . '/blog/' . $slug,
                        'articleSection'=> $post['categoria_nome'],
                        'inLanguage'    => 'pt-AO',
                    ]),
                    Seo::breadcrumb($breadcrumb),
                ],
            ],
        ]);
    }

    /** RSS 2.0 — /blog/feed.xml */
    public function feed(): void
    {
        header('Content-Type: application/rss+xml; charset=utf-8');
        $x = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
        echo '<title>' . $x(NOME_SITE . ' — Blog') . '</title><link>' . $x(URL_BASE . '/blog') . '</link>';
        echo '<description>' . $x('Guias de compra e dicas de tecnologia') . '</description><language>pt-AO</language>';
        echo '<atom:link href="' . $x(URL_BASE . '/blog/feed.xml') . '" rel="self" type="application/rss+xml"/>';
        foreach ((new Blog())->paraFeed() as $p) {
            echo '<item><title>' . $x($p['titulo']) . '</title><link>' . $x(URL_BASE . '/blog/' . $p['slug']) . '</link>'
               . '<guid isPermaLink="true">' . $x(URL_BASE . '/blog/' . $p['slug']) . '</guid>'
               . '<pubDate>' . date(DATE_RSS, strtotime($p['publicado_em'])) . '</pubDate>'
               . '<category>' . $x($p['categoria_nome']) . '</category>'
               . '<description>' . $x($p['resumo']) . '</description></item>';
        }
        echo '</channel></rss>';
        exit;
    }
}
