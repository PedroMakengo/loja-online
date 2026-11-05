<?php
// Router para o servidor embutido do PHP (só desenvolvimento local):
//   php -S localhost:8000 servidor-dev.php
// Em Apache/cPanel o .htaccess faz este papel — este ficheiro não é usado.
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($caminho !== '/' && is_file(__DIR__ . $caminho) && !preg_match('#^/(app|core|config|database|src|storage)/#', $caminho)) {
    return false;
}
// Igual ao Apache: o php -S põe SCRIPT_NAME = caminho pedido quando este tem extensão
// (ex.: /blog/feed.xml), o que faria a loja pensar que está numa subpasta.
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
