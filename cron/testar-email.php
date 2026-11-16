<?php
// =====================================================================
// cron/testar-email.php — envia um email de teste com a configuração
// actual (EMAIL_DRIVER / SMTP_*) e mostra o erro exacto se falhar.
//
//   php cron/testar-email.php destino@exemplo.com
//   (Docker/Coolify: docker exec <contentor> php cron/testar-email.php destino@exemplo.com)
// =====================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST']   = 'localhost';

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../core/autoload.php';

$para = $argv[1] ?? '';
if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php cron/testar-email.php destino@exemplo.com\n");
    exit(1);
}

echo "Driver:     " . EMAIL_DRIVER . "\n";
if (EMAIL_DRIVER === 'smtp') {
    echo "Servidor:   " . SMTP_HOST . ':' . SMTP_PORTA . ' (' . SMTP_SEGURANCA . ")\n";
    echo "Utilizador: " . SMTP_UTILIZADOR . "\n";
    echo "Senha:      " . (SMTP_SENHA !== '' ? str_repeat('*', strlen(SMTP_SENHA)) : '(vazia!)') . "\n";
}
echo "Remetente:  " . EMAIL_REMETENTE . "\n";
echo "Avisos:     " . EMAIL_NOTIFICACOES . "\n\n";

$ok = Core\Email::enviar($para, 'Teste de email — ' . NOME_SITE,
    '<p>Olá,</p><p>Este é um email de teste da loja <strong>' . NOME_SITE . '</strong>, enviado em ' . date('d/m/Y H:i') . '.</p>'
    . '<p>Se o recebeu, as notificações de pedidos estão a funcionar. Acentuação: ção, ã, é, ô.</p>');

if ($ok) {
    echo EMAIL_DRIVER === 'log' ? "OK — gravado em storage/logs/emails.log (driver 'log', nada foi enviado).\n" : "OK — enviado para $para. Veja a caixa de entrada (e o spam).\n";
    exit(0);
}
$erros = @file(RAIZ . '/storage/logs/emails-erros.log', FILE_IGNORE_NEW_LINES) ?: [];
fwrite(STDERR, "FALHOU — " . (end($erros) ?: 'sem detalhe') . "\n");
fwrite(STDERR, "Gmail: 535 = senha errada (use uma senha de app, não a senha normal); 'Sem ligação' = porta bloqueada pelo servidor.\n");
exit(1);
