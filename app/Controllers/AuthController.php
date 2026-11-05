<?php
// =====================================================================
// AuthController — entrar, criar conta, sair e recuperar a senha
// =====================================================================

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Email;
use Core\Flash;
use Core\Validador;
use App\Models\Carrinho;
use App\Models\Cliente;

class AuthController extends Controller
{
    // ----- Entrar ---------------------------------------------------------

    public function entrar(): void
    {
        if (Auth::autenticado()) {
            $this->redirecionar(caminhoSeguro($_GET['voltar'] ?? null));
        }
        $this->view('auth/entrar', [
            'voltar' => caminhoSeguro($_GET['voltar'] ?? null),
            'seo'    => ['titulo' => 'Entrar', 'noindex' => true],
        ]);
    }

    public function autenticar(): void
    {
        $email  = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha  = (string) ($_POST['senha'] ?? '');
        $voltar = caminhoSeguro($_POST['voltar'] ?? null);
        $voltarParaForm = '/entrar?voltar=' . urlencode($voltar);

        if ($email === '' || $senha === '') {
            Flash::guardarFormulario(['email' => 'Indique o email e a senha.'], $_POST);
            $this->redirecionar($voltarParaForm);
        }

        if (Auth::bloqueado($email)) {
            Flash::guardarFormulario(['email' => 'Demasiadas tentativas. Aguarde ' . Auth::minutosBloqueio() . ' minutos ou recupere a senha.'], $_POST);
            $this->redirecionar($voltarParaForm);
        }

        $cliente = (new Cliente())->verificar($email, $senha);
        if (!$cliente) {
            Auth::registarFalha($email);
            Flash::guardarFormulario(['email' => 'Email ou senha incorrectos.'], $_POST);
            $this->redirecionar($voltarParaForm);
        }
        if ($cliente['estado'] !== 'activo') {
            Flash::guardarFormulario(['email' => 'Esta conta está suspensa. Contacte-nos pelo WhatsApp.'], $_POST);
            $this->redirecionar($voltarParaForm);
        }

        Auth::limparFalhas($email);
        Auth::entrar((int) $cliente['id'], !empty($_POST['lembrar']));
        (new Carrinho())->associarCliente((int) $cliente['id']);

        Flash::definir('ok', 'Olá, ' . explode(' ', $cliente['nome'])[0] . '. Sessão iniciada.');
        $this->redirecionar($voltar);
    }

    // ----- Criar conta ------------------------------------------------------

    public function registar(): void
    {
        if (Auth::autenticado()) {
            $this->redirecionar('/conta');
        }
        $this->view('auth/registar', [
            'voltar' => caminhoSeguro($_GET['voltar'] ?? null),
            'seo'    => ['titulo' => 'Criar conta', 'noindex' => true],
        ]);
    }

    public function criarConta(): void
    {
        $voltar = caminhoSeguro($_POST['voltar'] ?? null);
        $modelo = new Cliente();

        $v = (new Validador($_POST))
            ->nome('nome')
            ->email('email')
            ->telefone('telefone')
            ->senha('senha', 'senha_confirmacao')
            ->booleano('aceita_marketing');

        if (empty($_POST['aceita_termos'])) {
            $v->adicionarErro('aceita_termos', 'Para criar conta, aceite os termos e a política de privacidade.');
        }
        if (!$v->falhou() && $modelo->emailEmUso($v->dados()['email'])) {
            $v->adicionarErro('email', 'Já existe uma conta com este email. Entre ou recupere a senha.');
        }

        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            $this->redirecionar('/registar?voltar=' . urlencode($voltar));
        }

        $d  = $v->dados();
        $id = $modelo->registar($d);

        Auth::entrar($id);
        (new Carrinho())->associarCliente($id);

        Email::enviar($d['email'], 'Bem-vindo à ' . NOME_SITE,
            '<p>Olá ' . e(explode(' ', $d['nome'])[0]) . ',</p>'
            . '<p>A sua conta foi criada. Pode acompanhar pedidos, guardar moradas e favoritos em <a href="' . URL_BASE . '/conta">A minha conta</a>.</p>'
            . '<p>Use o código <strong>BEMVINDO10</strong> para 10% de desconto na primeira compra.</p>');

        Flash::definir('ok', 'Conta criada. Bem-vindo à ' . NOME_SITE . '!');
        $this->redirecionar($voltar);
    }

    // ----- Sair -----------------------------------------------------------------

    public function sair(): void
    {
        (new Carrinho())->esquecerDispositivo();
        Auth::sair();
        Flash::definir('ok', 'Sessão terminada.');
        $this->redirecionar('/');
    }

    // ----- Recuperar senha ------------------------------------------------------

    public function recuperar(): void
    {
        $this->view('auth/recuperar', ['seo' => ['titulo' => 'Recuperar senha', 'noindex' => true]]);
    }

    public function enviarRecuperacao(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::guardarFormulario(['email' => 'Indique o email com que criou a conta.'], $_POST);
            $this->redirecionar('/recuperar-senha');
        }

        // Limite de pedidos por email/IP, para não ser usado para enviar spam
        $chave = 'rec:' . $email;
        if (!Auth::bloqueado($chave)) {
            Auth::registarFalha($chave);
            $modelo  = new Cliente();
            $cliente = $modelo->porEmail($email);
            if ($cliente && $cliente['estado'] === 'activo') {
                $token = $modelo->criarTokenRecuperacao((int) $cliente['id']);
                $link  = URL_BASE . '/redefinir-senha/' . $token;
                Email::enviar($email, 'Redefinir a sua senha',
                    '<p>Recebemos um pedido para redefinir a senha da sua conta.</p>'
                    . '<p><a href="' . $link . '" style="display:inline-block;background:#2346F5;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:bold">Definir nova senha</a></p>'
                    . '<p>O link é válido durante 60 minutos. Se não fez este pedido, ignore este email — a senha actual continua a funcionar.</p>'
                    . '<p style="color:#5D6680;font-size:13px">' . $link . '</p>');
            }
        }

        // A mesma resposta exista ou não a conta (não revela que emails estão registados)
        $this->view('auth/recuperar', [
            'enviado' => $email,
            'seo'     => ['titulo' => 'Recuperar senha', 'noindex' => true],
        ]);
    }

    public function redefinir(string $token): void
    {
        $cliente = (new Cliente())->porTokenRecuperacao($token);
        $this->view('auth/redefinir', [
            'token'  => $token,
            'valido' => $cliente !== null,
            'seo'    => ['titulo' => 'Nova senha', 'noindex' => true],
        ]);
    }

    public function guardarNovaSenha(string $token): void
    {
        $modelo  = new Cliente();
        $cliente = $modelo->porTokenRecuperacao($token);
        if (!$cliente) {
            $this->redirecionar('/redefinir-senha/' . $token);
        }

        $v = (new Validador($_POST))->senha('senha', 'senha_confirmacao');
        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), []);
            $this->redirecionar('/redefinir-senha/' . $token);
        }

        $modelo->alterarSenha((int) $cliente['id'], $v->dados()['senha']);
        Auth::limparFalhas($cliente['email']);
        Auth::entrar((int) $cliente['id']);
        (new Carrinho())->associarCliente((int) $cliente['id']);

        Flash::definir('ok', 'Senha alterada. Já tem sessão iniciada.');
        $this->redirecionar('/conta');
    }
}
