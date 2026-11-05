<?php

namespace App\Models;

use Core\Model;

class ZonaEntrega extends Model
{
    protected string $tabela = 'zonas_entrega';

    public function todas(): array
    {
        return $this->todos("SELECT * FROM zonas_entrega WHERE activo = 1 ORDER BY provincia = 'Luanda' DESC, expressa, provincia");
    }

    /** Opções de entrega para uma província (normal + expressa, se houver). */
    public function paraProvincia(string $provincia): array
    {
        return $this->todos(
            "SELECT * FROM zonas_entrega WHERE activo = 1 AND provincia = ? ORDER BY expressa, preco",
            [$provincia]
        );
    }

    public function provincias(): array
    {
        return array_column($this->todos("SELECT DISTINCT provincia FROM zonas_entrega WHERE activo = 1 ORDER BY provincia = 'Luanda' DESC, provincia"), 'provincia');
    }
}
