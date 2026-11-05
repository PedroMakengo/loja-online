<?php

namespace App\Models;

use Core\Model;

class Cliente extends Model
{
    protected string $tabela = 'clientes';

    public function porEmail(string $email): ?array
    {
        return $this->um("SELECT * FROM clientes WHERE email = ? LIMIT 1", [mb_strtolower(trim($email))]);
    }

    public function emailEmUso(string $email, ?int $excepto = null): bool
    {
        return (bool) $this->valor(
            "SELECT 1 FROM clientes WHERE email = ? AND id <> ?",
            [mb_strtolower(trim($email)), $excepto ?? 0]
        );
    }

    public function registar(array $d): int
    {
        return $this->inserir([
            'nome'             => $d['nome'],
            'email'            => mb_strtolower(trim($d['email'])),
            'telefone'         => $d['telefone'],
            'senha_hash'       => password_hash($d['senha'], PASSWORD_DEFAULT),
            'aceita_marketing' => $d['aceita_marketing'] ? 1 : 0,
        ]);
    }

    /** Devolve o cliente se as credenciais forem válidas. Actualiza o hash se o algoritmo mudou. */
    public function verificar(string $email, string $senha): ?array
    {
        $c = $this->porEmail($email);
        // Verificação feita mesmo sem cliente, para o tempo de resposta não revelar se o email existe
        $hash = $c['senha_hash'] ?? '$2y$10$shR46StZi3j1JXfoP2m80uEpDljX.v.H2.ADgaDjFkdD/FL/96PJq';
        if (!password_verify($senha, $hash) || !$c) {
            return null;
        }
        if (password_needs_rehash($c['senha_hash'], PASSWORD_DEFAULT)) {
            $this->executar("UPDATE clientes SET senha_hash = ? WHERE id = ?", [password_hash($senha, PASSWORD_DEFAULT), $c['id']]);
        }
        return $c;
    }

    public function actualizarDados(int $id, array $d): void
    {
        $this->executar(
            "UPDATE clientes SET nome = ?, email = ?, telefone = ?, nif = ?, aceita_marketing = ?, recebe_lembretes = ? WHERE id = ?",
            [$d['nome'], mb_strtolower(trim($d['email'])), $d['telefone'], $d['nif'] ?: null, $d['aceita_marketing'] ? 1 : 0, !empty($d['recebe_lembretes']) ? 1 : 0, $id]
        );
    }

    public function senhaCorrecta(int $id, string $senha): bool
    {
        return password_verify($senha, (string) $this->valor("SELECT senha_hash FROM clientes WHERE id = ?", [$id]));
    }

    public function alterarSenha(int $id, string $senha): void
    {
        $this->executar(
            "UPDATE clientes SET senha_hash = ?, token_recuperacao = NULL, token_expira_em = NULL WHERE id = ?",
            [password_hash($senha, PASSWORD_DEFAULT), $id]
        );
        // Termina sessões "lembradas" noutros dispositivos
        $this->executar("DELETE FROM sessoes_lembradas WHERE cliente_id = ?", [$id]);
    }

    // ----- Recuperação de senha (token guardado apenas como hash) ----------

    public function criarTokenRecuperacao(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $this->executar(
            "UPDATE clientes SET token_recuperacao = ?, token_expira_em = NOW() + INTERVAL 60 MINUTE WHERE id = ?",
            [hash('sha256', $token), $id]
        );
        return $token;
    }

    public function porTokenRecuperacao(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->um(
            "SELECT * FROM clientes WHERE token_recuperacao = ? AND token_expira_em > NOW() AND estado = 'activo' LIMIT 1",
            [hash('sha256', $token)]
        );
    }

    /** Números para o painel da conta. */
    public function resumo(int $id): array
    {
        return [
            'pedidos'    => (int) $this->valor("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ?", [$id]),
            'em_curso'   => (int) $this->valor("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ? AND estado IN ('pendente','pago','em_preparacao','enviado')", [$id]),
            'favoritos'  => (int) $this->valor("SELECT COUNT(*) FROM favoritos WHERE cliente_id = ?", [$id]),
            'enderecos'  => (int) $this->valor("SELECT COUNT(*) FROM enderecos WHERE cliente_id = ?", [$id]),
        ];
    }
}
