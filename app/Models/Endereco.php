<?php

namespace App\Models;

use Core\Model;

class Endereco extends Model
{
    protected string $tabela = 'enderecos';
    public const MAXIMO = 10;

    public function doCliente(int $clienteId): array
    {
        return $this->todos("SELECT * FROM enderecos WHERE cliente_id = ? ORDER BY principal DESC, id DESC", [$clienteId]);
    }

    /** Só devolve o endereço se pertencer ao cliente. */
    public function doClientePorId(int $clienteId, int $id): ?array
    {
        return $this->um("SELECT * FROM enderecos WHERE id = ? AND cliente_id = ?", [$id, $clienteId]);
    }

    public function principal(int $clienteId): ?array
    {
        return $this->um("SELECT * FROM enderecos WHERE cliente_id = ? ORDER BY principal DESC, id DESC LIMIT 1", [$clienteId]);
    }

    public function contar(int $clienteId): int
    {
        return (int) $this->valor("SELECT COUNT(*) FROM enderecos WHERE cliente_id = ?", [$clienteId]);
    }

    public function guardar(int $clienteId, array $d, ?int $id = null): int
    {
        $campos = [
            'nome_destinatario' => $d['nome_destinatario'],
            'telefone'          => $d['telefone'],
            'provincia'         => $d['provincia'],
            'municipio'         => $d['municipio'],
            'bairro'            => $d['bairro'],
            'rua'               => $d['rua'] ?: null,
            'referencia'        => $d['referencia'] ?: null,
        ];

        // O primeiro endereço é sempre o principal
        $tornarPrincipal = !empty($d['principal']) || $this->contar($clienteId) === 0;

        if ($id) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($campos)));
            $this->executar("UPDATE enderecos SET $sets WHERE id = ? AND cliente_id = ?", [...array_values($campos), $id, $clienteId]);
        } else {
            $id = $this->inserir($campos + ['cliente_id' => $clienteId]);
        }

        if ($tornarPrincipal) {
            $this->definirPrincipal($clienteId, $id);
        }
        return $id;
    }

    public function definirPrincipal(int $clienteId, int $id): void
    {
        $this->executar("UPDATE enderecos SET principal = (id = ?) WHERE cliente_id = ?", [$id, $clienteId]);
    }

    public function remover(int $clienteId, int $id): void
    {
        $eraPrincipal = (bool) $this->valor("SELECT principal FROM enderecos WHERE id = ? AND cliente_id = ?", [$id, $clienteId]);
        $this->executar("DELETE FROM enderecos WHERE id = ? AND cliente_id = ?", [$id, $clienteId]);
        if ($eraPrincipal && ($outro = $this->principal($clienteId))) {
            $this->definirPrincipal($clienteId, (int) $outro['id']);
        }
    }
}
