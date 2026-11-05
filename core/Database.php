<?php
// =====================================================================
// core/Database.php — Ligação PDO única (singleton)
// =====================================================================

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function ligacao(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NOME . ';charset=' . DB_CHARSET;
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // NOW() do MySQL no mesmo fuso que o PHP (Africa/Luanda, UTC+1 sem hora de verão).
                // Sem isto, prazos de pagamento e contagens ficam desfasados em servidores UTC.
                self::$pdo->exec("SET time_zone = '" . date('P') . "'");
            } catch (PDOException $e) {
                http_response_code(500);
                exit(AMBIENTE === 'dev'
                    ? 'Erro de ligação à base de dados: ' . $e->getMessage()
                    : 'Serviço temporariamente indisponível.');
            }
        }
        return self::$pdo;
    }
}
