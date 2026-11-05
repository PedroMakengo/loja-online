<?php

namespace App\Controllers\Admin;

use Core\AdminAuth;

class MensagensController extends Base
{
    protected ?string $area = 'mensagens';

    public function index(): void
    {
        $so = ($_GET['estado'] ?? '') === 'nao-lidas';
        [$mensagens, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM contactos" . ($so ? " WHERE lido = 0" : ''),
            "SELECT * FROM contactos" . ($so ? " WHERE lido = 0" : '') . " ORDER BY lido, criado_em DESC",
            []
        );
        $this->pagina('mensagens', compact('mensagens', 'pg', 'so'), 'Mensagens');
    }

    public function lida(string $id): void
    {
        $this->db()->prepare("UPDATE contactos SET lido = 1 - lido WHERE id = ?")->execute([(int) $id]);
        AdminAuth::registar('mensagem_lida', 'contacto', $id);
        if (eAjax()) {
            $this->json(['ok' => true]);
        }
        $this->redirecionar('/admin/mensagens');
    }
}
