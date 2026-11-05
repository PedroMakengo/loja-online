<?php

namespace App\Models;

use Core\Model;

class Marca extends Model
{
    protected string $tabela = 'marcas';

    public function todasActivas(): array
    {
        return $this->todos(
            "SELECT m.*, COUNT(p.id) AS total_produtos
               FROM marcas m
               LEFT JOIN produtos p ON p.marca_id = m.id AND p.estado = 'activo'
              WHERE m.activo = 1
              GROUP BY m.id
              ORDER BY m.destaque DESC, m.nome"
        );
    }

    public function destaque(): array
    {
        return $this->todos("SELECT * FROM marcas WHERE activo = 1 AND destaque = 1 ORDER BY nome");
    }

    public function porSlug(string $slug): ?array
    {
        return $this->um("SELECT * FROM marcas WHERE slug = ? AND activo = 1 LIMIT 1", [$slug]);
    }
}
