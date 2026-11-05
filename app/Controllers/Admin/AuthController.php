<?php

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Auth;
use Core\Flash;
use Core\Validador;

class AuthController extends Base
{
    protected ?string $area = null;

    public function entrar(): void
    {
        if (AdminAuth::admin()) {
            $this->redirecionar('/admin');
        }
        $this->pagina('entrar', ['voltar' => caminhoSeguro($_GET['voltar'] ?? null, '/admin')], 'Entrar');
    }

    public function autenticar(): void
    {
        $email  = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha  = (string) ($_POST['senha'] ?? '');
        $voltar = caminhoSeguro($_POST['voltar'] ?? null, '/admin');
        $chave  = 'admin:' . $email;

        if (Auth::bloqueado($chave)) {
            Flash::guardarFormulario(['email' => 'Demasiadas tentativas. Aguarde ' . Auth::minutosBloqueio() . ' minutos.'], $_POST);
            $this->redirecionar('/admin/entrar');
        }

        $st = $this->db()->prepare("SELECT * FROM admins WHERE email = ? AND activo = 1");
        $st->execute([$email]);
        $admin = $st->fetch();

        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            Auth::registarFalha($chave);
            Flash::guardarFormulario(['email' => 'Email ou senha incorrectos.'], $_POST);
            $this->redirecionar('/admin/entrar?voltar=' . urlencode($voltar));
        }

        Auth::limparFalhas($chave);
        AdminAuth::entrar((int) $admin['id']);
        AdminAuth::registar('login', 'admin', $admin['id']);
        $this->redirecionar($admin['deve_mudar_senha'] ? '/admin/perfil' : $voltar);
    }

    public function sair(): void
    {
        AdminAuth::sair();
        $this->redirecionar('/admin/entrar');
    }

    public function perfil(): void
    {
        AdminAuth::exigir();
        $this->pagina('perfil', ['admin' => AdminAuth::admin()], 'O meu perfil');
    }

    public function guardarSenha(): void
    {
        AdminAuth::exigir();
        $id = (int) AdminAuth::id();
        $st = $this->db()->prepare("SELECT senha_hash FROM admins WHERE id = ?");
        $st->execute([$id]);

        $v = (new Validador($_POST))->senha('senha', 'senha_confirmacao');
        if (!password_verify((string) ($_POST['senha_actual'] ?? ''), (string) $st->fetchColumn())) {
            $v->adicionarErro('senha_actual', 'A senha actual não está correcta.');
        }
        if (!$v->falhou() && mb_strlen($v->dados()['senha']) < 10) {
            $v->adicionarErro('senha', 'No painel, a senha precisa de pelo menos 10 caracteres.');
        }
        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), []);
            $this->redirecionar('/admin/perfil');
        }

        $this->db()->prepare("UPDATE admins SET senha_hash = ?, deve_mudar_senha = 0 WHERE id = ?")
             ->execute([password_hash($v->dados()['senha'], PASSWORD_DEFAULT), $id]);
        AdminAuth::registar('alterou_senha', 'admin', $id);
        AdminAuth::entrar($id);
        Flash::definir('ok', 'Senha alterada.');
        $this->redirecionar('/admin');
    }
}
