<?php
// =====================================================================
// app/Models/Categoria.php — Árvore de categorias (até 3 níveis):
//   Categoria (ex.: Informática)
//     └ Subcategoria (ex.: Computadores Portáteis)
//         └ Sub-subcategoria (ex.: Macbook)
// Guardada em categorias.parent_id. Na loja só aparecem os ramos em que
// a categoria e todos os antepassados estão activos.
// Guia: .claude/skills/categorias-subcategorias/SKILL.md
// =====================================================================

namespace App\Models;

use Core\Model;

class Categoria extends Model
{
    protected string $tabela = 'categorias';

    /** Profundidade máxima: 1 = categoria, 2 = subcategoria, 3 = sub-subcategoria. */
    public const NIVEIS = 3;

    /** Nome de cada nível (índice = profundidade). */
    public const NOMES_NIVEL = [1 => 'Categoria', 2 => 'Subcategoria', 3 => 'Sub-subcategoria'];

    /** @var array<int, array>|null  todas as categorias activas e visíveis, por id (cache por pedido) */
    private static ?array $visiveis = null;

    /**
     * Árvore visível na loja: categorias de topo, cada uma com 'filhos' (recursivo),
     * 'nivel' (1..3), 'produtos_directos' e 'total_produtos' (inclui descendentes).
     */
    public function arvore(): array
    {
        $porId = $this->visiveis();
        return array_values(array_filter($porId, fn ($c) => $c['parent_id'] === null));
    }

    /** Todas as categorias visíveis em lista plana, na ordem da árvore (pai antes dos filhos). */
    public function todasActivas(): array
    {
        $plano = [];
        $percorrer = function (array $nos) use (&$percorrer, &$plano) {
            foreach ($nos as $n) {
                $plano[] = $n;
                $percorrer($n['filhos']);
            }
        };
        $percorrer($this->arvore());
        return $plano;
    }

    public function destaque(): array
    {
        return array_values(array_filter($this->todasActivas(), fn ($c) => (int) $c['destaque'] === 1));
    }

    /** Só devolve categorias visíveis (activas e com todos os antepassados activos). */
    public function porSlug(string $slug): ?array
    {
        foreach ($this->visiveis() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }
        return null;
    }

    /** Ids da categoria e de todas as subcategorias visíveis (para listar os produtos do ramo). */
    public function idsDoRamo(int $id): array
    {
        $c = $this->visiveis()[$id] ?? null;
        if (!$c) {
            return [$id];
        }
        $ids = [$id];
        foreach ($c['filhos'] as $f) {
            array_push($ids, ...$this->idsDoRamo((int) $f['id']));
        }
        return $ids;
    }

    /** Antepassados + a própria, da raiz para baixo: [['nome','slug'], ...]. */
    public function caminho(int $id): array
    {
        $todas = $this->visiveis();
        $caminho = [];
        while ($id && isset($todas[$id]) && count($caminho) < self::NIVEIS + 1) {
            array_unshift($caminho, $todas[$id]);
            $id = (int) $todas[$id]['parent_id'];
        }
        return $caminho;
    }

    /** Breadcrumb (formato das views) para uma categoria: Início › Produtos › Pai › Filha. */
    public function breadcrumb(int $id, bool $comProdutos = true): array
    {
        $b = [['Início', '/']];
        if ($comProdutos) {
            $b[] = ['Produtos', '/produtos'];
        }
        foreach ($this->caminho($id) as $c) {
            $b[] = [$c['nome'], '/categoria/' . $c['slug']];
        }
        return $b;
    }

    // ----- Painel (inclui inactivas) ---------------------------------------------

    /**
     * Árvore completa para o painel, em lista plana ordenada, com 'nivel',
     * 'tem_filhos', 'produtos_directos' e 'total_produtos'.
     */
    public function arvoreAdmin(): array
    {
        $linhas = $this->todos(
            "SELECT c.*, (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id) AS produtos_directos
               FROM categorias c ORDER BY c.ordem, c.nome"
        );
        return self::achatar(self::montar($linhas, false));
    }

    /** Ids da categoria e de todos os descendentes (incluindo inactivos). */
    public function descendentes(int $id): array
    {
        $ids = [$id];
        $fila = [$id];
        while ($fila) {
            $marcadores = implode(',', array_fill(0, count($fila), '?'));
            $filhos = array_map('intval', array_column(
                $this->todos("SELECT id FROM categorias WHERE parent_id IN ($marcadores)", $fila), 'id'));
            $filhos = array_diff($filhos, $ids); // protecção contra ciclos em dados antigos
            $ids = array_merge($ids, $filhos);
            $fila = $filhos;
        }
        return $ids;
    }

    /** Nível de uma categoria (1 = topo). */
    public function nivel(int $id): int
    {
        $nivel = 0;
        $visto = [];
        while ($id && !isset($visto[$id]) && $nivel <= self::NIVEIS + 1) {
            $visto[$id] = true;
            $nivel++;
            $id = (int) $this->valor("SELECT COALESCE(parent_id, 0) FROM categorias WHERE id = ?", [$id]);
        }
        return $nivel;
    }

    /** Quantos níveis tem o ramo abaixo (e incluindo) a categoria: folha = 1. */
    public function alturaDoRamo(int $id): int
    {
        $filhos = array_map('intval', array_column($this->todos("SELECT id FROM categorias WHERE parent_id = ?", [$id]), 'id'));
        $max = 0;
        foreach ($filhos as $f) {
            $max = max($max, $this->alturaDoRamo($f));
        }
        return 1 + $max;
    }

    /** Opções para <select> com indentação ("— Subcategoria"), na ordem da árvore. */
    public static function opcoesIndentadas(array $plano): array
    {
        $ops = [];
        foreach ($plano as $c) {
            $ops[(int) $c['id']] = str_repeat('— ', $c['nivel'] - 1) . $c['nome'] . ((int) $c['activo'] ? '' : ' (inactiva)');
        }
        return $ops;
    }

    // ---------------------------------------------------------------------

    private function visiveis(): array
    {
        if (self::$visiveis === null) {
            $linhas = $this->todos(
                "SELECT c.*, COUNT(p.id) AS produtos_directos
                   FROM categorias c
                   LEFT JOIN produtos p ON p.categoria_id = c.id AND p.estado = 'activo'
                  WHERE c.activo = 1
                  GROUP BY c.id
                  ORDER BY c.ordem, c.nome"
            );
            $porId = [];
            foreach (self::achatar(self::montar($linhas, true)) as $c) {
                $porId[(int) $c['id']] = $c;
            }
            self::$visiveis = $porId;
        }
        return self::$visiveis;
    }

    /**
     * Monta a árvore a partir das linhas (já ordenadas). Com $soVisiveis, um filho cujo pai
     * não está nas linhas (inactivo) fica de fora com todo o seu ramo.
     */
    private static function montar(array $linhas, bool $soVisiveis): array
    {
        $porPai = [];
        $ids = [];
        foreach ($linhas as $l) {
            $l['id'] = (int) $l['id'];
            $l['parent_id'] = $l['parent_id'] !== null ? (int) $l['parent_id'] : null;
            $ids[$l['id']] = true;
            $porPai[$l['parent_id'] ?? 0][] = $l;
        }
        if (!$soVisiveis) {
            // Painel: órfãos (pai apagado) aparecem no topo para poderem ser corrigidos
            foreach ($porPai as $pai => $grupo) {
                if ($pai !== 0 && !isset($ids[$pai])) {
                    array_push($porPai[0], ...$grupo);
                    unset($porPai[$pai]);
                }
            }
        }
        $construir = function (int $pai, int $nivel, array $visitados) use (&$construir, $porPai): array {
            $nos = [];
            foreach ($porPai[$pai] ?? [] as $c) {
                if (isset($visitados[$c['id']])) {
                    continue; // ciclo
                }
                $c['nivel'] = $nivel;
                $c['filhos'] = $construir($c['id'], $nivel + 1, $visitados + [$c['id'] => true]);
                $c['produtos_directos'] = (int) $c['produtos_directos'];
                $c['total_produtos'] = $c['produtos_directos'] + array_sum(array_column($c['filhos'], 'total_produtos'));
                $c['tem_filhos'] = (bool) $c['filhos'];
                $nos[] = $c;
            }
            return $nos;
        };
        return $construir(0, 1, []);
    }

    /** Lista plana (pai antes dos filhos). Os nós mantêm 'filhos' para a navegação. */
    private static function achatar(array $nos): array
    {
        $plano = [];
        foreach ($nos as $n) {
            $plano[] = $n;
            array_push($plano, ...self::achatar($n['filhos']));
        }
        return $plano;
    }
}
