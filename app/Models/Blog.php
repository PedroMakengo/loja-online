<?php
// =====================================================================
// app/Models/Blog.php — Artigos do blog
//
// Um artigo é público quando estado = 'publicado' e publicado_em já
// chegou (permite agendar). O conteúdo é guardado já limpo
// (HtmlSeguro) e é processado na leitura:
//   * cada <h2> recebe um id e entra no índice;
//   * [produto:slug] numa linha vira um cartão com preço e stock actuais.
// =====================================================================

namespace App\Models;

use Core\Model;

class Blog extends Model
{
    protected string $tabela = 'blog_posts';

    private const PUBLICO = "p.estado = 'publicado' AND p.publicado_em <= NOW()";
    private const BASE = "SELECT p.id, p.titulo, p.slug, p.resumo, p.imagem, p.publicado_em, p.actualizado_em, p.destaque,
                                 p.conteudo, c.nome AS categoria_nome, c.slug AS categoria_slug, a.nome AS autor
                            FROM blog_posts p JOIN blog_categorias c ON c.id = p.categoria_id LEFT JOIN admins a ON a.id = p.autor_id";

    public function publicados(?int $categoriaId, int $pagina, int $porPagina = 9, ?int $excluir = null): array
    {
        $w = self::PUBLICO; $par = [];
        if ($categoriaId) { $w .= ' AND p.categoria_id = ?'; $par[] = $categoriaId; }
        if ($excluir)     { $w .= ' AND p.id <> ?'; $par[] = $excluir; }
        $total = (int) $this->valor("SELECT COUNT(*) FROM blog_posts p WHERE $w", $par);
        $offset = (max(1, $pagina) - 1) * $porPagina;
        return [
            'itens'   => $this->todos(self::BASE . " WHERE $w ORDER BY p.publicado_em DESC LIMIT $porPagina OFFSET $offset", $par),
            'total'   => $total,
            'pagina'  => max(1, $pagina),
            'paginas' => (int) ceil($total / $porPagina),
        ];
    }

    public function destaque(): ?array
    {
        return $this->um(self::BASE . " WHERE " . self::PUBLICO . " ORDER BY p.destaque DESC, p.publicado_em DESC LIMIT 1");
    }

    /** $preVisualizar permite ver rascunhos (só para administradores). */
    public function porSlug(string $slug, bool $preVisualizar = false): ?array
    {
        $w = $preVisualizar ? '1=1' : self::PUBLICO;
        return $this->um(
            "SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug, a.nome AS autor
               FROM blog_posts p JOIN blog_categorias c ON c.id = p.categoria_id LEFT JOIN admins a ON a.id = p.autor_id
              WHERE p.slug = ? AND $w LIMIT 1",
            [$slug]
        );
    }

    public function relacionados(array $post, int $limite = 3): array
    {
        return $this->todos(
            self::BASE . " WHERE " . self::PUBLICO . " AND p.id <> ? ORDER BY (p.categoria_id = ?) DESC, p.publicado_em DESC LIMIT $limite",
            [$post['id'], $post['categoria_id']]
        );
    }

    public function categorias(): array
    {
        return $this->todos(
            "SELECT c.*, COUNT(p.id) AS total FROM blog_categorias c
          LEFT JOIN blog_posts p ON p.categoria_id = c.id AND " . self::PUBLICO . "
              GROUP BY c.id ORDER BY c.ordem, c.nome"
        );
    }

    public function categoriaPorSlug(string $slug): ?array
    {
        return $this->um("SELECT * FROM blog_categorias WHERE slug = ?", [$slug]);
    }

    public function registarVisualizacao(int $id): void
    {
        $this->executar("UPDATE blog_posts SET visualizacoes = visualizacoes + 1, actualizado_em = actualizado_em WHERE id = ?", [$id]);
    }

    public function paraSitemap(): array
    {
        return $this->todos("SELECT slug, GREATEST(actualizado_em, publicado_em) AS data FROM blog_posts p WHERE " . self::PUBLICO . " ORDER BY publicado_em DESC");
    }

    public function paraFeed(int $limite = 20): array
    {
        return $this->todos(self::BASE . " WHERE " . self::PUBLICO . " ORDER BY p.publicado_em DESC LIMIT $limite");
    }

    // ----- Leitura do conteúdo ------------------------------------------------

    public static function minutosLeitura(string $html): int
    {
        $palavras = str_word_count(strip_tags($html), 0, 'ÁÀÂÃÉÊÍÓÔÕÚÇáàâãéêíóôõúç');
        return max(1, (int) round($palavras / 200));
    }

    /**
     * Processa o conteúdo guardado. Devolve [html, indice, produtosCitados].
     * $renderProduto: callable(array $produto): string — HTML do cartão.
     */
    public static function processar(string $html, callable $renderProduto): array
    {
        // Índice: ids estáveis a partir do texto dos <h2>
        $indice = [];
        $usados = [];
        $html = preg_replace_callback('#<h2>(.*?)</h2>#is', function ($m) use (&$indice, &$usados) {
            $texto = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
            $id = slugify($texto) ?: 'seccao';
            $base = $id; $n = 2;
            while (isset($usados[$id])) { $id = $base . '-' . $n++; }
            $usados[$id] = true;
            $indice[] = ['id' => $id, 'texto' => $texto];
            return '<h2 id="' . $id . '">' . $m[1] . '</h2>';
        }, $html);

        // [produto:slug] -> cartão. Primeiro os que estão sozinhos num parágrafo (bloco), depois os soltos.
        preg_match_all('#\[produto:([a-z0-9-]{2,220})\]#', $html, $m);
        $slugs = array_unique($m[1]);
        $produtos = [];
        if ($slugs) {
            $mod = new Produto();
            foreach ($slugs as $s) {
                if ($p = $mod->porSlug($s)) {
                    $produtos[$s] = $p;
                }
            }
        }
        $substituir = fn ($s) => isset($produtos[$s]) ? $renderProduto($produtos[$s]) : '';
        $html = preg_replace_callback('#<p>\s*\[produto:([a-z0-9-]{2,220})\]\s*</p>#', fn ($m) => $substituir($m[1]), $html);
        $html = preg_replace_callback('#\[produto:([a-z0-9-]{2,220})\]#', fn ($m) => $substituir($m[1]), $html);

        return [$html, $indice, array_values($produtos)];
    }
}
