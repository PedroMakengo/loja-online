<?php
// =====================================================================
// Admin\AvaliacoesController — moderação de avaliações.
// Aprovar recalcula a média do produto e, na primeira moderação,
// dá os pontos ao cliente. Pode responder publicamente.
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;
use App\Models\Avaliacao;
use App\Servicos\Fidelidade;

class AvaliacoesController extends Base
{
    protected ?string $area = 'clientes';

    public function index(): void
    {
        $estado = in_array($_GET['estado'] ?? 'pendente', ['pendente', 'aprovada', 'rejeitada', 'todas'], true) ? ($_GET['estado'] ?? 'pendente') : 'pendente';
        $w = $estado === 'todas' ? '1=1' : 'a.estado = ?';
        $p = $estado === 'todas' ? [] : [$estado];

        [$avaliacoes, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM avaliacoes a WHERE $w",
            "SELECT a.*, c.nome AS cliente, c.id AS cliente_id, pr.nome AS produto, pr.slug, pr.id AS produto_id, pe.numero
               FROM avaliacoes a JOIN clientes c ON c.id = a.cliente_id JOIN produtos pr ON pr.id = a.produto_id
          LEFT JOIN pedidos pe ON pe.id = a.pedido_id
              WHERE $w ORDER BY a.criado_em " . ($estado === 'pendente' ? 'ASC' : 'DESC'),
            $p, 20
        );
        $contagens = $this->db()->query("SELECT estado, COUNT(*) FROM avaliacoes GROUP BY estado")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $this->pagina('avaliacoes', compact('avaliacoes', 'pg', 'estado', 'contagens'), 'Avaliações');
    }

    public function moderar(string $id): void
    {
        $accao    = (string) ($_POST['accao'] ?? '');
        $resposta = trim(mb_substr((string) ($_POST['resposta'] ?? ''), 0, 600));
        $db = $this->db();

        $st = $db->prepare("SELECT a.*, pr.nome AS produto FROM avaliacoes a JOIN produtos pr ON pr.id = a.produto_id WHERE a.id = ?");
        $st->execute([(int) $id]);
        $a = $st->fetch();
        if (!$a || !in_array($accao, ['aprovar', 'rejeitar', 'responder'], true)) {
            $this->redirecionar('/admin/avaliacoes');
        }

        $primeiraModeracao = $a['moderado_em'] === null;
        $db->beginTransaction();
        if ($accao === 'responder') {
            $db->prepare("UPDATE avaliacoes SET resposta_loja = NULLIF(?, ''), respondido_em = IF(? = '', NULL, NOW()) WHERE id = ?")
               ->execute([$resposta, $resposta, $a['id']]);
        } else {
            $novo = $accao === 'aprovar' ? 'aprovada' : 'rejeitada';
            $db->prepare("UPDATE avaliacoes SET estado = ?, moderado_por = ?, moderado_em = NOW(),
                                 resposta_loja = COALESCE(NULLIF(?, ''), resposta_loja),
                                 respondido_em = IF(? <> '', NOW(), respondido_em)
                           WHERE id = ?")
               ->execute([$novo, AdminAuth::id(), $resposta, $resposta, $a['id']]);
            if ($novo === 'aprovada' && $primeiraModeracao) {
                Fidelidade::movimento($db, (int) $a['cliente_id'], null, 'avaliacao', (int) Fidelidade::cfg()['pontos_avaliacao'],
                    'Avaliação de ' . mb_strimwidth($a['produto'], 0, 120, '…'));
            }
        }
        $db->commit();

        (new Avaliacao())->recalcular((int) $a['produto_id']);
        AdminAuth::registar($accao === 'responder' ? 'respondeu' : ($accao === 'aprovar' ? 'aprovou' : 'rejeitou'), 'avaliacao', $a['id'], $a['produto']);
        Flash::definir('ok', match ($accao) {
            'aprovar'   => 'Avaliação publicada' . ($primeiraModeracao ? ' e pontos atribuídos ao cliente.' : '.'),
            'rejeitar'  => 'Avaliação rejeitada.',
            'responder' => 'Resposta guardada.',
        });
        $this->redirecionar('/admin/avaliacoes' . (isset($_POST['voltar_estado']) ? '?estado=' . urlencode((string) $_POST['voltar_estado']) : ''));
    }
}
