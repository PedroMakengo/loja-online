<?php

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;
use Core\Validador;

class AdminsController extends Base
{
    protected ?string $area = 'admins';

    public function index(): void
    {
        $this->pagina('admins', [
            'admins'   => $this->db()->query("SELECT id, nome, email, papel, activo, ultimo_login_em FROM admins ORDER BY activo DESC, nome")->fetchAll(),
            'registos' => $this->db()->query("SELECT r.*, a.nome FROM admin_registos r LEFT JOIN admins a ON a.id = r.admin_id ORDER BY r.id DESC LIMIT 40")->fetchAll(),
        ], 'Administradores');
    }

    public function criar(): void
    {
        $v = (new Validador($_POST))
            ->nome('nome')
            ->email('email')
            ->emLista('papel', array_keys(AdminAuth::PAPEIS), 'Escolha o papel.');
        if (!$v->falhou()) {
            $st = $this->db()->prepare("SELECT 1 FROM admins WHERE email = ?");
            $st->execute([$v->dados()['email']]);
            if ($st->fetchColumn()) {
                $v->adicionarErro('email', 'Já existe um administrador com este email.');
            }
        }
        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            $this->redirecionar('/admin/administradores');
        }

        // Senha temporária: mostrada uma vez; tem de ser trocada no primeiro acesso
        $temporaria = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
        $d = $v->dados();
        $this->db()->prepare("INSERT INTO admins (nome, email, senha_hash, papel, deve_mudar_senha) VALUES (?, ?, ?, ?, 1)")
             ->execute([$d['nome'], $d['email'], password_hash($temporaria, PASSWORD_DEFAULT), $d['papel']]);
        AdminAuth::registar('criou', 'admin', $this->db()->lastInsertId(), $d['email'] . ' (' . $d['papel'] . ')');

        Flash::definir('importante', "Administrador criado. Senha temporária: $temporaria — partilhe-a por um canal seguro; vai ser pedida uma nova no primeiro acesso.");
        $this->redirecionar('/admin/administradores');
    }

    public function alternar(string $id): void
    {
        if ((int) $id === AdminAuth::id()) {
            Flash::definir('erro', 'Não pode desactivar a sua própria conta.');
            $this->redirecionar('/admin/administradores');
        }
        $this->db()->prepare("UPDATE admins SET activo = 1 - activo WHERE id = ?")->execute([(int) $id]);
        AdminAuth::registar('alternou_activo', 'admin', $id);
        Flash::definir('ok', 'Acesso actualizado.');
        $this->redirecionar('/admin/administradores');
    }
}
