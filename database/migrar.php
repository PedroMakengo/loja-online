<?php
// =====================================================================
// database/migrar.php — Aplica os ficheiros database/NNN_*.sql em falta.
//
//   php database/migrar.php
//
// Corre sozinho no arranque do contentor (docker/entrypoint.sh).
// Regista o que já foi aplicado na tabela `migracoes`, por isso pode
// correr em cada deploy sem repetir nada.
// Numa BD que já tinha a loja instalada à mão (sem `migracoes`), os
// ficheiros 001–010 são marcados como aplicados sem correr de novo.
// =====================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST']   = 'localhost';

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/db.php';

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NOME . ';charset=' . DB_CHARSET;

// Espera pela base de dados (no primeiro arranque o MySQL demora a ficar pronto)
$pdo = null;
for ($tentativa = 1; $tentativa <= 30; $tentativa++) {
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true, // necessário para ficheiros com várias instruções
        ]);
        break;
    } catch (PDOException $e) {
        fwrite(STDERR, "[migrar] BD indisponível ($tentativa/30): {$e->getMessage()}\n");
        sleep(3);
    }
}
if (!$pdo) {
    fwrite(STDERR, "[migrar] Não foi possível ligar à base de dados.\n");
    exit(1);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS migracoes (
    ficheiro    VARCHAR(190) PRIMARY KEY,
    aplicado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$ficheiros = glob(__DIR__ . '/[0-9][0-9][0-9]_*.sql');
sort($ficheiros);

$aplicados = $pdo->query("SELECT ficheiro FROM migracoes")->fetchAll(PDO::FETCH_COLUMN);

// Instalação antiga feita à mão: as tabelas existem mas não há registo.
if (!$aplicados && $pdo->query("SHOW TABLES LIKE 'produtos'")->fetch()) {
    $base = array_filter($ficheiros, fn ($f) => basename($f) <= '010_~');
    $st = $pdo->prepare("INSERT IGNORE INTO migracoes (ficheiro) VALUES (?)");
    foreach ($base as $f) {
        $st->execute([basename($f)]);
    }
    $aplicados = array_map('basename', $base);
    echo "[migrar] BD existente — 001 a 010 marcados como aplicados.\n";
}

$novos = 0;
foreach ($ficheiros as $f) {
    $nome = basename($f);
    if (in_array($nome, $aplicados, true)) {
        continue;
    }
    echo "[migrar] A aplicar $nome ... ";
    try {
        $st = $pdo->query(file_get_contents($f));
        // Percorre os resultados de todas as instruções para que um erro a meio seja detectado
        do {
            if ($st->columnCount() > 0) {
                $st->fetchAll();
            }
        } while ($st->nextRowset());
        $st->closeCursor();
    } catch (PDOException $e) {
        echo "ERRO\n";
        fwrite(STDERR, "[migrar] $nome: {$e->getMessage()}\n");
        exit(1);
    }
    $pdo->prepare("INSERT INTO migracoes (ficheiro) VALUES (?)")->execute([$nome]);
    echo "ok\n";
    $novos++;
}

echo "[migrar] Concluído — $novos ficheiro(s) aplicado(s).\n";
