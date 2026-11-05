<?php
// Relatórios de vendas por período, com exportação CSV (Excel em PT).

namespace App\Controllers\Admin;

use App\Servicos\Pagamentos;

class RelatoriosController extends Base
{
    protected ?string $area = 'relatorios';

    protected function periodo(): array
    {
        $de  = $_GET['de']  ?? date('Y-m-01');
        $ate = $_GET['ate'] ?? date('Y-m-d');
        $de  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $de) ? $de : date('Y-m-01');
        $ate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate) ? $ate : date('Y-m-d');
        if ($ate < $de) { [$de, $ate] = [$ate, $de]; }
        return [$de, $ate];
    }

    private function dados(string $de, string $ate): array
    {
        $db = $this->db();
        $p = [$de . ' 00:00:00', $ate . ' 23:59:59'];
        $filtro = "p.pago_em BETWEEN ? AND ? AND p.estado NOT IN ('cancelado','reembolsado')";
        $q = function (string $sql) use ($db, $p) { $st = $db->prepare($sql); $st->execute($p); return $st->fetchAll(); };

        $totais = $q("SELECT COUNT(*) pedidos, COALESCE(SUM(total),0) receita, COALESCE(SUM(desconto),0) descontos,
                             COALESCE(SUM(portes),0) portes, COALESCE(SUM(iva),0) iva, COALESCE(AVG(total),0) ticket
                        FROM pedidos p WHERE $filtro")[0];
        $custo = $q("SELECT COALESCE(SUM(pi.quantidade * pr.custo),0) custo, COALESCE(SUM(CASE WHEN pr.custo IS NULL THEN pi.total END),0) sem_custo
                       FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id LEFT JOIN produtos pr ON pr.id = pi.produto_id WHERE $filtro")[0];

        return [
            'totais'     => $totais + $custo,
            'porDia'     => $q("SELECT DATE(p.pago_em) dia, COUNT(*) pedidos, SUM(p.total) receita FROM pedidos p WHERE $filtro GROUP BY DATE(p.pago_em) ORDER BY dia"),
            'porMetodo'  => $q("SELECT p.metodo_pagamento metodo, COUNT(*) pedidos, SUM(p.total) receita FROM pedidos p WHERE $filtro GROUP BY p.metodo_pagamento ORDER BY receita DESC"),
            'porCategoria' => $q("SELECT COALESCE(c.nome,'(produto apagado)') categoria, SUM(pi.quantidade) unidades, SUM(pi.total) receita
                                    FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id LEFT JOIN produtos pr ON pr.id = pi.produto_id LEFT JOIN categorias c ON c.id = pr.categoria_id
                                   WHERE $filtro GROUP BY c.id ORDER BY receita DESC"),
            'porProvincia' => $q("SELECT p.entrega_provincia provincia, COUNT(*) pedidos, SUM(p.total) receita FROM pedidos p WHERE $filtro GROUP BY p.entrega_provincia ORDER BY receita DESC"),
            'topProdutos'  => $q("SELECT pi.nome, pi.sku, SUM(pi.quantidade) unidades, SUM(pi.total) receita
                                    FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id WHERE $filtro GROUP BY pi.sku, pi.nome ORDER BY receita DESC LIMIT 15"),
        ];
    }

    public function index(): void
    {
        [$de, $ate] = $this->periodo();
        $this->pagina('relatorios', ['de' => $de, 'ate' => $ate] + $this->dados($de, $ate), 'Relatórios');
    }

    /** CSV com uma linha por pedido pago no período. Separador ";" e BOM para abrir bem no Excel em português. */
    public function csv(): void
    {
        [$de, $ate] = $this->periodo();
        $st = $this->db()->prepare(
            "SELECT p.numero, p.pago_em, c.nome, c.email, p.nif_factura, p.entrega_provincia, p.metodo_pagamento, p.metodo_entrega,
                    p.subtotal, p.desconto, p.portes, p.iva, p.total, p.estado
               FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
              WHERE p.pago_em BETWEEN ? AND ? ORDER BY p.pago_em"
        );
        $st->execute([$de . ' 00:00:00', $ate . ' 23:59:59']);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"vendas-$de-a-$ate.csv\"");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Pedido', 'Pago em', 'Cliente', 'Email', 'NIF', 'Província', 'Pagamento', 'Entrega', 'Subtotal', 'Desconto', 'Portes', 'IVA incluído', 'Total', 'Estado'], ';');
        $n = fn ($v) => number_format((float) $v, 2, ',', '');
        while ($r = $st->fetch()) {
            fputcsv($out, [$r['numero'], $r['pago_em'], $r['nome'], $r['email'], $r['nif_factura'], $r['entrega_provincia'],
                Pagamentos::nome($r['metodo_pagamento']), $r['metodo_entrega'], $n($r['subtotal']), $n($r['desconto']), $n($r['portes']), $n($r['iva']), $n($r['total']), $r['estado']], ';');
        }
        fclose($out);
        exit;
    }
}
