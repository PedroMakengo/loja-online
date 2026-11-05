<?php
// =====================================================================
// PedidoController — detalhe e rastreamento do pedido, instruções de
// pagamento, comprovativo de transferência, nova tentativa de
// pagamento e cancelamento
// =====================================================================

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Flash;
use App\Models\Pedido;
use App\Servicos\Pagamentos;

class PedidoController extends Controller
{
    private Pedido $modelo;

    public function __construct()
    {
        Auth::exigir();
        $this->modelo = new Pedido();
    }

    private function pedidoOu404(string $numero): array
    {
        $p = $this->modelo->porNumeroDoCliente($numero, (int) Auth::id());
        if (!$p) {
            (new ErroController())->naoEncontrado();
            exit;
        }
        return $p;
    }

    public function mostrar(string $numero): void
    {
        Pagamentos::expirarPendentes();
        $pedido = $this->pedidoOu404($numero);

        $this->view('conta/pedido', [
            'pedido'     => $pedido,
            'itens'      => $this->modelo->itens((int) $pedido['id']),
            'historico'  => $this->modelo->historico((int) $pedido['id']),
            'pagamento'  => $this->modelo->pagamentoActual((int) $pedido['id']),
            'metodos'    => Pagamentos::disponiveis(),
            'banco'      => Pagamentos::config()['banco'],
            'simulado'   => Pagamentos::simulado() && PAGAMENTOS_SIMULADOS,
            'cliente'    => Auth::cliente(),
            'seccao'     => 'pedidos',
            'seo'        => ['titulo' => 'Pedido ' . $numero, 'noindex' => true],
        ]);
    }

    /** GET AJAX — estado actual, para a página actualizar sozinha enquanto se aguarda o pagamento. */
    public function estado(string $numero): void
    {
        $pedido = $this->pedidoOu404($numero);
        $pg = $this->modelo->pagamentoActual((int) $pedido['id']);
        $this->json(['estado' => $pedido['estado'], 'pagamento' => $pg['estado'] ?? null]);
    }

    public function comprovativo(string $numero): void
    {
        $pedido = $this->pedidoOu404($numero);
        $pg = $this->modelo->pagamentoActual((int) $pedido['id']);

        if ($pedido['estado'] !== 'pendente' || !$pg || $pg['metodo'] !== 'transferencia' || $pg['estado'] !== 'pendente') {
            $this->redirecionar('/conta/pedidos/' . $numero);
        }

        $f = $_FILES['comprovativo'] ?? null;
        $erro = match (true) {
            !$f || $f['error'] === UPLOAD_ERR_NO_FILE                   => 'Escolha o ficheiro do comprovativo.',
            $f['error'] === UPLOAD_ERR_INI_SIZE || $f['size'] > 5242880 => 'O ficheiro tem mais de 5 MB. Envie uma fotografia ou um PDF mais pequeno.',
            $f['error'] !== UPLOAD_ERR_OK                               => 'O envio falhou. Tente de novo.',
            default                                                     => null,
        };

        $extensoes = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$erro) {
            // Tipo verificado pelo conteúdo do ficheiro, não pelo nome
            $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            if (!isset($extensoes[$tipo])) {
                $erro = 'Envie o comprovativo em PDF, JPG ou PNG.';
            }
        }
        if ($erro) {
            Flash::definir('erro', $erro);
            $this->redirecionar('/conta/pedidos/' . $numero . '#pagamento');
        }

        $pasta = RAIZ . '/storage/comprovativos';
        if (!is_dir($pasta)) {
            mkdir($pasta, 0750, true);
        }
        $nome = $numero . '-' . bin2hex(random_bytes(6)) . '.' . $extensoes[$tipo];
        if (!move_uploaded_file($f['tmp_name'], $pasta . '/' . $nome)) {
            Flash::definir('erro', 'Não foi possível guardar o ficheiro. Tente de novo.');
            $this->redirecionar('/conta/pedidos/' . $numero . '#pagamento');
        }

        $db = Database::ligacao();
        $db->prepare("UPDATE pagamentos SET comprovativo = ? WHERE id = ?")->execute([$nome, $pg['id']]);
        $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'pendente', ?)")
           ->execute([$pedido['id'], 'Comprovativo de transferência recebido. A verificar com o banco.']);

        Flash::definir('ok', 'Comprovativo recebido. Confirmamos o pagamento em até 24 horas úteis.');
        $this->redirecionar('/conta/pedidos/' . $numero);
    }

    /** Nova tentativa (push expirado ou recusado) — pode trocar de método. */
    public function repetirPagamento(string $numero): void
    {
        $pedido  = $this->pedidoOu404($numero);
        $metodos = Pagamentos::disponiveis();
        $metodo  = (string) ($_POST['metodo_pagamento'] ?? '');
        $pg      = $this->modelo->pagamentoActual((int) $pedido['id']);

        if ($pedido['estado'] !== 'pendente' || !isset($metodos[$metodo])) {
            $this->redirecionar('/conta/pedidos/' . $numero);
        }
        $telefone = null;
        if ($metodos[$metodo]['pede_telefone']) {
            $telefone = telefoneAngola($_POST['telefone_pagamento'] ?? '');
            if (!$telefone) {
                Flash::definir('erro', 'Indique um número angolano com 9 dígitos, a começar por 9.');
                $this->redirecionar('/conta/pedidos/' . $numero . '#pagamento');
            }
        }

        $db = Database::ligacao();
        $db->beginTransaction();
        $db->prepare("UPDATE pagamentos SET estado = 'expirado' WHERE pedido_id = ? AND estado = 'pendente'")->execute([$pedido['id']]);
        Pagamentos::iniciar($db, (int) $pedido['id'], $metodo, (float) $pedido['total'], $telefone);
        $db->prepare("UPDATE pedidos SET metodo_pagamento = ? WHERE id = ?")->execute([$metodo, $pedido['id']]);
        if ($pg && $pg['metodo'] !== $metodo) {
            $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'pendente', ?)")
               ->execute([$pedido['id'], 'Forma de pagamento alterada para ' . Pagamentos::nome($metodo) . '.']);
        }
        $db->commit();

        Flash::definir('ok', $metodos[$metodo]['tipo'] === 'push' ? 'Enviámos um novo pedido de pagamento para o seu telemóvel.' : 'Forma de pagamento actualizada.');
        $this->redirecionar('/conta/pedidos/' . $numero . '#pagamento');
    }

    public function cancelar(string $numero): void
    {
        $pedido = $this->pedidoOu404($numero);
        if (Pedido::cancelarComStock((int) $pedido['id'], 'Cancelado pelo cliente.')) {
            Flash::definir('ok', 'Pedido cancelado.');
        } else {
            Flash::definir('erro', 'Este pedido já não pode ser cancelado aqui. Fale connosco pelo WhatsApp.');
        }
        $this->redirecionar('/conta/pedidos/' . $numero);
    }

    /** Só em desenvolvimento com o driver simulado: confirma o pagamento actual. */
    public function simular(string $numero): void
    {
        if (!(Pagamentos::simulado() && PAGAMENTOS_SIMULADOS)) {
            (new ErroController())->naoEncontrado();
            return;
        }
        $pedido = $this->pedidoOu404($numero);
        $pg = $this->modelo->pagamentoActual((int) $pedido['id']);
        if ($pg) {
            Pagamentos::confirmar((int) $pg['id'], 'Pagamento confirmado por ' . Pagamentos::nome($pg['metodo']) . ' (simulação).');
        }
        Flash::definir('ok', 'Pagamento simulado com sucesso.');
        $this->redirecionar('/conta/pedidos/' . $numero);
    }
}
