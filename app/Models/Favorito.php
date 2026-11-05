<?php

namespace App\Models;

use Core\Model;

class Favorito extends Model
{
    protected string $tabela = 'favoritos';

    /** IDs dos produtos favoritos (para marcar os corações nas listagens). */
    public function ids(int $clienteId): array
    {
        static $cache = [];
        return $cache[$clienteId] ??= array_map('intval', array_column(
            $this->todos("SELECT produto_id FROM favoritos WHERE cliente_id = ?", [$clienteId]),
            'produto_id'
        ));
    }

    /** Alterna. Devolve true se ficou favorito. */
    public function alternar(int $clienteId, int $produtoId): bool
    {
        if ($this->executar("DELETE FROM favoritos WHERE cliente_id = ? AND produto_id = ?", [$clienteId, $produtoId]) > 0) {
            return false;
        }
        $existe = $this->valor("SELECT 1 FROM produtos WHERE id = ? AND estado = 'activo'", [$produtoId]);
        if (!$existe) {
            return false;
        }
        $this->executar("INSERT IGNORE INTO favoritos (cliente_id, produto_id) VALUES (?, ?)", [$clienteId, $produtoId]);
        return true;
    }

    public function produtos(int $clienteId): array
    {
        $ids = array_column(
            $this->todos("SELECT produto_id FROM favoritos WHERE cliente_id = ? ORDER BY criado_em DESC", [$clienteId]),
            'produto_id'
        );
        if (!$ids) {
            return [];
        }
        // Mantém a ordem "mais recente primeiro"
        $porId = array_column((new Produto())->porIds($ids), null, 'id');
        return array_values(array_filter(array_map(fn ($id) => $porId[$id] ?? null, $ids)));
    }
}
