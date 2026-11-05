<?php
// =====================================================================
// core/Flash.php — Mensagens de uma só leitura e valores antigos de
// formulário (para repor campos depois de um erro de validação)
// =====================================================================

namespace Core;

class Flash
{
    public static function definir(string $tipo, string $mensagem): void
    {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    public static function ler(): array
    {
        $m = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $m;
    }

    /** Guarda o input (sem senhas) e os erros por campo para o próximo pedido. */
    public static function guardarFormulario(array $erros, array $input): void
    {
        unset($input['senha'], $input['senha_confirmacao'], $input['senha_actual'], $input['_csrf']);
        $_SESSION['_erros'] = $erros;
        $_SESSION['_antigo'] = $input;
    }

    public static function erros(): array
    {
        static $e = null;
        if ($e === null) { $e = $_SESSION['_erros'] ?? []; unset($_SESSION['_erros']); }
        return $e;
    }

    public static function antigo(): array
    {
        static $a = null;
        if ($a === null) { $a = $_SESSION['_antigo'] ?? []; unset($_SESSION['_antigo']); }
        return $a;
    }
}
