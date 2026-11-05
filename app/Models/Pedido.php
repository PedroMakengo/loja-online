<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Pedido extends Model
{
    protected string $tabela = 'pedidos';

    public const ESTADOS = [
        'pendente'      => ['Aguarda pagamento', 'aviso'],
        'pago'          => ['Pago', 'marca'],
        'em_preparacao' => ['Em preparação', 'marca'],
        'enviado'       => ['A caminho', 'marca'],
        'entregue'      => ['Entregue', 'sucesso'],
        'cancelado'     => ['Cancelado', 'suave'],
        'reembolsado'   => ['Reembolsado', 'suave'],
    ];

    /** Etapas mostradas no rastreamento. */
    public const ETAPAS = [
        'pendente'      => 'Pedido recebido',
        'pago'          => 'Pagamento confirmado',
        'em_preparacao' => 'Em preparação',
        'enviado'       => 'A caminho',
        'entregue'      => 'Entregue',
    ];

    public function doCliente(int $clienteId, int $limite = 50): array
    {
        return $this->todos(
            "SELECT p.id, p.numero, p.total, p.estado, p.metodo_pagamento, p.criado_em,
                    (SELECT COALESCE(SUM(quantidade),0) FROM pedido_itens WHERE pedido_id = p.id) AS artigos,
                    (SELECT nome FROM pedido_itens WHERE pedido_id = p.id ORDER BY id LIMIT 1) AS primeiro_artigo
               FROM pedidos p
              WHERE p.cliente_id = ?
              ORDER BY p.criado_em DESC
              LIMIT $limite",
            [$clienteId]
        );
    }

    public function porNumeroDoCliente(string $numero, int $clienteId): ?array
    {
        return $this->um("SELECT * FROM pedidos WHERE numero = ? AND cliente_id = ? LIMIT 1", [$numero, $clienteId]);
    }

    public function itens(int $pedidoId): array
    {
        return $this->todos(
            "SELECT pi.*, p.slug, p.imagem, c.slug AS categoria_slug
               FROM pedido_itens pi
          LEFT JOIN produtos p ON p.id = pi.produto_id
          LEFT JOIN categorias c ON c.id = p.categoria_id
              WHERE pi.pedido_id = ? ORDER BY pi.id",
            [$pedidoId]
        );
    }

    public function historico(int $pedidoId): array
    {
        return $this->todos("SELECT * FROM pedido_historico WHERE pedido_id = ? ORDER BY criado_em, id", [$pedidoId]);
    }

    /** Pagamento mais recente do pedido. */
    public function pagamentoActual(int $pedidoId): ?array
    {
        return $this->um("SELECT * FROM pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1", [$pedidoId]);
    }

    /** Cancela um pedido pendente e devolve o stock. Seguro contra chamadas repetidas. */
    public static function cancelarComStock(int $pedidoId, string $nota): bool
    {
        $db = Database::ligacao();
        $db->beginTransaction();
        try {
            $st = $db->prepare("SELECT id, estado, cupao_id FROM pedidos WHERE id = ? FOR UPDATE");
            $st->execute([$pedidoId]);
            $p = $st->fetch();
            if (!$p || $p['estado'] !== 'pendente') {
                $db->rollBack();
                return false;
            }
            $db->prepare(
                "UPDATE produtos pr JOIN pedido_itens pi ON pi.produto_id = pr.id
                    SET pr.stock = pr.stock + pi.quantidade, pr.vendas = GREATEST(0, pr.vendas - pi.quantidade)
                  WHERE pi.pedido_id = ?"
            )->execute([$pedidoId]);
            if ($p['cupao_id']) {
                $db->prepare("UPDATE cupoes SET utilizacoes = GREATEST(0, utilizacoes - 1) WHERE id = ?")->execute([$p['cupao_id']]);
            }
            \App\Servicos\Fidelidade::aoCancelar($db, $pedidoId);
            $db->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?")->execute([$pedidoId]);
            $db->prepare("UPDATE pagamentos SET estado = 'expirado' WHERE pedido_id = ? AND estado = 'pendente'")->execute([$pedidoId]);
            $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'cancelado', ?)")->execute([$pedidoId, $nota]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
