<?php
// =====================================================================
// app/Servicos/Fidelidade.php — Programa de pontos
//
//   Ganhar:   pagamento confirmado (1 ponto / 1 000 Kz, sem portes),
//             bónus na primeira compra, avaliação aprovada.
//   Usar:     no checkout, a partir do mínimo, até 20% dos produtos.
//   Anular:   pedido cancelado devolve os pontos usados; pedido pago e
//             depois reembolsado retira os pontos que tinha dado.
//
// Cada movimento fica em pontos_movimentos (histórico do cliente) e o
// saldo em clientes.pontos. A chave única (pedido_id, tipo) garante que
// o mesmo pedido nunca dá nem gasta pontos duas vezes.
// =====================================================================

namespace App\Servicos;

use Core\Database;
use PDO;

class Fidelidade
{
    private static ?array $cfg = null;

    public static function cfg(): array
    {
        return self::$cfg ??= require RAIZ . '/config/fidelidade.php';
    }

    public static function activo(): bool
    {
        return (bool) self::cfg()['activo'];
    }

    public static function pontosPorValor(float $kz): int
    {
        return self::activo() ? (int) floor(max(0, $kz) / self::cfg()['kz_por_ponto']) : 0;
    }

    public static function valorEmKz(int $pontos): float
    {
        return $pontos * self::cfg()['valor_ponto'];
    }

    /** Quantos pontos o cliente pode usar nesta compra (0 se não atingir o mínimo). */
    public static function maximoResgatavel(int $saldo, float $subtotalProdutos): int
    {
        $c = self::cfg();
        if (!self::activo() || $saldo < $c['minimo_resgate']) {
            return 0;
        }
        $maxKz = $subtotalProdutos * $c['maximo_percentagem'] / 100;
        return (int) min($saldo, floor($maxKz / $c['valor_ponto']));
    }

    /**
     * Regista um movimento e actualiza o saldo. Devolve false se o movimento
     * já existia (mesmo pedido + tipo). O saldo nunca fica negativo.
     */
    public static function movimento(PDO $db, int $clienteId, ?int $pedidoId, string $tipo, int $pontos, string $descricao, ?int $adminId = null): bool
    {
        if ($pontos === 0) {
            return false;
        }
        if ($pontos < 0) {
            $st = $db->prepare("SELECT pontos FROM clientes WHERE id = ? FOR UPDATE");
            $st->execute([$clienteId]);
            $pontos = -min(abs($pontos), max(0, (int) $st->fetchColumn()));
            if ($pontos === 0) {
                return false;
            }
        }
        try {
            $db->prepare("INSERT INTO pontos_movimentos (cliente_id, pedido_id, tipo, pontos, descricao, admin_id) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$clienteId, $pedidoId, $tipo, $pontos, $descricao, $adminId]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false; // já registado
            }
            throw $e;
        }
        $db->prepare("UPDATE clientes SET pontos = GREATEST(0, pontos + ?) WHERE id = ?")->execute([$pontos, $clienteId]);
        return true;
    }

    /** Chamado depois de um pagamento ser confirmado. */
    public static function aoConfirmarPagamento(int $pedidoId): int
    {
        if (!self::activo()) {
            return 0;
        }
        $db = Database::ligacao();
        $st = $db->prepare("SELECT id, numero, cliente_id, total, portes FROM pedidos WHERE id = ?");
        $st->execute([$pedidoId]);
        $p = $st->fetch();
        if (!$p) {
            return 0;
        }

        $db->beginTransaction();
        $ganhos = self::pontosPorValor((float) $p['total'] - (float) $p['portes']);
        $total = 0;
        if (self::movimento($db, (int) $p['cliente_id'], $pedidoId, 'compra', $ganhos, 'Compra ' . $p['numero'])) {
            $total += $ganhos;
        }
        // Bónus da primeira compra paga
        $st = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ? AND pago_em IS NOT NULL AND id <> ?");
        $st->execute([$p['cliente_id'], $pedidoId]);
        if ((int) $st->fetchColumn() === 0 && self::movimento($db, (int) $p['cliente_id'], $pedidoId, 'bonus', self::cfg()['bonus_primeira_compra'], 'Bónus de primeira compra')) {
            $total += self::cfg()['bonus_primeira_compra'];
        }
        $db->prepare("UPDATE pedidos SET pontos_ganhos = ? WHERE id = ?")->execute([$total, $pedidoId]);
        $db->commit();
        return $total;
    }

    /**
     * Pedido cancelado ou reembolsado: devolve os pontos usados e retira os
     * ganhos. Deve correr dentro da transacção do cancelamento.
     */
    public static function aoCancelar(PDO $db, int $pedidoId): void
    {
        $st = $db->prepare("SELECT id, numero, cliente_id, pontos_usados, pontos_ganhos FROM pedidos WHERE id = ?");
        $st->execute([$pedidoId]);
        $p = $st->fetch();
        if (!$p) {
            return;
        }
        if ((int) $p['pontos_usados'] > 0) {
            self::movimento($db, (int) $p['cliente_id'], $pedidoId, 'estorno', (int) $p['pontos_usados'], 'Devolução dos pontos usados no pedido ' . $p['numero']);
        }
        if ((int) $p['pontos_ganhos'] > 0) {
            self::movimento($db, (int) $p['cliente_id'], $pedidoId, 'anulacao', -(int) $p['pontos_ganhos'], 'Anulação dos pontos do pedido ' . $p['numero']);
        }
    }

    public static function historico(int $clienteId, int $limite = 50): array
    {
        $st = Database::ligacao()->prepare(
            "SELECT m.*, p.numero FROM pontos_movimentos m LEFT JOIN pedidos p ON p.id = m.pedido_id
              WHERE m.cliente_id = ? ORDER BY m.criado_em DESC, m.id DESC LIMIT $limite"
        );
        $st->execute([$clienteId]);
        return $st->fetchAll();
    }
}
