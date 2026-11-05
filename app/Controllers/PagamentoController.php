<?php
// =====================================================================
// PagamentoController — notificações (webhooks) dos operadores.
//
// Formato esperado (JSON), assinado com HMAC-SHA256 do corpo bruto
// no cabeçalho X-Assinatura, usando 'segredo_webhook':
//   { "referencia": "123456789", "entidade": "01234", "valor": 389900, "estado": "pago" }
//   ou { "id_externo": "ABC123", "valor": 389900, "estado": "pago" }
//
// Cada operador tem o seu formato: adaptar o mapeamento em ler() ao
// contrato assinado. A lógica de confirmação não muda.
// =====================================================================

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use App\Servicos\Pagamentos;

class PagamentoController extends Controller
{
    public function notificacao(): void
    {
        $corpo = (string) file_get_contents('php://input');
        $assinatura = (string) ($_SERVER['HTTP_X_ASSINATURA'] ?? '');
        $valida = Pagamentos::assinaturaValida($corpo, $assinatura);
        $db = Database::ligacao();

        $registar = function (string $resultado) use ($db, $corpo, $valida) {
            $db->prepare("INSERT INTO pagamento_notificacoes (corpo, assinatura_valida, resultado, ip) VALUES (?, ?, ?, ?)")
               ->execute([mb_substr($corpo, 0, 5000), $valida ? 1 : 0, $resultado, $_SERVER['REMOTE_ADDR'] ?? null]);
        };

        if (!$valida) {
            $registar('assinatura inválida');
            $this->json(['ok' => false], 401);
        }

        $d = json_decode($corpo, true);
        if (!is_array($d)) {
            $registar('corpo inválido');
            $this->json(['ok' => false], 400);
        }

        if (!empty($d['referencia'])) {
            $st = $db->prepare("SELECT id, estado FROM pagamentos WHERE referencia = ? AND entidade = ? ORDER BY id DESC LIMIT 1");
            $st->execute([(string) $d['referencia'], (string) ($d['entidade'] ?? Pagamentos::config()['entidade'])]);
        } else {
            $st = $db->prepare("SELECT id, estado FROM pagamentos WHERE id_externo = ? ORDER BY id DESC LIMIT 1");
            $st->execute([(string) ($d['id_externo'] ?? '')]);
        }
        $pg = $st->fetch();

        if (!$pg) {
            $registar('pagamento não encontrado');
            // 200 para o operador não repetir indefinidamente; fica registado para análise
            $this->json(['ok' => true, 'aviso' => 'desconhecido']);
        }

        $estado = strtolower((string) ($d['estado'] ?? ''));
        if (in_array($estado, ['pago', 'confirmado', 'success', 'paid'], true)) {
            $ok = Pagamentos::confirmar((int) $pg['id'], 'Pagamento confirmado pelo operador.', isset($d['valor']) ? (float) $d['valor'] : null);
            $registar($ok ? 'confirmado' : 'ignorado (já confirmado, valor inferior ou pedido não pendente)');
        } elseif (in_array($estado, ['recusado', 'falhado', 'failed', 'rejected', 'cancelado'], true)) {
            $db->prepare("UPDATE pagamentos SET estado = 'falhado' WHERE id = ? AND estado = 'pendente'")->execute([$pg['id']]);
            $registar('falhado');
        } else {
            $registar('estado ignorado: ' . $estado);
        }

        $this->json(['ok' => true]);
    }
}
