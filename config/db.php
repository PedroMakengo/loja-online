<?php
// =====================================================================
// config/db.php — Credenciais da base de dados (MySQL/MariaDB)
// No Coolify/Docker vêm das variáveis de ambiente DB_*; os valores
// por omissão servem o XAMPP local.
// =====================================================================

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NOME', env('DB_NOME', 'diotekno_loja'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'R@@t_#2026'));
define('DB_CHARSET', 'utf8mb4');
