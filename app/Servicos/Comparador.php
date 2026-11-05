<?php
// =====================================================================
// app/Servicos/Comparador.php — Lista de comparação na sessão
// (funciona sem conta). Máximo 4 produtos.
// =====================================================================

namespace App\Servicos;

use App\Models\Produto;
use Core\Database;

class Comparador
{
    public const MAXIMO = 4;

    public static function ids(): array
    {
        return array_values(array_map('intval', $_SESSION['comparar'] ?? []));
    }

    /** Devolve ['ok', 'activo', 'mensagem', 'total']. */
    public static function alternar(int $produtoId): array
    {
        $ids = self::ids();
        if (in_array($produtoId, $ids, true)) {
            $_SESSION['comparar'] = array_values(array_diff($ids, [$produtoId]));
            return ['ok' => true, 'activo' => false, 'mensagem' => 'Retirado da comparação.', 'total' => count($_SESSION['comparar'])];
        }
        if (count($ids) >= self::MAXIMO) {
            return ['ok' => false, 'activo' => false, 'mensagem' => 'Pode comparar até ' . self::MAXIMO . ' produtos. Retire um para adicionar outro.', 'total' => count($ids)];
        }
        $st = Database::ligacao()->prepare("SELECT categoria_id FROM produtos WHERE id = ? AND estado = 'activo'");
        $st->execute([$produtoId]);
        $categoria = $st->fetchColumn();
        if (!$categoria) {
            return ['ok' => false, 'activo' => false, 'mensagem' => 'Produto não disponível.', 'total' => count($ids)];
        }

        $aviso = '';
        if ($ids) {
            $st = Database::ligacao()->prepare("SELECT categoria_id FROM produtos WHERE id = ?");
            $st->execute([$ids[0]]);
            if ((int) $st->fetchColumn() !== (int) $categoria) {
                $aviso = ' Atenção: é de outra categoria, por isso as características podem não coincidir.';
            }
        }
        $ids[] = $produtoId;
        $_SESSION['comparar'] = $ids;
        return ['ok' => true, 'activo' => true, 'mensagem' => 'Adicionado à comparação.' . $aviso, 'total' => count($ids)];
    }

    public static function limpar(): void
    {
        unset($_SESSION['comparar']);
    }

    /** Produtos + tabela de características alinhada por [grupo][nome]. */
    public static function montar(): array
    {
        $ids = self::ids();
        $porId = array_column((new Produto())->porIds($ids), null, 'id');
        $produtos = array_values(array_filter(array_map(fn ($id) => $porId[$id] ?? null, $ids)));

        $linhas = [];
        if ($produtos) {
            $marcadores = implode(',', array_fill(0, count($produtos), '?'));
            $st = Database::ligacao()->prepare(
                "SELECT produto_id, grupo, nome, valor FROM produto_especificacoes WHERE produto_id IN ($marcadores) ORDER BY ordem, id"
            );
            $st->execute(array_column($produtos, 'id'));
            foreach ($st->fetchAll() as $s) {
                $linhas[$s['grupo']][$s['nome']][(int) $s['produto_id']] = $s['valor'];
            }
            // "Geral" no fim (é onde fica a garantia)
            if (isset($linhas['Geral'])) {
                $geral = $linhas['Geral']; unset($linhas['Geral']); $linhas['Geral'] = $geral;
            }
        }
        return ['produtos' => $produtos, 'linhas' => $linhas];
    }
}
