<?php

namespace App\Models;

use Core\Model;

class Categoria extends Model
{
    protected string $tabela = 'categorias';

    /** Categorias activas com contagem de produtos (cache por pedido). */
    public function todasActivas(): array
    {
        static $cache = null;
        return $cache ??= $this->todos(
            "SELECT c.*, COUNT(p.id) AS total_produtos
               FROM categorias c
               LEFT JOIN produtos p ON p.categoria_id = c.id AND p.estado = 'activo'
              WHERE c.activo = 1
              GROUP BY c.id
              ORDER BY c.ordem, c.nome"
        );
    }

    public function destaque(): array
    {
        return array_values(array_filter($this->todasActivas(), fn ($c) => (int) $c['destaque'] === 1));
    }

    public function porSlug(string $slug): ?array
    {
        return $this->um("SELECT * FROM categorias WHERE slug = ? AND activo = 1 LIMIT 1", [$slug]);
    }
}
