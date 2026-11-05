<?php
// =====================================================================
// core/AdminAuth.php — Sessão e permissões do painel administrativo.
// Sessão separada da do cliente (chave 'admin_id'); expira após
// 2 horas de inactividade.
//
// Papéis:
//   super        tudo, incluindo gerir administradores
//   gestor       tudo menos administradores
//   stock        produtos, categorias, marcas, stock
//   atendimento  pedidos, clientes, mensagens
// =====================================================================

namespace Core;

class AdminAuth
{
    private const INACTIVIDADE = 7200;

    public const PERMISSOES = [
        'painel'     => ['super', 'gestor', 'stock', 'atendimento'],
        'pedidos'    => ['super', 'gestor', 'atendimento'],
        'pagamentos' => ['super', 'gestor'],
        'clientes'   => ['super', 'gestor', 'atendimento'],
        'mensagens'  => ['super', 'gestor', 'atendimento'],
        'produtos'   => ['super', 'gestor', 'stock'],
        'catalogo'   => ['super', 'gestor', 'stock'],
        'marketing'  => ['super', 'gestor'],
        'relatorios' => ['super', 'gestor'],
        'admins'     => ['super'],
    ];

    public const PAPEIS = [
        'super'       => 'Administrador geral',
        'gestor'      => 'Gestor da loja',
        'stock'       => 'Gestão de stock',
        'atendimento' => 'Atendimento',
    ];

    private static ?array $admin = null;
    private static bool $carregado = false;

    public static function admin(): ?array
    {
        if (self::$carregado) {
            return self::$admin;
        }
        self::$carregado = true;

        $id = $_SESSION['admin_id'] ?? null;
        if (!$id) {
            return null;
        }
        if (time() - ($_SESSION['admin_actividade'] ?? 0) > self::INACTIVIDADE) {
            unset($_SESSION['admin_id'], $_SESSION['admin_actividade']);
            return null;
        }
        $_SESSION['admin_actividade'] = time();

        $st = Database::ligacao()->prepare("SELECT id, nome, email, papel, deve_mudar_senha FROM admins WHERE id = ? AND activo = 1");
        $st->execute([$id]);
        return self::$admin = $st->fetch() ?: null;
    }

    public static function id(): ?int
    {
        return isset(self::admin()['id']) ? (int) self::admin()['id'] : null;
    }

    public static function pode(string $area): bool
    {
        $a = self::admin();
        return $a && in_array($a['papel'], self::PERMISSOES[$area] ?? [], true);
    }

    /** Exige sessão de admin e permissão para a área. */
    public static function exigir(string $area = 'painel'): void
    {
        if (!self::admin()) {
            if (eAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                exit(json_encode(['ok' => false, 'mensagem' => 'A sessão terminou. Entre de novo.']));
            }
            header('Location: ' . url('/admin/entrar?voltar=' . urlencode(strtok($_SERVER['REQUEST_URI'] ?? '/admin', '#'))));
            exit;
        }
        // Senha inicial tem de ser trocada antes de usar o painel
        if (self::admin()['deve_mudar_senha'] && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/perfil') && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/sair')) {
            Flash::definir('erro', 'Por segurança, defina uma nova senha antes de continuar.');
            header('Location: ' . url('/admin/perfil'));
            exit;
        }
        if (!self::pode($area)) {
            http_response_code(403);
            Flash::definir('erro', 'Não tem permissão para aceder a esta área.');
            header('Location: ' . url('/admin'));
            exit;
        }
    }

    public static function entrar(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_actividade'] = time();
        self::$carregado = false;
        Database::ligacao()->prepare("UPDATE admins SET ultimo_login_em = NOW() WHERE id = ?")->execute([$id]);
    }

    public static function sair(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_actividade']);
        session_regenerate_id(true);
        self::$admin = null;
    }

    /** Auditoria. */
    public static function registar(string $accao, ?string $alvo = null, int|string|null $alvoId = null, ?string $detalhe = null): void
    {
        Database::ligacao()->prepare(
            "INSERT INTO admin_registos (admin_id, accao, alvo, alvo_id, detalhe, ip) VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([self::id(), $accao, $alvo, $alvoId !== null ? (string) $alvoId : null, $detalhe ? mb_substr($detalhe, 0, 500) : null, $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}
