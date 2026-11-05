<?php
// =====================================================================
// cron/marketing.php — lembretes de carrinho abandonado + lotes da
// newsletter. cPanel > Tarefas Cron, a cada 15 minutos:
//   */15 * * * * /usr/local/bin/php /home/UTILIZADOR/public_html/cron/marketing.php
// =====================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST']   = $argv[1] ?? 'localhost'; // passar o domínio: php cron/marketing.php diotekno.ao
$_SERVER['HTTPS']       = 'on';

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../core/autoload.php';

$l = App\Servicos\Lembretes::processar();
$n = App\Servicos\Newsletter::processarLote();
echo date('c') . " — lembretes: {$l['primeiro']} primeiros, {$l['segundo']} segundos, {$l['ignorados']} ignorados; newsletter: $n enviados\n";
