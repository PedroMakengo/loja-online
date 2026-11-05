<?php
// =====================================================================
// core/Csrf.php — Protecção CSRF em todos os POST (form e AJAX)
// =====================================================================

namespace Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function verificar(): void
    {
        $enviado = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($enviado) || !hash_equals(self::token(), $enviado)) {
            http_response_code(419);
            if (eAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                exit(json_encode(['ok' => false, 'mensagem' => 'A sessão expirou. Actualize a página e tente de novo.']));
            }
            exit('A sessão expirou. Volte atrás e actualize a página.');
        }
    }
}
