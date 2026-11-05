<?php
// =====================================================================
// cron/expirar-pagamentos.php — expira pagamentos fora de prazo,
// cancela pedidos abandonados e devolve o stock.
//
// cPanel > Tarefas Cron, a cada 10 minutos:
//   */10 * * * * /usr/local/bin/php /home/UTILIZADOR/public_html/cron/expirar-pagamentos.php
//
// Opcional: a mesma limpeza já corre sozinha quando alguém abre o
// checkout ou um pedido. O cron só garante que o stock volta a horas
// mesmo sem visitas.
// =====================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST']   = 'localhost';

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../core/autoload.php';

$total = App\Servicos\Pagamentos::expirarPendentes();
echo date('c') . " — $total registo(s) tratados\n";
