<?php
// =====================================================================
// core/Auth.php — Sessão do cliente da loja
//  * session_regenerate_id no login (evita fixação de sessão)
//  * "Lembrar-me" com seletor/validador (o token nunca é guardado em claro)
//  * limite de tentativas de login por IP e por email
// =====================================================================

namespace Core;

class Auth
{
    private const COOKIE_LEMBRAR = 'dtk_lembrar';
    private const MAX_TENTATIVAS = 6;      // por janela
    private const JANELA_MINUTOS = 15;

    private static ?array $cliente = null;
    private static bool $carregado = false;

    public static function cliente(): ?array
    {
        if (self::$carregado) {
            return self::$cliente;
        }
        self::$carregado = true;

        $id = $_SESSION['cliente_id'] ?? null;
        if (!$id) {
            $id = self::restaurarLembrado();
        }
        if ($id) {
            $c = Database::ligacao()->prepare("SELECT id, nome, email, telefone, nif, pontos, aceita_marketing, recebe_lembretes, google_id, avatar_url, senha_definida, criado_em FROM clientes WHERE id = ? AND estado = 'activo'");
            $c->execute([$id]);
            self::$cliente = $c->fetch() ?: null;
            if (!self::$cliente) {
                unset($_SESSION['cliente_id']);
            }
        }
        return self::$cliente;
    }

    public static function id(): ?int
    {
        return isset(self::cliente()['id']) ? (int) self::cliente()['id'] : null;
    }

    public static function autenticado(): bool
    {
        return self::cliente() !== null;
    }

    /** Primeiro nome, para o cabeçalho. */
    public static function primeiroNome(): string
    {
        return explode(' ', trim(self::cliente()['nome'] ?? ''))[0];
    }

    public static function entrar(int $clienteId, bool $lembrar = false): void
    {
        session_regenerate_id(true);
        $_SESSION['cliente_id'] = $clienteId;
        self::$carregado = false;

        Database::ligacao()->prepare("UPDATE clientes SET ultimo_login_em = NOW() WHERE id = ?")->execute([$clienteId]);

        if ($lembrar) {
            self::criarLembrado($clienteId);
        }
    }

    public static function sair(): void
    {
        $cookie = $_COOKIE[self::COOKIE_LEMBRAR] ?? '';
        if (str_contains($cookie, ':')) {
            [$seletor] = explode(':', $cookie, 2);
            Database::ligacao()->prepare("DELETE FROM sessoes_lembradas WHERE seletor = ?")->execute([$seletor]);
        }
        self::definirCookie(self::COOKIE_LEMBRAR, '', time() - 3600);

        $_SESSION = [];
        session_regenerate_id(true);
        self::$cliente = null;
        self::$carregado = true;
    }

    /** Redirecciona para o login se não houver sessão. */
    public static function exigir(): void
    {
        if (self::autenticado()) {
            return;
        }
        if (eAjax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            exit(json_encode(['ok' => false, 'mensagem' => 'Entre na sua conta para continuar.', 'entrar' => url('/entrar')], JSON_UNESCAPED_UNICODE));
        }
        $voltar = strtok($_SERVER['REQUEST_URI'] ?? '/', '#');
        header('Location: ' . url('/entrar?voltar=' . urlencode($voltar)));
        exit;
    }

    // ----- Limite de tentativas ---------------------------------------------

    public static function bloqueado(string $email): bool
    {
        $db = Database::ligacao();
        $db->exec("DELETE FROM tentativas_login WHERE criado_em < NOW() - INTERVAL 1 DAY");
        $st = $db->prepare(
            "SELECT
                SUM(ip = ?)    AS por_ip,
                SUM(email = ?) AS por_email
               FROM tentativas_login
              WHERE criado_em > NOW() - INTERVAL " . self::JANELA_MINUTOS . " MINUTE"
        );
        $st->execute([self::ip(), mb_strtolower($email)]);
        $r = $st->fetch();
        return (int) $r['por_ip'] >= self::MAX_TENTATIVAS * 3 || (int) $r['por_email'] >= self::MAX_TENTATIVAS;
    }

    public static function registarFalha(string $email): void
    {
        Database::ligacao()->prepare("INSERT INTO tentativas_login (ip, email) VALUES (?, ?)")->execute([self::ip(), mb_strtolower($email)]);
    }

    public static function limparFalhas(string $email): void
    {
        Database::ligacao()->prepare("DELETE FROM tentativas_login WHERE email = ?")->execute([mb_strtolower($email)]);
    }

    public static function minutosBloqueio(): int
    {
        return self::JANELA_MINUTOS;
    }

    // ----- Lembrar-me ---------------------------------------------------------

    private static function criarLembrado(int $clienteId): void
    {
        $seletor   = bin2hex(random_bytes(12));
        $validador = bin2hex(random_bytes(32));
        Database::ligacao()->prepare(
            "INSERT INTO sessoes_lembradas (cliente_id, seletor, validador_hash, expira_em) VALUES (?, ?, ?, NOW() + INTERVAL 30 DAY)"
        )->execute([$clienteId, $seletor, hash('sha256', $validador)]);
        self::definirCookie(self::COOKIE_LEMBRAR, $seletor . ':' . $validador, time() + 60 * 60 * 24 * 30);
    }

    private static function restaurarLembrado(): ?int
    {
        $cookie = $_COOKIE[self::COOKIE_LEMBRAR] ?? '';
        if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) {
            return null;
        }
        $db = Database::ligacao();
        $st = $db->prepare("SELECT * FROM sessoes_lembradas WHERE seletor = ? AND expira_em > NOW()");
        $st->execute([$m[1]]);
        $linha = $st->fetch();

        if (!$linha || !hash_equals($linha['validador_hash'], hash('sha256', $m[2]))) {
            self::definirCookie(self::COOKIE_LEMBRAR, '', time() - 3600);
            return null;
        }

        // Rotação: o token usado é substituído por um novo
        $db->prepare("DELETE FROM sessoes_lembradas WHERE id = ?")->execute([$linha['id']]);
        session_regenerate_id(true);
        $_SESSION['cliente_id'] = (int) $linha['cliente_id'];
        self::criarLembrado((int) $linha['cliente_id']);
        return (int) $linha['cliente_id'];
    }

    private static function definirCookie(string $nome, string $valor, int $expira): void
    {
        setcookie($nome, $valor, [
            'expires'  => $expira,
            'path'     => BASE_PATH === '' ? '/' : BASE_PATH . '/',
            'secure'   => str_starts_with(URL_ORIGEM, 'https'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function ip(): string
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
    }
}
