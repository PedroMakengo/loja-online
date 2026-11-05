<?php

namespace App\Models;

use Core\Model;

class Banner extends Model
{
    protected string $tabela = 'banners';

    /** Banners activos dentro do período, com dados vivos do produto associado. */
    public function activos(): array
    {
        return $this->todos(
            "SELECT b.*, p.nome AS produto_nome, p.preco AS produto_preco, p.preco_anterior AS produto_preco_anterior,
                    p.imagem AS produto_imagem, c.slug AS categoria_slug
               FROM banners b
          LEFT JOIN produtos p ON p.id = b.produto_id AND p.estado = 'activo'
          LEFT JOIN categorias c ON c.id = p.categoria_id
              WHERE b.activo = 1
                AND (b.inicio IS NULL OR b.inicio <= NOW())
                AND (b.fim IS NULL OR b.fim >= NOW())
              ORDER BY b.ordem"
        );
    }
}
