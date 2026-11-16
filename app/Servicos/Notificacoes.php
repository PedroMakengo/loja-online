<?php
// =====================================================================
// App\Servicos\Notificacoes — emails do ciclo de vida do pedido.
//  cliente(): avisa o dono do pedido (saudação com o primeiro nome).
//  loja():    aviso interno para EMAIL_NOTIFICACOES (equipa da loja).
// Mapa completo de quando cada email sai:
//   .claude/skills/pedidos-notificacoes/SKILL.md
// =====================================================================

namespace App\Servicos;

use Core\Database;
use Core\Email;

class Notificacoes
{
    /** Email para o cliente; $html é o corpo depois de "Olá <nome>,". */
    public static function cliente(int $clienteId, string $assunto, string $html): bool
    {
        $st = Database::ligacao()->prepare("SELECT nome, email FROM clientes WHERE id = ?");
        $st->execute([$clienteId]);
        $c = $st->fetch();
        if (!$c || empty($c['email'])) {
            return false;
        }
        return Email::enviar($c['email'], $assunto, '<p>Olá ' . e(explode(' ', trim($c['nome']))[0]) . ',</p>' . $html);
    }

    /** Aviso interno para a equipa (desligado com EMAIL_NOTIFICACOES=nao). */
    public static function loja(string $assunto, string $html): bool
    {
        if (!filter_var(EMAIL_NOTIFICACOES, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return Email::enviar(EMAIL_NOTIFICACOES, '[Loja] ' . $assunto, $html);
    }

    public static function linkPedido(string $numero): string
    {
        return '<p><a href="' . URL_BASE . '/conta/pedidos/' . e($numero) . '">Ver o pedido</a></p>';
    }

    public static function linkAdmin(string $numero): string
    {
        return '<p><a href="' . URL_BASE . '/admin/pedidos/' . e($numero) . '">Abrir no painel</a></p>';
    }

    // ----- Avisos usados em mais de um sítio -------------------------------------

    /** Pedido cancelado automaticamente (prazo de pagamento terminou ou abandonado). */
    public static function canceladoPorFaltaDePagamento(int $pedidoId): void
    {
        $st = Database::ligacao()->prepare("SELECT numero, cliente_id FROM pedidos WHERE id = ?");
        $st->execute([$pedidoId]);
        if ($p = $st->fetch()) {
            self::cliente((int) $p['cliente_id'], 'Pedido ' . $p['numero'] . ' cancelado por falta de pagamento',
                '<p>O prazo para pagar o pedido <strong>' . e($p['numero']) . '</strong> terminou e a reserva dos produtos foi libertada.</p>'
                . '<p>Se ainda quiser estes produtos, pode fazer um novo pedido. Se já pagou, responda a este email com o comprovativo.</p>'
                . '<p><a href="' . URL_BASE . '/carrinho">Voltar à loja</a></p>');
        }
    }
}
