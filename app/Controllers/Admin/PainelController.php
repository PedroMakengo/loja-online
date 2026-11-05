<?php
// Resumo: vendas, o que precisa de acção e evolução dos últimos 30 dias.

namespace App\Controllers\Admin;

class PainelController extends Base
{
    public function index(): void
    {
        $db = $this->db();

        $kpi = $db->query(
            "SELECT
               COALESCE(SUM(CASE WHEN DATE(pago_em) = CURDATE() THEN total END), 0)                            AS vendas_hoje,
               COUNT(CASE WHEN DATE(pago_em) = CURDATE() THEN 1 END)                                            AS pedidos_hoje,
               COALESCE(SUM(CASE WHEN pago_em >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN total END), 0)        AS vendas_mes,
               COUNT(CASE WHEN pago_em >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 END)                        AS pedidos_mes,
               COALESCE(SUM(CASE WHEN pago_em >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
                                  AND pago_em < DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                  AND DAY(pago_em) <= DAY(CURDATE()) THEN total END), 0)                       AS vendas_mes_anterior_ate_hoje
             FROM pedidos WHERE pago_em IS NOT NULL AND estado NOT IN ('cancelado','reembolsado')"
        )->fetch();

        $accoes = $db->query(
            "SELECT
               (SELECT COUNT(*) FROM pedidos WHERE estado = 'pago')                       AS por_preparar,
               (SELECT COUNT(*) FROM pedidos WHERE estado = 'em_preparacao')              AS em_preparacao,
               (SELECT COUNT(*) FROM pedidos WHERE estado = 'enviado')                    AS enviados,
               (SELECT COUNT(*) FROM pedidos WHERE estado = 'pendente')                   AS aguardam_pagamento,
               (SELECT COUNT(*) FROM pagamentos pg JOIN pedidos p ON p.id = pg.pedido_id
                 WHERE pg.metodo = 'transferencia' AND pg.estado = 'pendente' AND pg.comprovativo IS NOT NULL AND p.estado = 'pendente') AS transferencias,
               (SELECT COUNT(*) FROM produtos WHERE estado = 'activo' AND stock <= stock_minimo) AS stock_baixo,
               (SELECT COUNT(*) FROM produtos WHERE estado = 'activo' AND stock = 0)      AS esgotados,
               (SELECT COUNT(*) FROM contactos WHERE lido = 0)                            AS mensagens,
               (SELECT COUNT(*) FROM avaliacoes WHERE estado = 'pendente')               AS avaliacoes,
               (SELECT COUNT(*) FROM clientes WHERE criado_em >= CURDATE() - INTERVAL 30 DAY) AS clientes_novos"
        )->fetch();

        // Vendas por dia (30 dias), com dias sem vendas a zero
        $linhas = $db->query(
            "SELECT DATE(pago_em) AS dia, SUM(total) AS total, COUNT(*) AS pedidos
               FROM pedidos WHERE pago_em >= CURDATE() - INTERVAL 29 DAY AND estado NOT IN ('cancelado','reembolsado')
              GROUP BY DATE(pago_em)"
        )->fetchAll(\PDO::FETCH_UNIQUE);
        $serie = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $serie[] = ['dia' => $d, 'total' => (float) ($linhas[$d]['total'] ?? 0), 'pedidos' => (int) ($linhas[$d]['pedidos'] ?? 0)];
        }

        $ultimos = $db->query(
            "SELECT p.numero, p.total, p.estado, p.metodo_pagamento, p.criado_em, c.nome AS cliente
               FROM pedidos p JOIN clientes c ON c.id = p.cliente_id ORDER BY p.criado_em DESC LIMIT 8"
        )->fetchAll();

        $topProdutos = $db->query(
            "SELECT pi.nome, SUM(pi.quantidade) AS unidades, SUM(pi.total) AS receita
               FROM pedido_itens pi JOIN pedidos p ON p.id = pi.pedido_id
              WHERE p.pago_em >= CURDATE() - INTERVAL 29 DAY AND p.estado NOT IN ('cancelado','reembolsado')
              GROUP BY pi.nome ORDER BY receita DESC LIMIT 5"
        )->fetchAll();

        $this->pagina('painel', compact('kpi', 'accoes', 'serie', 'ultimos', 'topProdutos'), 'Resumo');
    }
}
