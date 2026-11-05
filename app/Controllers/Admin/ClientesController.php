<?php

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;

class ClientesController extends Base
{
    protected ?string $area = 'clientes';

    public function index(): void
    {
        $w = ['1=1']; $p = [];
        if ($q = trim((string) ($_GET['q'] ?? ''))) {
            $like = '%' . addcslashes($q, '%_') . '%';
            $dig = preg_replace('/\D/', '', $q);
            $w[] = '(c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ? OR c.nif LIKE ?)';
            array_push($p, $like, $like, $dig ? "%$dig%" : $like, $like);
        }
        $where = implode(' AND ', $w);
        $ordem = match ($_GET['ordem'] ?? '') { 'gasto' => 'gasto DESC', 'pedidos' => 'pedidos DESC', default => 'c.criado_em DESC' };

        [$clientes, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM clientes c WHERE $where",
            "SELECT c.id, c.nome, c.email, c.telefone, c.estado, c.criado_em, c.ultimo_login_em,
                    COUNT(p.id) AS pedidos,
                    COALESCE(SUM(CASE WHEN p.pago_em IS NOT NULL AND p.estado NOT IN ('cancelado','reembolsado') THEN p.total END), 0) AS gasto
               FROM clientes c LEFT JOIN pedidos p ON p.cliente_id = c.id
              WHERE $where GROUP BY c.id ORDER BY $ordem",
            $p
        );
        $this->pagina('clientes/lista', compact('clientes', 'pg'), 'Clientes');
    }

    public function mostrar(string $id): void
    {
        $st = $this->db()->prepare("SELECT * FROM clientes WHERE id = ?");
        $st->execute([(int) $id]);
        $cliente = $st->fetch();
        if (!$cliente) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            return;
        }
        $q = fn ($sql) => (function ($s) use ($id) { $s->execute([(int) $id]); return $s->fetchAll(); })($this->db()->prepare($sql));

        $this->pagina('clientes/mostrar', [
            'cliente'   => $cliente,
            'pedidos'   => $q("SELECT numero, total, estado, metodo_pagamento, criado_em FROM pedidos WHERE cliente_id = ? ORDER BY criado_em DESC"),
            'enderecos' => $q("SELECT * FROM enderecos WHERE cliente_id = ? ORDER BY principal DESC"),
            'favoritos' => $q("SELECT p.nome, p.slug, p.preco FROM favoritos f JOIN produtos p ON p.id = f.produto_id WHERE f.cliente_id = ? ORDER BY f.criado_em DESC LIMIT 10"),
            'pontos'    => \App\Servicos\Fidelidade::historico((int) $id, 15),
            'totais'    => $q("SELECT COUNT(*) pedidos, COALESCE(SUM(CASE WHEN pago_em IS NOT NULL AND estado NOT IN ('cancelado','reembolsado') THEN total END),0) gasto FROM pedidos WHERE cliente_id = ?")[0],
        ], $cliente['nome']);
    }

    /** Ajuste manual de pontos (compensação, correcção). Fica registado com o autor. */
    public function ajustarPontos(string $id): void
    {
        AdminAuth::exigir('pagamentos');
        $pontos = (int) ($_POST['pontos'] ?? 0);
        $motivo = trim(mb_substr((string) ($_POST['motivo'] ?? ''), 0, 150));
        if ($pontos === 0 || abs($pontos) > 100000 || $motivo === '') {
            Flash::definir('erro', 'Indique os pontos (positivos para dar, negativos para retirar) e o motivo.');
            $this->redirecionar('/admin/clientes/' . (int) $id);
        }
        $db = $this->db();
        $db->beginTransaction();
        \App\Servicos\Fidelidade::movimento($db, (int) $id, null, 'ajuste', $pontos, $motivo, AdminAuth::id());
        $db->commit();
        AdminAuth::registar('ajustou_pontos', 'cliente', $id, ($pontos > 0 ? '+' : '') . $pontos . ': ' . $motivo);
        Flash::definir('ok', 'Pontos actualizados.');
        $this->redirecionar('/admin/clientes/' . (int) $id);
    }

    public function alternarEstado(string $id): void
    {
        AdminAuth::exigir('pagamentos'); // só super/gestor bloqueiam contas
        $st = $this->db()->prepare("SELECT estado FROM clientes WHERE id = ?");
        $st->execute([(int) $id]);
        $actual = $st->fetchColumn();
        if ($actual) {
            $novo = $actual === 'activo' ? 'bloqueado' : 'activo';
            $this->db()->prepare("UPDATE clientes SET estado = ? WHERE id = ?")->execute([$novo, (int) $id]);
            if ($novo === 'bloqueado') {
                $this->db()->prepare("DELETE FROM sessoes_lembradas WHERE cliente_id = ?")->execute([(int) $id]);
            }
            AdminAuth::registar($novo === 'bloqueado' ? 'bloqueou' : 'desbloqueou', 'cliente', $id);
            Flash::definir('ok', $novo === 'bloqueado' ? 'Conta bloqueada. O cliente deixa de conseguir entrar.' : 'Conta reactivada.');
        }
        $this->redirecionar('/admin/clientes/' . (int) $id);
    }
}
