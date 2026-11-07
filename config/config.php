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

// Ficheiro .env na raiz do projecto (XAMPP / docker compose local). Fica fora do Git.
// Não substitui variáveis já definidas pelo servidor (Coolify tem sempre prioridade).
(function (string $ficheiro): void {
    if (!is_file($ficheiro) || !is_readable($ficheiro)) {
        return;
    }
    foreach (file($ficheiro, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
            continue;
        }
        [$nome, $valor] = array_map('trim', explode('=', $linha, 2));
        if (preg_match('/^[A-Z][A-Z0-9_]*$/', $nome) !== 1 || getenv($nome) !== false) {
            continue;
        }
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }
        putenv("$nome=$valor");
        $_ENV[$nome] = $valor;
    }
})(dirname(__DIR__) . '/.env');

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

// ---------------------------------------------------------------------
// Entrar com o Google (Google Cloud Console → APIs e serviços → Credenciais
// → ID do cliente OAuth "Aplicação Web"). Sem estas variáveis o botão não aparece.
// URI de redireccionamento autorizado: <URL do site>/entrar/google/retorno
// ---------------------------------------------------------------------
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', '')); // opcional: fixa o URI se o proxy mudar o host

// Versão dos assets — mudar após recompilar CSS/JS para limpar cache.
define('VERSAO_ASSETS', '1.9.2');

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
