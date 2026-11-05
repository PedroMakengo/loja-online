<?php
// =====================================================================
// core/Model.php — Modelo base com helpers PDO (prepared statements)
// =====================================================================

namespace Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $tabela = '';

    public function __construct()
    {
        $this->db = Database::ligacao();
    }

    protected function todos(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function um(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $linha = $stmt->fetch();
        return $linha === false ? null : $linha;
    }

    protected function valor(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    protected function executar(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function porId(int $id): ?array
    {
        return $this->um("SELECT * FROM {$this->tabela} WHERE id = ? LIMIT 1", [$id]);
    }

    public function inserir(array $dados): int
    {
        $colunas = array_keys($dados);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tabela,
            implode(', ', $colunas),
            implode(', ', array_fill(0, count($colunas), '?'))
        );
        $this->executar($sql, array_values($dados));
        return (int) $this->db->lastInsertId();
    }
}
