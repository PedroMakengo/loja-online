<?php
// =====================================================================
// MarketingController — links dos emails: recuperar carrinho e
// cancelar subscrições. Os links de cancelamento são assinados (HMAC):
// ninguém consegue cancelar a subscrição de outra pessoa.
// =====================================================================

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Flash;
use App\Servicos\Marketing;
use App\Servicos\Newsletter;

class MarketingController extends Controller
{
    /** /carrinho/recuperar/{token} — regista o clique e leva ao carrinho. */
    public function recuperar(string $token): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            $this->redirecionar('/carrinho');
        }
        $db = Database::ligacao();
        $st = $db->prepare("SELECT id, cliente_id, convertido FROM carrinhos WHERE recuperacao_token = ?");
        $st->execute([$token]);
        $c = $st->fetch();
        if (!$c) {
            $this->redirecionar('/carrinho');
        }
        $db->prepare("UPDATE carrinhos SET recuperado_em = COALESCE(recuperado_em, NOW()), actualizado_em = actualizado_em WHERE id = ?")->execute([$c['id']]);

        if ($c['convertido']) {
            Flash::definir('ok', 'Esta compra já foi concluída. Pode acompanhá-la em "Os meus pedidos".');
            $this->redirecionar('/conta/pedidos');
        }
        if (!Auth::autenticado()) {
            Flash::definir('ok', 'Entre na sua conta para ver o carrinho que guardámos.');
            $this->redirecionar('/entrar?voltar=' . urlencode('/carrinho'));
        }
        $this->redirecionar('/carrinho');
    }

    // ----- Newsletter --------------------------------------------------------

    public function sairNewsletter(string $emailB64, string $assinatura): void
    {
        $email = $this->emailValido($emailB64, $assinatura);
        $this->view('paginas/cancelar-subscricao', [
            'tipo'  => 'newsletter',
            'email' => $email,
            'acao'  => url('/newsletter/sair/' . $emailB64 . '/' . $assinatura),
            'feito' => false,
            'seo'   => ['titulo' => 'Cancelar subscrição', 'noindex' => true],
        ]);
    }

    /** POST — do botão da página ou do "cancelar com um clique" do Gmail (RFC 8058). */
    public function confirmarSairNewsletter(string $emailB64, string $assinatura): void
    {
        $email = $this->emailValido($emailB64, $assinatura);
        Newsletter::cancelar($email);
        if (($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
            http_response_code(200);
            exit('OK');
        }
        $this->view('paginas/cancelar-subscricao', [
            'tipo' => 'newsletter', 'email' => $email, 'acao' => null, 'feito' => true,
            'seo'  => ['titulo' => 'Subscrição cancelada', 'noindex' => true],
        ]);
    }

    private function emailValido(string $emailB64, string $assinatura): string
    {
        $email = mb_strtolower(Marketing::deB64($emailB64));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !Marketing::valida('nl:' . $email, $assinatura)) {
            (new ErroController())->naoEncontrado();
            exit;
        }
        return $email;
    }

    // ----- Lembretes de carrinho ------------------------------------------------

    public function sairLembretes(string $id, string $assinatura): void
    {
        $this->validarCliente($id, $assinatura);
        $this->view('paginas/cancelar-subscricao', [
            'tipo' => 'lembretes', 'email' => null, 'acao' => url("/lembretes/sair/$id/$assinatura"), 'feito' => false,
            'seo'  => ['titulo' => 'Desligar lembretes', 'noindex' => true],
        ]);
    }

    public function confirmarSairLembretes(string $id, string $assinatura): void
    {
        $this->validarCliente($id, $assinatura);
        Database::ligacao()->prepare("UPDATE clientes SET recebe_lembretes = 0 WHERE id = ?")->execute([(int) $id]);
        if (($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
            exit('OK');
        }
        $this->view('paginas/cancelar-subscricao', [
            'tipo' => 'lembretes', 'email' => null, 'acao' => null, 'feito' => true,
            'seo'  => ['titulo' => 'Lembretes desligados', 'noindex' => true],
        ]);
    }

    private function validarCliente(string $id, string $assinatura): void
    {
        if (!ctype_digit($id) || !Marketing::valida('lb:' . (int) $id, $assinatura)) {
            (new ErroController())->naoEncontrado();
            exit;
        }
    }
}
