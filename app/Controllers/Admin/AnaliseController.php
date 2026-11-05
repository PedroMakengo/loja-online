<?php
// =====================================================================
// Análise de Dados — painel analítico (estilo Power BI) com gráficos.
// Cada bloco devolve dados prontos para assets/js/graficos.js.
// Compara sempre com o período anterior de igual duração.
// =====================================================================

namespace App\Controllers\Admin;

use App\Models\Pedido;
use App\Servicos\Pagamentos;

class AnaliseController extends RelatoriosController
{
    protected ?string $area = 'relatorios';

    private const FILTRO_PAGO = "p.pago_em BETWEEN ? AND ? AND p.estado NOT IN ('cancelado','reembolsado')";
    private const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    private const DIAS_SEMANA = [2 => 'Seg', 3 => 'Ter', 4 => 'Qua', 5 => 'Qui', 6 => 'Sex', 7 => 'Sáb', 1 => 'Dom'];

    public function index(): void
    {
        [$de, $ate] = $this->periodo();
        $dias = (int) ((strtotime($ate) - strtotime($de)) / 86400) + 1;
        $antAte = date('Y-m-d', strtotime($de . ' -1 day'));
        $antDe  = date('Y-m-d', strtotime($antAte . ' -' . ($dias - 1) . ' days'));
        $mensal = $dias > 92; // períodos longos agrupados por mês

        $actual   = $this->kpis($de, $ate);
        $anterior = $this->kpis($antDe, $antAte);
        $serie    = $this->serie($de, $ate, $mensal);
        $serieAnt = $this->serie($antDe, $antAte, $mensal);

        // Série temporal: período actual + anterior alinhados pela posição (dia 1 com dia 1)
        $tempo = [];
        $chavesAnt = array_values($serieAnt);
        $i = 0;
        foreach ($serie as $chave => $v) {
            $tempo[] = [
                'rotulo'           => $this->rotuloData($chave, $mensal),
                'receita'          => $v['receita'],
                'receita_anterior' => $chavesAnt[$i]['receita'] ?? 0,
                'pedidos'          => $v['pedidos'],
                'pedidos_anterior' => $chavesAnt[$i]['pedidos'] ?? 0,
            ];
            $i++;
        }

        $this->pagina('analise', [
            'de' => $de, 'ate' => $ate, 'antDe' => $antDe, 'antAte' => $antAte, 'mensal' => $mensal,
            'actual'     => $actual,
            'anterior'   => $anterior,
            'tempo'      => $tempo,
            'metodos'    => $this->metodos($de, $ate),
            'categorias' => $this->categorias($de, $ate),
            'semana'     => $this->diaSemana($de, $ate),
            'mapaHoras'  => $this->mapaHoras($de, $ate),
            'provincias' => $this->provincias($de, $ate),
            'estados'    => $this->estados($de, $ate),
            'clientes'   => $this->novosVsRecorrentes($de, $ate, $mensal),
            'produtos'   => $this->topProdutos($de, $ate),
        ], 'Análise de Dados');
    }

    // ------------------------------------------------------------------

    private function consulta(string $sql, array $params): array
    {
        $st = $this->db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    private function intervalo(string $de, string $ate): array
    {
        return [$de . ' 00:00:00', $ate . ' 23:59:59'];
    }

    private function kpis(string $de, string $ate): array
    {
        $p = $this->intervalo($de, $ate);
        $f = self::FILTRO_PAGO;
        $base = $this->consulta("SELECT COUNT(*) pedidos, COALESCE(SUM(total),0) receita, COALESCE(AVG(total),0) ticket,
                                        COUNT(DISTINCT cliente_id) clientes
                                   FROM pedidos p WHERE $f", $p)[0];
        $unid = $this->consulta("SELECT COALESCE(SUM(pi.quantidade),0) u FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id WHERE $f", $p)[0]['u'];
        // Conversão: dos pedidos criados no período, quantos foram pagos
        $conv = $this->consulta("SELECT COUNT(*) criados, SUM(pago_em IS NOT NULL) pagos FROM pedidos WHERE criado_em BETWEEN ? AND ?", $p)[0];

        return [
            'receita'   => (float) $base['receita'],
            'pedidos'   => (int) $base['pedidos'],
            'ticket'    => (float) $base['ticket'],
            'clientes'  => (int) $base['clientes'],
            'unidades'  => (int) $unid,
            'conversao' => $conv['criados'] ? round($conv['pagos'] / $conv['criados'] * 100, 1) : null,
        ];
    }

    /** Receita e pedidos por dia (ou mês), com zeros nos intervalos sem vendas. */
    private function serie(string $de, string $ate, bool $mensal): array
    {
        $fmt = $mensal ? '%Y-%m-01' : '%Y-%m-%d';
        $linhas = $this->consulta("SELECT DATE_FORMAT(p.pago_em, '$fmt') k, COUNT(*) pedidos, SUM(p.total) receita
                                     FROM pedidos p WHERE " . self::FILTRO_PAGO . " GROUP BY k", $this->intervalo($de, $ate));
        $porChave = array_column($linhas, null, 'k');

        $serie = [];
        $t   = strtotime($mensal ? date('Y-m-01', strtotime($de)) : $de);
        $fim = strtotime($ate);
        while ($t <= $fim) {
            $k = date('Y-m-d', $t);
            $serie[$k] = ['receita' => (float) ($porChave[$k]['receita'] ?? 0), 'pedidos' => (int) ($porChave[$k]['pedidos'] ?? 0)];
            $t = strtotime($mensal ? '+1 month' : '+1 day', $t);
        }
        return $serie;
    }

    private function rotuloData(string $k, bool $mensal): string
    {
        $t = strtotime($k);
        return $mensal
            ? self::MESES[(int) date('n', $t) - 1] . ' ' . date('Y', $t)
            : date('d', $t) . ' ' . self::MESES[(int) date('n', $t) - 1];
    }

    private function metodos(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT p.metodo_pagamento m, COUNT(*) pedidos, SUM(p.total) receita FROM pedidos p
                               WHERE " . self::FILTRO_PAGO . " GROUP BY m ORDER BY receita DESC", $this->intervalo($de, $ate));
        return $this->agruparOutros(array_map(fn ($r) => ['rotulo' => Pagamentos::nome($r['m']), 'valor' => (float) $r['receita'], 'pedidos' => (int) $r['pedidos']], $l), 5);
    }

    private function categorias(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT COALESCE(c.nome,'(produto apagado)') nome, SUM(pi.quantidade) unidades, SUM(pi.total) receita
                                FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id
                           LEFT JOIN produtos pr ON pr.id = pi.produto_id LEFT JOIN categorias c ON c.id = pr.categoria_id
                               WHERE " . self::FILTRO_PAGO . " GROUP BY c.id, c.nome ORDER BY receita DESC", $this->intervalo($de, $ate));
        return $this->agruparOutros(array_map(fn ($r) => ['rotulo' => $r['nome'], 'valor' => (float) $r['receita'], 'unidades' => (int) $r['unidades']], $l), 7);
    }

    /** Mantém os N maiores e junta o resto em "Outros" (cores nunca passam de N+1). */
    private function agruparOutros(array $linhas, int $n): array
    {
        if (count($linhas) <= $n) {
            return $linhas;
        }
        $resto = array_slice($linhas, $n - 1);
        $outros = ['rotulo' => 'Outros', 'valor' => array_sum(array_column($resto, 'valor'))];
        foreach (['pedidos', 'unidades'] as $c) {
            if (isset($resto[0][$c])) $outros[$c] = array_sum(array_column($resto, $c));
        }
        return [...array_slice($linhas, 0, $n - 1), $outros];
    }

    private function diaSemana(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT DAYOFWEEK(p.pago_em) d, COUNT(*) pedidos, SUM(p.total) receita FROM pedidos p
                               WHERE " . self::FILTRO_PAGO . " GROUP BY d", $this->intervalo($de, $ate));
        $por = array_column($l, null, 'd');
        $r = [];
        foreach (self::DIAS_SEMANA as $d => $nome) {
            $r[] = ['rotulo' => $nome, 'receita' => (float) ($por[$d]['receita'] ?? 0), 'pedidos' => (int) ($por[$d]['pedidos'] ?? 0)];
        }
        return $r;
    }

    /** Mapa de calor: pedidos pagos por dia da semana × hora. */
    private function mapaHoras(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT DAYOFWEEK(p.pago_em) d, HOUR(p.pago_em) h, COUNT(*) n FROM pedidos p
                               WHERE " . self::FILTRO_PAGO . " GROUP BY d, h", $this->intervalo($de, $ate));
        $m = [];
        foreach ($l as $r) {
            $m[$r['d']][$r['h']] = (int) $r['n'];
        }
        $linhas = [];
        foreach (self::DIAS_SEMANA as $d => $nome) {
            $valores = [];
            for ($h = 0; $h < 24; $h++) $valores[] = $m[$d][$h] ?? 0;
            $linhas[] = ['rotulo' => $nome, 'valores' => $valores];
        }
        return ['colunas' => array_map(fn ($h) => sprintf('%02dh', $h), range(0, 23)), 'linhas' => $linhas];
    }

    private function provincias(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT COALESCE(p.entrega_provincia,'—') nome, COUNT(*) pedidos, SUM(p.total) receita FROM pedidos p
                               WHERE " . self::FILTRO_PAGO . " GROUP BY nome ORDER BY receita DESC LIMIT 8", $this->intervalo($de, $ate));
        return array_map(fn ($r) => ['rotulo' => $r['nome'], 'valor' => (float) $r['receita'], 'pedidos' => (int) $r['pedidos']], $l);
    }

    /** Funil: pedidos criados no período e até onde chegaram. */
    private function estados(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT estado, COUNT(*) n FROM pedidos WHERE criado_em BETWEEN ? AND ? GROUP BY estado", $this->intervalo($de, $ate));
        $n = array_column($l, 'n', 'estado');
        $g = fn (array $e) => array_sum(array_map(fn ($x) => (int) ($n[$x] ?? 0), $e));
        $total = array_sum(array_map('intval', $n));
        return [
            ['rotulo' => 'Pedidos criados', 'valor' => $total],
            ['rotulo' => 'Pagos',           'valor' => $g(['pago', 'em_preparacao', 'enviado', 'entregue'])],
            ['rotulo' => 'Enviados',        'valor' => $g(['enviado', 'entregue'])],
            ['rotulo' => 'Entregues',       'valor' => $g(['entregue'])],
            ['rotulo' => Pedido::ESTADOS['cancelado'][0], 'valor' => $g(['cancelado', 'reembolsado']), 'perda' => true],
        ];
    }

    /** Receita de clientes novos (1.ª compra paga) vs recorrentes, por dia/mês. */
    private function novosVsRecorrentes(string $de, string $ate, bool $mensal): array
    {
        $fmt = $mensal ? '%Y-%m-01' : '%Y-%m-%d';
        $l = $this->consulta("SELECT DATE_FORMAT(p.pago_em, '$fmt') k,
                                     SUM(CASE WHEN EXISTS (SELECT 1 FROM pedidos a WHERE a.cliente_id = p.cliente_id AND a.pago_em < p.pago_em
                                                             AND a.estado NOT IN ('cancelado','reembolsado')) THEN 0 ELSE p.total END) novos,
                                     SUM(CASE WHEN EXISTS (SELECT 1 FROM pedidos a WHERE a.cliente_id = p.cliente_id AND a.pago_em < p.pago_em
                                                             AND a.estado NOT IN ('cancelado','reembolsado')) THEN p.total ELSE 0 END) recorrentes
                                FROM pedidos p WHERE " . self::FILTRO_PAGO . " GROUP BY k", $this->intervalo($de, $ate));
        $por = array_column($l, null, 'k');
        $r = [];
        foreach (array_keys($this->serie($de, $ate, $mensal)) as $k) {
            $r[] = ['rotulo' => $this->rotuloData($k, $mensal), 'novos' => (float) ($por[$k]['novos'] ?? 0), 'recorrentes' => (float) ($por[$k]['recorrentes'] ?? 0)];
        }
        return $r;
    }

    private function topProdutos(string $de, string $ate): array
    {
        $l = $this->consulta("SELECT pi.nome, SUM(pi.quantidade) unidades, SUM(pi.total) receita
                                FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id
                               WHERE " . self::FILTRO_PAGO . " GROUP BY pi.sku, pi.nome ORDER BY receita DESC LIMIT 8", $this->intervalo($de, $ate));
        return array_map(fn ($r) => ['rotulo' => $r['nome'], 'valor' => (float) $r['receita'], 'unidades' => (int) $r['unidades']], $l);
    }
}
