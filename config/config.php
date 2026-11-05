<?php
// =====================================================================
// config/config.php — Configuração global da Loja Virtual DIOTEKNO
// =====================================================================

declare(strict_types=1);

/** Lê uma variável de ambiente (Docker/Coolify); devolve $padrao se não existir. */
function env(string $nome, ?string $padrao = null): ?string
{
    $v = getenv($nome);
    if ($v === false) {
        $v = $_ENV[$nome] ?? $_SERVER[$nome] ?? null;
    }
    return ($v === null || $v === '') ? $padrao : (string) $v;
}

// Ambiente: 'dev' mostra erros; 'prod' esconde-os e regista em log.
// No Coolify/Docker vem da variável AMBIENTE (por omissão 'dev' no XAMPP local).
define('AMBIENTE', env('AMBIENTE', 'dev') === 'prod' ? 'prod' : 'dev');

// Botões "Simular pagamento" (driver simulado): sempre em dev; em prod só com PAGAMENTOS_SIMULADOS=1
// (útil para demonstrações antes de haver contrato com os operadores).
define('PAGAMENTOS_SIMULADOS', AMBIENTE === 'dev' || env('PAGAMENTOS_SIMULADOS') === '1');

// Emails: 'mail' usa mail() do servidor; 'log' grava em storage/logs/emails.log.
// Os contentores Docker não têm sendmail — usar 'log' até haver SMTP configurado.
define('EMAIL_DRIVER', env('EMAIL_DRIVER', AMBIENTE === 'dev' ? 'log' : 'mail'));

if (AMBIENTE === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');
}

date_default_timezone_set('Africa/Luanda');
mb_internal_encoding('UTF-8');

// ---------------------------------------------------------------------
// Identidade da loja
// ---------------------------------------------------------------------
define('NOME_SITE', 'DIOTEKNO');
define('SLOGAN_SITE', 'Tecnologia com garantia oficial, entregue em todo o país');
define('EMAIL_LOJA', env('EMAIL_LOJA', 'geral@diotekno.ao'));
define('TELEFONE_LOJA', '+244 923 000 000');
define('WHATSAPP_LOJA', '244923000000');          // só dígitos, com indicativo
define('MORADA_LOJA', 'Rua Rainha Ginga, Ingombota, Luanda');
define('NIF_LOJA', '5000000000');

// ---------------------------------------------------------------------
// Comércio
// ---------------------------------------------------------------------
define('MOEDA', 'Kz');
define('IVA_TAXA', 14);                 // % — preços apresentados já com IVA
define('PORTES_GRATIS_LUANDA', 150000); // Kz — acima deste valor, entrega grátis em Luanda
define('PRODUTOS_POR_PAGINA', 24);

// ---------------------------------------------------------------------
// URL base — detectado automaticamente para funcionar na raiz do
// domínio ou numa subpasta, sem precisar de /public nem RewriteBase.
// ---------------------------------------------------------------------
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('BASE_PATH', rtrim($scriptDir === '/' ? '' : $scriptDir, '/'));

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('URL_ORIGEM', ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('URL_BASE', URL_ORIGEM . BASE_PATH);

define('RAIZ', dirname(__DIR__));

// Versão dos assets — mudar após recompilar CSS/JS para limpar cache.
define('VERSAO_ASSETS', '1.8.0');

// ---------------------------------------------------------------------
// Sessão segura
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name('diotekno_sessao');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_PATH === '' ? '/' : BASE_PATH . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
