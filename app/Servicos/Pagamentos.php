<?php
// =====================================================================
// app/Servicos/Pagamentos.php — Métodos de pagamento angolanos
//
// Tipos de fluxo:
//   referencia  -> Referência Multicaixa (entidade + referência, ATM / homebanking)
//   push        -> Multicaixa Express, Afrimoney, Unitel Money (pedido no telemóvel)
//   manual      -> Transferência bancária (comprovativo verificado pela loja)
//   redireccao  -> Cartões Visa/Mastercard (página segura do gateway)
//
// A confirmação chega sempre por um destes caminhos:
//   * webhook do operador  -> POST /pagamentos/notificacao (assinatura HMAC)
//   * confirmação manual   -> painel admin (transferências)
//   * simulação            -> só com AMBIENTE = 'dev' e driver 'simulado'
//
// PONTO DE INTEGRAÇÃO: para ligar um operador real, implementar os
// pedidos HTTP em pedirAoOperador() (gerar referência / enviar push /
// criar sessão de cartão) segundo a documentação do contrato assinado
// (ex.: EMIS GPO, ProxyPay, Afrimoney, Unitel Money). O resto do fluxo
// — base de dados, estados, stock, emails — já está pronto.
// =====================================================================

namespace App\Servicos;

use Core\Database;
use Core\Email;
use PDO;
use RuntimeException;

class Pagamentos
{
    public const METODOS = [
        'multicaixa_express'    => ['nome' => 'Multicaixa Express', 'tipo' => 'push',       'descricao' => 'Aceite o pagamento na app Multicaixa Express.', 'pede_telefone' => true],
        'referencia_multicaixa' => ['nome' => 'Referência Multicaixa', 'tipo' => 'referencia', 'descricao' => 'Pague num ATM ou no internet banking com entidade e referência.', 'pede_telefone' => false],
        'transferencia'         => ['nome' => 'Transferência bancária', 'tipo' => 'manual',  'descricao' => 'Transfira para o IBAN da loja e envie o comprovativo.', 'pede_telefone' => false],
        'afrimoney'             => ['nome' => 'Afrimoney', 'tipo' => 'push',                'descricao' => 'Confirme o pagamento na carteira Afrimoney.', 'pede_telefone' => true],
        'unitel_money'          => ['nome' => 'Unitel Money', 'tipo' => 'push',             'descricao' => 'Confirme o pagamento na carteira Unitel Money.', 'pede_telefone' => true],
        'cartao'                => ['nome' => 'Cartão Visa ou Mastercard', 'tipo' => 'redireccao', 'descricao' => 'Pague numa página segura com o seu cartão.', 'pede_telefone' => false],
    ];

    private static ?array $cfg = null;

    public static function config(): array
    {
        return self::$cfg ??= require RAIZ . '/config/pagamentos.php';
    }

    public static function simulado(): bool
    {
        return self::config()['driver'] === 'simulado';
    }

    /** Métodos activos, pela ordem da configuração. */
    public static function disponiveis(): array
    {
        $lista = [];
        foreach (self::config()['metodos_activos'] as $codigo) {
            if (isset(self::METODOS[$codigo])) {
                $lista[$codigo] = self::METODOS[$codigo] + ['codigo' => $codigo];
            }
        }
        return $lista;
    }

    public static function nome(string $codigo): string
    {
        return self::METODOS[$codigo]['nome'] ?? $codigo;
    }

    // ---------------------------------------------------------------------
    // Iniciar — cria o registo de pagamento dentro da transacção do pedido
    // ---------------------------------------------------------------------

    public static function iniciar(PDO $db, int $pedidoId, string $metodo, float $valor, ?string $telefone): int
    {
        $cfg  = self::config();
        $tipo = self::METODOS[$metodo]['tipo'] ?? throw new RuntimeException('Método de pagamento inválido.');

        $entidade = $referencia = null;
        $expira = match ($tipo) {
            'referencia' => date('Y-m-d H:i:s', strtotime('+' . $cfg['validade_referencia'] . ' hours')),
            'push'       => date('Y-m-d H:i:s', strtotime('+' . $cfg['validade_push'] . ' minutes')),
            'manual'     => date('Y-m-d H:i:s', strtotime('+' . $cfg['validade_transferencia'] . ' hours')),
            'redireccao' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        };

        if ($tipo === 'referencia') {
            $entidade = $cfg['entidade'];
        }

        // Até 5 tentativas em caso de colisão de referência (UNIQUE entidade+referencia)
        for ($i = 0; $i < 5; $i++) {
            if ($tipo === 'referencia') {
                $referencia = (string) random_int(100000000, 999999999);
            }
            try {
                $st = $db->prepare(
                    "INSERT INTO pagamentos (pedido_id, metodo, entidade, referencia, telefone, valor, estado, expira_em)
                     VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?)"
                );
                $st->execute([$pedidoId, $metodo, $entidade, $referencia, $telefone, $valor, $expira]);
                $id = (int) $db->lastInsertId();
                break;
            } catch (\PDOException $e) {
                if ($tipo !== 'referencia' || $e->getCode() !== '23000' || $i === 4) {
                    throw $e;
                }
            }
        }

        self::pedirAoOperador($db, $id, $metodo, $tipo);
        return $id;
    }

    /**
     * PONTO DE INTEGRAÇÃO com o operador real.
     * No driver 'simulado' apenas grava um id externo fictício.
     */
    private static function pedirAoOperador(PDO $db, int $pagamentoId, string $metodo, string $tipo): void
    {
        if (self::simulado()) {
            $db->prepare("UPDATE pagamentos SET id_externo = ? WHERE id = ?")
               ->execute(['SIM-' . strtoupper(bin2hex(random_bytes(6))), $pagamentoId]);
            return;
        }

        // Exemplo de onde ligar cada operador:
        //   'referencia' -> registar a referência no operador (ProxyPay / EMIS)
        //   'push'       -> enviar pedido de pagamento ao telemóvel (EMIS GPO / Afrimoney / Unitel Money)
        //   'redireccao' -> criar sessão de pagamento e guardar o URL em resposta_gateway
        //   'manual'     -> nada a fazer
        if ($tipo !== 'manual') {
            throw new RuntimeException("O método '$metodo' está activo mas o operador ainda não está ligado em Pagamentos::pedirAoOperador().");
        }
    }

    // ---------------------------------------------------------------------
    // Confirmar — idempotente: confirmar duas vezes não duplica nada
    // ---------------------------------------------------------------------

    /** Devolve true se o pagamento ficou confirmado agora (false se já estava ou não é possível). */
    public static function confirmar(int $pagamentoId, string $nota, ?float $valorRecebido = null): bool
    {
        $db = Database::ligacao();
        $db->beginTransaction();
        try {
            $st = $db->prepare("SELECT pg.*, p.numero, p.total, p.estado AS estado_pedido, p.cliente_id
                                  FROM pagamentos pg JOIN pedidos p ON p.id = pg.pedido_id
                                 WHERE pg.id = ? FOR UPDATE");
            $st->execute([$pagamentoId]);
            $pg = $st->fetch();

            if (!$pg || $pg['estado'] === 'confirmado' || $pg['estado_pedido'] !== 'pendente') {
                $db->rollBack();
                return false;
            }
            if ($valorRecebido !== null && round($valorRecebido, 2) < round((float) $pg['valor'], 2)) {
                $db->prepare("UPDATE pagamentos SET resposta_gateway = CONCAT(COALESCE(resposta_gateway,''), ?) WHERE id = ?")
                   ->execute(["\nValor recebido inferior: $valorRecebido", $pagamentoId]);
                $db->commit();
                return false;
            }

            $db->prepare("UPDATE pagamentos SET estado = 'confirmado', pago_em = NOW() WHERE id = ?")->execute([$pagamentoId]);
            $db->prepare("UPDATE pedidos SET estado = 'pago', pago_em = NOW() WHERE id = ?")->execute([$pg['pedido_id']]);
            $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'pago', ?)")->execute([$pg['pedido_id'], $nota]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $pontos = Fidelidade::aoConfirmarPagamento((int) $pg['pedido_id']);

        $c = $db->prepare("SELECT nome, email, pontos FROM clientes WHERE id = ?");
        $c->execute([$pg['cliente_id']]);
        $cliente = $c->fetch();
        Email::enviar($cliente['email'], 'Pagamento confirmado — pedido ' . $pg['numero'],
            '<p>Olá ' . e(explode(' ', $cliente['nome'])[0]) . ',</p>'
            . '<p>Recebemos o pagamento de <strong>' . kz($pg['valor']) . '</strong> do pedido <strong>' . e($pg['numero']) . '</strong>. Já estamos a prepará-lo.</p>'
            . ($pontos > 0 ? '<p>Ganhou <strong>' . $pontos . ' pontos</strong> com esta compra. Saldo actual: ' . (int) $cliente['pontos'] . ' pontos.</p>' : '')
            . '<p><a href="' . URL_BASE . '/conta/pedidos/' . $pg['numero'] . '">Acompanhar o pedido</a></p>');
        return true;
    }

    // ---------------------------------------------------------------------
    // Expirar — corre no cron e também de forma preguiçosa nas páginas
    // de checkout e de pedido (funciona mesmo sem cron configurado)
    // ---------------------------------------------------------------------

    public static function expirarPendentes(): int
    {
        $db = Database::ligacao();
        $ids = $db->query(
            "SELECT pg.id FROM pagamentos pg JOIN pedidos p ON p.id = pg.pedido_id
              WHERE pg.estado = 'pendente' AND pg.expira_em < NOW() AND p.estado = 'pendente'
                AND NOT (pg.metodo = 'transferencia' AND pg.comprovativo IS NOT NULL)
              LIMIT 100"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $db->prepare("UPDATE pagamentos SET estado = 'expirado' WHERE id = ? AND estado = 'pendente'")->execute([$id]);
            $pedidoId = (int) $db->query("SELECT pedido_id FROM pagamentos WHERE id = " . (int) $id)->fetchColumn();
            // Push expirado: o cliente pode tentar de novo. Restantes: o pedido é cancelado.
            $metodo = $db->query("SELECT metodo FROM pagamentos WHERE id = " . (int) $id)->fetchColumn();
            if ((self::METODOS[$metodo]['tipo'] ?? '') !== 'push') {
                \App\Models\Pedido::cancelarComStock($pedidoId, 'Prazo de pagamento terminou.');
            }
        }
        // Pedidos abandonados há mais de 72 h (ex.: push expirado e nunca repetido)
        $velhos = $db->query(
            "SELECT id FROM pedidos WHERE estado = 'pendente' AND criado_em < NOW() - INTERVAL 72 HOUR
                AND NOT EXISTS (SELECT 1 FROM pagamentos WHERE pedido_id = pedidos.id AND metodo = 'transferencia' AND comprovativo IS NOT NULL AND estado = 'pendente')
              LIMIT 100"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($velhos as $pid) {
            \App\Models\Pedido::cancelarComStock((int) $pid, 'Cancelado por falta de pagamento.');
        }

        return count($ids) + count($velhos);
    }

    // ---------------------------------------------------------------------
    // Webhooks
    // ---------------------------------------------------------------------

    public static function assinaturaValida(string $corpo, string $assinatura): bool
    {
        $esperada = hash_hmac('sha256', $corpo, self::config()['segredo_webhook']);
        return $assinatura !== '' && hash_equals($esperada, strtolower($assinatura));
    }

    /** Formata a referência em blocos de 3: 123 456 789 */
    public static function referenciaFormatada(?string $ref): string
    {
        return trim(chunk_split((string) $ref, 3, ' '));
    }
}
