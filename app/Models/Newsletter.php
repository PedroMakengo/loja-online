<?php

namespace App\Models;

use Core\Model;

class Newsletter extends Model
{
    protected string $tabela = 'newsletter';

    /** true se é novo, false se já existia. */
    public function subscrever(string $email, string $origem = 'site'): bool
    {
        $email = mb_strtolower(trim($email));
        $estado = $this->valor("SELECT estado FROM newsletter WHERE email = ?", [$email]);
        if ($estado === 'activo') {
            return false;
        }
        if ($estado === 'cancelado') {
            $this->executar("UPDATE newsletter SET estado = 'activo', cancelado_em = NULL, origem = ? WHERE email = ?", [$origem, $email]);
            return true;
        }
        return $this->executar("INSERT INTO newsletter (email, origem) VALUES (?, ?)", [$email, $origem]) > 0;
    }
}
