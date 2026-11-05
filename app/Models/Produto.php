<?php
// =====================================================================
// app/Models/Produto.php — Catálogo: listagem com filtros, facetas,
// pesquisa, destaques da home e página de produto
// =====================================================================

namespace App\Models;

use Core\Model;

class Produto extends Model
{
    protected string $tabela = 'produtos';

    private const BASE = "SELECT p.id, p.nome, p.slug, p.sku, p.resumo, p.preco, p.preco_anterior,
                                 p.stock, p.imagem, p.destaque, p.novidade, p.oferta_ate, p.vendas,
                                 p.avaliacao_media, p.total_avaliacoes, p.categoria_id, p.marca_id,
                                 c.nome AS categoria_nome, c.slug AS categoria_slug,
                                 m.nome AS marca_nome, m.slug AS marca_slug
                            FROM produtos p
                            JOIN categorias c ON c.id = p.categoria_id
                       LEFT JOIN marcas m ON m.id = p.marca_id";

    public const ORDENS = [
        'relevancia'  => 'Mais relevantes',
        'mais-vendidos' => 'Mais vendidos',
        'preco-asc'   => 'Preço: menor para maior',
        'preco-desc'  => 'Preço: maior para menor',
        'novidades'   => 'Novidades',
        'desconto'    => 'Maior desconto',
    ];

    /**
     * Listagem com filtros. Devolve ['itens' => [...], 'total' => n].
     * $f: categoria_id, marca_id, marcas[] (slugs), min, max, promo, novidade, stock, q, ordem, pagina
     */
    public function listar(array $f, int $porPagina = PRODUTOS_POR_PAGINA): array
    {
        [$where, $params] = $this->condicoes($f);

        $ordem = match ($f['ordem'] ?? 'relevancia') {
            'preco-asc'     => 'p.preco ASC',
            'preco-desc'    => 'p.preco DESC',
            'novidades'     => 'p.novidade DESC, p.criado_em DESC',
            'mais-vendidos' => 'p.vendas DESC',
            'desconto'      => '(COALESCE(p.preco_anterior, p.preco) - p.preco) / COALESCE(p.preco_anterior, p.preco) DESC',
            default         => '(p.stock > 0) DESC, p.destaque DESC, p.vendas DESC',
        };

        $pagina = max(1, (int) ($f['pagina'] ?? 1));
        $offset = ($pagina - 1) * $porPagina;

        $total = (int) $this->valor(
            "SELECT COUNT(*) FROM produtos p
               JOIN categorias c ON c.id = p.categoria_id
          LEFT JOIN marcas m ON m.id = p.marca_id
              WHERE $where",
            $params
        );

        $itens = $this->todos(self::BASE . " WHERE $where ORDER BY $ordem LIMIT $porPagina OFFSET $offset", $params);

        return ['itens' => $itens, 'total' => $total, 'pagina' => $pagina, 'paginas' => (int) ceil($total / $porPagina)];
    }

    /** Marcas presentes no conjunto actual (sem o filtro de marca), com contagem. */
    public function facetaMarcas(array $f): array
    {
        unset($f['marcas']);
        [$where, $params] = $this->condicoes($f);
        return $this->todos(
            "SELECT m.nome, m.slug, COUNT(*) AS total
               FROM produtos p
               JOIN categorias c ON c.id = p.categoria_id
               JOIN marcas m ON m.id = p.marca_id
              WHERE $where
              GROUP BY m.id ORDER BY total DESC, m.nome",
            $params
        );
    }

    /** Intervalo de preço do conjunto actual (sem o filtro de preço). */
    public function facetaPreco(array $f): array
    {
        unset($f['min'], $f['max']);
        [$where, $params] = $this->condicoes($f);
        $r = $this->um(
            "SELECT MIN(p.preco) AS min, MAX(p.preco) AS max FROM produtos p
               JOIN categorias c ON c.id = p.categoria_id
          LEFT JOIN marcas m ON m.id = p.marca_id WHERE $where",
            $params
        );
        return ['min' => (int) ($r['min'] ?? 0), 'max' => (int) ($r['max'] ?? 0)];
    }

    private function condicoes(array $f): array
    {
        $w = ["p.estado = 'activo'", 'c.activo = 1'];
        $p = [];

        if (!empty($f['categoria_id'])) { $w[] = 'p.categoria_id = ?'; $p[] = (int) $f['categoria_id']; }
        if (!empty($f['marca_id']))     { $w[] = 'p.marca_id = ?';     $p[] = (int) $f['marca_id']; }

        if (!empty($f['marcas']) && is_array($f['marcas'])) {
            $w[] = 'm.slug IN (' . implode(',', array_fill(0, count($f['marcas']), '?')) . ')';
            array_push($p, ...array_values($f['marcas']));
        }
        if (!empty($f['min'])) { $w[] = 'p.preco >= ?'; $p[] = (float) $f['min']; }
        if (!empty($f['max'])) { $w[] = 'p.preco <= ?'; $p[] = (float) $f['max']; }
        if (!empty($f['promo']))    { $w[] = 'p.preco_anterior > p.preco'; }
        if (!empty($f['novidade'])) { $w[] = 'p.novidade = 1'; }
        if (!empty($f['stock']))    { $w[] = 'p.stock > 0'; }

        // Pesquisa: cada palavra tem de aparecer no nome, SKU, marca ou categoria
        if (!empty($f['q'])) {
            foreach ($this->termos($f['q']) as $termo) {
                $w[] = '(p.nome LIKE ? OR p.sku LIKE ? OR m.nome LIKE ? OR c.nome LIKE ?)';
                $like = '%' . $termo . '%';
                array_push($p, $like, $like, $like, $like);
            }
        }

        return [implode(' AND ', $w), $p];
    }

    private function termos(string $q): array
    {
        $q = mb_strtolower(trim($q));
        $termos = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $termos = array_map(fn ($t) => addcslashes($t, '%_'), $termos);
        return array_slice($termos, 0, 6);
    }

    /** Autocomplete da pesquisa (máx. 6 produtos). */
    public function sugestoes(string $q, int $limite = 6): array
    {
        if (mb_strlen(trim($q)) < 2) {
            return [];
        }
        $r = $this->listar(['q' => $q, 'ordem' => 'mais-vendidos'], $limite);
        return $r['itens'];
    }

    // ----- Secções da Home -------------------------------------------------

    public function maisVendidos(int $limite = 10): array
    {
        return $this->todos(self::BASE . " WHERE p.estado = 'activo' AND p.stock > 0 ORDER BY p.vendas DESC LIMIT $limite");
    }

    public function ofertasDoDia(int $limite = 8): array
    {
        return $this->todos(self::BASE . " WHERE p.estado = 'activo' AND p.oferta_ate > NOW()
                                             AND p.preco_anterior > p.preco ORDER BY p.oferta_ate, p.vendas DESC LIMIT $limite");
    }

    public function novidades(int $limite = 10): array
    {
        return $this->todos(self::BASE . " WHERE p.estado = 'activo' AND p.novidade = 1 ORDER BY p.criado_em DESC, p.id DESC LIMIT $limite");
    }

    public function porCategoria(int $categoriaId, int $limite = 10): array
    {
        return $this->todos(self::BASE . " WHERE p.estado = 'activo' AND p.categoria_id = ? ORDER BY p.vendas DESC LIMIT $limite", [$categoriaId]);
    }

    // ----- Página de produto -----------------------------------------------

    public function porSlug(string $slug): ?array
    {
        return $this->um(
            "SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug,
                    m.nome AS marca_nome, m.slug AS marca_slug
               FROM produtos p
               JOIN categorias c ON c.id = p.categoria_id
          LEFT JOIN marcas m ON m.id = p.marca_id
              WHERE p.slug = ? AND p.estado = 'activo' LIMIT 1",
            [$slug]
        );
    }

    public function porIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $ids = array_map('intval', $ids);
        return $this->todos(self::BASE . ' WHERE p.id IN (' . implode(',', $ids) . ") AND p.estado = 'activo'");
    }

    public function especificacoes(int $id): array
    {
        $linhas = $this->todos("SELECT grupo, nome, valor FROM produto_especificacoes WHERE produto_id = ? ORDER BY ordem, id", [$id]);
        $grupos = [];
        foreach ($linhas as $l) {
            $grupos[$l['grupo']][] = $l;
        }
        return $grupos;
    }

    public function imagens(int $id): array
    {
        return $this->todos("SELECT ficheiro, alt FROM produto_imagens WHERE produto_id = ? ORDER BY ordem, id", [$id]);
    }

    public function relacionados(array $produto, int $limite = 8): array
    {
        return $this->todos(
            self::BASE . " WHERE p.estado = 'activo' AND p.categoria_id = ? AND p.id <> ?
                            ORDER BY (p.marca_id = ?) DESC, ABS(p.preco - ?) LIMIT $limite",
            [$produto['categoria_id'], $produto['id'], $produto['marca_id'] ?? 0, $produto['preco']]
        );
    }

    public function registarVisualizacao(int $id): void
    {
        $this->executar("UPDATE produtos SET visualizacoes = visualizacoes + 1 WHERE id = ?", [$id]);
    }

    /** Para o sitemap. */
    public function paraSitemap(): array
    {
        return $this->todos("SELECT slug, actualizado_em FROM produtos WHERE estado = 'activo' ORDER BY id");
    }
}
