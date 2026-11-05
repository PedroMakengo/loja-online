<?php
// =====================================================================
// Admin\PedidosController — fila de pedidos, confirmação manual de
// pagamentos (transferências), estados de envio e cancelamentos.
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Email;
use Core\Flash;
use App\Models\Pedido;
use App\Servicos\Pagamentos;

class PedidosController extends Base
{
    protected ?string $area = 'pedidos';

    /** Transições permitidas a partir de cada estado. */
    private const TRANSICOES = [
        'pago'          => ['em_preparacao'],
        'em_preparacao' => ['enviado'],
        'enviado'       => ['entregue'],
    ];

    /** Separadores da fila de trabalho. */
    public const FILTROS = [
        ''               => 'Todos',
        'transferencias' => 'Transferências a verificar',
        'pago'           => 'Por preparar',
        'em_preparacao'  => 'Em preparação',
        'enviado'        => 'A caminho',
        'pendente'       => 'Aguardam pagamento',
        'entregue'       => 'Entregues',
        'cancelado'      => 'Cancelados',
    ];

    public function index(): void
    {
        Pagamentos::expirarPendentes();

        $w = ['1=1']; $p = [];
        $filtro = (string) ($_GET['estado'] ?? '');
        if ($filtro === 'transferencias') {
            $w[] = "p.estado = 'pendente' AND EXISTS (SELECT 1 FROM pagamentos pg WHERE pg.pedido_id = p.id AND pg.metodo = 'transferencia' AND pg.estado = 'pendente' AND pg.comprovativo IS NOT NULL)";
        } elseif (isset(Pedido::ESTADOS[$filtro])) {
            $w[] = 'p.estado = ?'; $p[] = $filtro;
        }
        if ($q = trim((string) ($_GET['q'] ?? ''))) {
            $w[] = '(p.numero LIKE ? OR c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ? OR p.entrega_telefone LIKE ?)';
            $like = '%' . addcslashes($q, '%_') . '%';
            $digitos = preg_replace('/\D/', '', $q);
            array_push($p, $like, $like, $like, $digitos ? '%' . $digitos . '%' : $like, $digitos ? '%' . $digitos . '%' : $like);
        }
        if (isset(Pagamentos::METODOS[$_GET['metodo'] ?? ''])) {
            $w[] = 'p.metodo_pagamento = ?'; $p[] = $_GET['metodo'];
        }
        $where = implode(' AND ', $w);

        // Fila: o que é mais antigo e precisa de acção primeiro
        $ordem = in_array($filtro, ['pago', 'em_preparacao', 'transferencias'], true) ? 'p.criado_em ASC' : 'p.criado_em DESC';

        [$pedidos, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM pedidos p JOIN clientes c ON c.id = p.cliente_id WHERE $where",
            "SELECT p.*, c.nome AS cliente, c.telefone AS cliente_telefone,
                    (SELECT SUM(quantidade) FROM pedido_itens WHERE pedido_id = p.id) AS artigos,
                    (SELECT comprovativo FROM pagamentos WHERE pedido_id = p.id ORDER BY id DESC LIMIT 1) AS comprovativo
               FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
              WHERE $where ORDER BY $ordem",
            $p
        );

        $contagens = $this->db()->query("SELECT estado, COUNT(*) FROM pedidos GROUP BY estado")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $contagens['transferencias'] = (int) $this->db()->query(
            "SELECT COUNT(*) FROM pagamentos pg JOIN pedidos p ON p.id = pg.pedido_id
              WHERE pg.metodo = 'transferencia' AND pg.estado = 'pendente' AND pg.comprovativo IS NOT NULL AND p.estado = 'pendente'"
        )->fetchColumn();

        $this->pagina('pedidos/lista', compact('pedidos', 'pg', 'filtro', 'contagens'), 'Pedidos');
    }

    public function mostrar(string $numero): void
    {
        $pedido = $this->obter($numero);
        $modelo = new Pedido();
        $st = $this->db()->prepare("SELECT id, nome, email, telefone, nif, criado_em,
                                           (SELECT COUNT(*) FROM pedidos WHERE cliente_id = c.id AND pago_em IS NOT NULL) AS compras
                                      FROM clientes c WHERE id = ?");
        $st->execute([$pedido['cliente_id']]);

        $pagamentos = $this->db()->prepare("SELECT * FROM pagamentos WHERE pedido_id = ? ORDER BY id DESC");
        $pagamentos->execute([$pedido['id']]);

        $registos = $this->db()->prepare(
            "SELECT r.*, a.nome AS admin FROM admin_registos r LEFT JOIN admins a ON a.id = r.admin_id
              WHERE r.alvo = 'pedido' AND r.alvo_id = ? ORDER BY r.criado_em DESC"
        );
        $registos->execute([$pedido['numero']]);

        $this->pagina('pedidos/mostrar', [
            'pedido'     => $pedido,
            'cliente'    => $st->fetch(),
            'itens'      => $modelo->itens((int) $pedido['id']),
            'historico'  => $modelo->historico((int) $pedido['id']),
            'pagamentos' => $pagamentos->fetchAll(),
            'registos'   => $registos->fetchAll(),
            'proximos'   => self::TRANSICOES[$pedido['estado']] ?? [],
        ], 'Pedido ' . $numero);
    }

    /** Confirmação manual (transferência verificada no extracto do banco). */
    public function confirmarPagamento(string $numero): void
    {
        AdminAuth::exigir('pagamentos');
        $pedido = $this->obter($numero);
        $pg = (new Pedido())->pagamentoActual((int) $pedido['id']);

        if (!$pg || $pedido['estado'] !== 'pendente') {
            Flash::definir('erro', 'Este pedido já não aguarda pagamento.');
        } elseif (empty($_POST['verificado'])) {
            Flash::definir('erro', 'Confirme que verificou a entrada do valor no banco.');
        } elseif (Pagamentos::confirmar((int) $pg['id'], 'Pagamento por ' . Pagamentos::nome($pg['metodo']) . ' confirmado pela loja.')) {
            AdminAuth::registar('confirmou_pagamento', 'pedido', $numero, Pagamentos::nome($pg['metodo']) . ' ' . strip_tags(kz($pg['valor'])));
            Flash::definir('ok', 'Pagamento confirmado. O cliente foi avisado por email.');
        }
        $this->redirecionar('/admin/pedidos/' . $numero);
    }

    /** Rejeitar comprovativo: o cliente pode enviar outro. */
    public function rejeitarComprovativo(string $numero): void
    {
        AdminAuth::exigir('pagamentos');
        $pedido = $this->obter($numero);
        $pg = (new Pedido())->pagamentoActual((int) $pedido['id']);
        $motivo = trim(mb_substr((string) ($_POST['motivo'] ?? ''), 0, 200)) ?: 'O valor não foi encontrado no extracto bancário.';

        if ($pg && $pg['comprovativo'] && $pedido['estado'] === 'pendente') {
            $this->db()->prepare("UPDATE pagamentos SET comprovativo = NULL, expira_em = GREATEST(expira_em, NOW() + INTERVAL 24 HOUR) WHERE id = ?")->execute([$pg['id']]);
            $this->db()->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'pendente', ?)")
                 ->execute([$pedido['id'], 'Comprovativo não aceite: ' . $motivo . ' Envie um novo comprovativo.']);
            AdminAuth::registar('rejeitou_comprovativo', 'pedido', $numero, $motivo);
            $this->avisarCliente($pedido, 'Precisamos de um novo comprovativo — pedido ' . $numero,
                '<p>Não conseguimos confirmar a transferência do pedido <strong>' . $numero . '</strong>: ' . e($motivo) . '</p>'
                . '<p><a href="' . URL_BASE . '/conta/pedidos/' . $numero . '">Enviar novo comprovativo</a>. A reserva dos produtos foi prolongada por 24 horas.</p>');
            Flash::definir('ok', 'Comprovativo rejeitado. O cliente foi avisado.');
        }
        $this->redirecionar('/admin/pedidos/' . $numero);
    }

    public function mudarEstado(string $numero): void
    {
        $pedido = $this->obter($numero);
        $novo   = (string) ($_POST['estado'] ?? '');
        $nota   = trim(mb_substr((string) ($_POST['nota'] ?? ''), 0, 255));

        if (!in_array($novo, self::TRANSICOES[$pedido['estado']] ?? [], true)) {
            Flash::definir('erro', 'Esta mudança de estado não é possível.');
            $this->redirecionar('/admin/pedidos/' . $numero);
        }

        $rastreio = trim(mb_substr((string) ($_POST['codigo_rastreio'] ?? ''), 0, 60));
        if ($novo === 'enviado' && $pedido['metodo_entrega'] !== 'levantamento' && $pedido['entrega_provincia'] !== 'Luanda' && $rastreio === '') {
            Flash::definir('erro', 'Indique o código de rastreamento da transportadora.');
            $this->redirecionar('/admin/pedidos/' . $numero);
        }

        $textos = [
            'em_preparacao' => 'Estamos a preparar a sua encomenda.',
            'enviado'       => $pedido['metodo_entrega'] === 'levantamento' ? 'Pronto para levantar na loja.' : 'A encomenda saiu para entrega.',
            'entregue'      => $pedido['metodo_entrega'] === 'levantamento' ? 'Levantado na loja.' : 'Encomenda entregue.',
        ];

        $db = $this->db();
        $db->beginTransaction();
        $db->prepare("UPDATE pedidos SET estado = ?, codigo_rastreio = COALESCE(NULLIF(?, ''), codigo_rastreio) WHERE id = ? AND estado = ?")
           ->execute([$novo, $rastreio, $pedido['id'], $pedido['estado']]);
        $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, ?, ?)")
           ->execute([$pedido['id'], $novo, $textos[$novo] . ($nota ? ' ' . $nota : '')]);
        $db->commit();

        AdminAuth::registar('estado_' . $novo, 'pedido', $numero, $rastreio ? 'Rastreio: ' . $rastreio : $nota);

        if ($novo !== 'em_preparacao') {
            $this->avisarCliente($pedido, ($novo === 'enviado' ? ($pedido['metodo_entrega'] === 'levantamento' ? 'Pronto para levantar' : 'A sua encomenda está a caminho') : 'Encomenda entregue') . ' — ' . $numero,
                '<p>' . $textos[$novo] . ($nota ? ' ' . e($nota) : '') . '</p>'
                . ($rastreio ? '<p>Código de rastreamento: <strong>' . e($rastreio) . '</strong></p>' : '')
                . '<p><a href="' . URL_BASE . '/conta/pedidos/' . $numero . '">Acompanhar o pedido</a></p>');
        }
        Flash::definir('ok', 'Estado actualizado para "' . Pedido::ESTADOS[$novo][0] . '".');
        $this->redirecionar('/admin/pedidos/' . $numero);
    }

    public function cancelar(string $numero): void
    {
        $pedido = $this->obter($numero);
        $motivo = trim(mb_substr((string) ($_POST['motivo'] ?? ''), 0, 200));
        if ($motivo === '') {
            Flash::definir('erro', 'Indique o motivo do cancelamento — fica visível para o cliente.');
            $this->redirecionar('/admin/pedidos/' . $numero);
        }

        if ($pedido['estado'] === 'pendente') {
            Pedido::cancelarComStock((int) $pedido['id'], 'Cancelado pela loja: ' . $motivo);
        } elseif (in_array($pedido['estado'], ['pago', 'em_preparacao'], true)) {
            // Pedido pago: devolve stock e fica "reembolsado" — o reembolso em si é feito fora do sistema
            $db = $this->db();
            $db->beginTransaction();
            $db->prepare("UPDATE produtos pr JOIN pedido_itens pi ON pi.produto_id = pr.id
                             SET pr.stock = pr.stock + pi.quantidade, pr.vendas = GREATEST(0, pr.vendas - pi.quantidade)
                           WHERE pi.pedido_id = ?")->execute([$pedido['id']]);
            \App\Servicos\Fidelidade::aoCancelar($db, (int) $pedido['id']);
            $db->prepare("UPDATE pedidos SET estado = 'reembolsado' WHERE id = ?")->execute([$pedido['id']]);
            $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'reembolsado', ?)")
               ->execute([$pedido['id'], 'Cancelado pela loja: ' . $motivo . ' O valor pago será devolvido.']);
            $db->commit();
        } else {
            Flash::definir('erro', 'Pedidos enviados ou entregues não podem ser cancelados aqui.');
            $this->redirecionar('/admin/pedidos/' . $numero);
        }

        AdminAuth::registar('cancelou', 'pedido', $numero, $motivo);
        $this->avisarCliente($pedido, 'Pedido ' . $numero . ' cancelado',
            '<p>O pedido <strong>' . $numero . '</strong> foi cancelado. Motivo: ' . e($motivo) . '</p>'
            . ($pedido['estado'] !== 'pendente' ? '<p>Vamos devolver o valor pago. Entraremos em contacto para combinar a devolução.</p>' : ''));
        Flash::definir('ok', $pedido['estado'] === 'pendente' ? 'Pedido cancelado e stock devolvido.' : 'Pedido marcado como reembolsado e stock devolvido. Trate da devolução do valor.');
        $this->redirecionar('/admin/pedidos/' . $numero);
    }

    /** Nota interna (não visível ao cliente). */
    public function nota(string $numero): void
    {
        $this->obter($numero);
        $nota = trim(mb_substr((string) ($_POST['nota'] ?? ''), 0, 500));
        if ($nota !== '') {
            AdminAuth::registar('nota', 'pedido', $numero, $nota);
        }
        $this->redirecionar('/admin/pedidos/' . $numero . '#notas');
    }

    /** Mostra o comprovativo (ficheiro fora da pasta pública). */
    public function comprovativo(string $ficheiro): void
    {
        AdminAuth::exigir('pagamentos');
        if (!preg_match('/^DTK-\d{4}-\d{6}-[a-f0-9]{12}\.(pdf|jpg|png|webp)$/', $ficheiro)) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            return;
        }
        $caminho = RAIZ . '/storage/comprovativos/' . $ficheiro;
        if (!is_file($caminho)) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            return;
        }
        $tipos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        header('Content-Type: ' . $tipos[pathinfo($ficheiro, PATHINFO_EXTENSION)]);
        header('Content-Length: ' . filesize($caminho));
        header('Content-Disposition: inline; filename="' . $ficheiro . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($caminho);
        exit;
    }

    // ---------------------------------------------------------------------

    private function obter(string $numero): array
    {
        $st = $this->db()->prepare("SELECT * FROM pedidos WHERE numero = ?");
        $st->execute([$numero]);
        $p = $st->fetch();
        if (!$p) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            exit;
        }
        return $p;
    }

    private function avisarCliente(array $pedido, string $assunto, string $html): void
    {
        $st = $this->db()->prepare("SELECT nome, email FROM clientes WHERE id = ?");
        $st->execute([$pedido['cliente_id']]);
        if ($c = $st->fetch()) {
            Email::enviar($c['email'], $assunto, '<p>Olá ' . e(explode(' ', $c['nome'])[0]) . ',</p>' . $html);
        }
    }
}
