<?php
// =====================================================================
// app/Models/Avaliacao.php — Avaliações de clientes.
// Só quem recebeu o produto (pedido entregue) pode avaliar, uma vez por
// produto. Ficam pendentes até serem aprovadas no painel.
// =====================================================================

namespace App\Models;

use Core\Model;

class Avaliacao extends Model
{
    protected string $tabela = 'avaliacoes';

    /**
     * Devolve o id do pedido entregue que dá direito a avaliar, ou null.
     * 'ja_avaliou' => true se já existe uma avaliação deste cliente.
     */
    public function elegibilidade(int $clienteId, int $produtoId): array
    {
        $existente = $this->um("SELECT id, estado, nota FROM avaliacoes WHERE cliente_id = ? AND produto_id = ?", [$clienteId, $produtoId]);
        $pedido = $this->valor(
            "SELECT p.id FROM pedidos p JOIN pedido_itens pi ON pi.pedido_id = p.id
              WHERE p.cliente_id = ? AND pi.produto_id = ? AND p.estado = 'entregue'
              ORDER BY p.criado_em DESC LIMIT 1",
            [$clienteId, $produtoId]
        );
        return ['pedido_id' => $pedido ? (int) $pedido : null, 'existente' => $existente];
    }

    /** Produtos de pedidos entregues que o cliente ainda não avaliou. */
    public function porAvaliar(int $clienteId): array
    {
        return $this->todos(
            "SELECT DISTINCT pr.id, pr.nome, pr.slug, pr.imagem, c.slug AS categoria_slug, MAX(p.criado_em) AS comprado_em
               FROM pedidos p
               JOIN pedido_itens pi ON pi.pedido_id = p.id
               JOIN produtos pr ON pr.id = pi.produto_id
               JOIN categorias c ON c.id = pr.categoria_id
          LEFT JOIN avaliacoes a ON a.produto_id = pr.id AND a.cliente_id = p.cliente_id
              WHERE p.cliente_id = ? AND p.estado = 'entregue' AND a.id IS NULL
              GROUP BY pr.id ORDER BY comprado_em DESC",
            [$clienteId]
        );
    }

    public function criar(int $clienteId, int $produtoId, int $pedidoId, int $nota, ?string $titulo, ?string $comentario): int
    {
        return $this->inserir([
            'produto_id'        => $produtoId,
            'cliente_id'        => $clienteId,
            'pedido_id'         => $pedidoId,
            'nota'              => $nota,
            'titulo'            => $titulo ?: null,
            'comentario'        => $comentario ?: null,
            'compra_verificada' => 1,
            'estado'            => 'pendente',
        ]);
    }

    /** Avaliações aprovadas, com o nome do cliente abreviado ("Pedro M."). */
    public function aprovadas(int $produtoId, string $ordem = 'recentes', int $limite = 10, int $offset = 0): array
    {
        $ord = match ($ordem) {
            'melhores' => 'a.nota DESC, a.criado_em DESC',
            'piores'   => 'a.nota ASC, a.criado_em DESC',
            default    => 'a.criado_em DESC',
        };
        return $this->todos(
            "SELECT a.id, a.nota, a.titulo, a.comentario, a.compra_verificada, a.resposta_loja, a.respondido_em, a.criado_em, c.nome
               FROM avaliacoes a JOIN clientes c ON c.id = a.cliente_id
              WHERE a.produto_id = ? AND a.estado = 'aprovada'
              ORDER BY $ord LIMIT $limite OFFSET $offset",
            [$produtoId]
        );
    }

    /** [5 => n, 4 => n, ..., 1 => n] */
    public function distribuicao(int $produtoId): array
    {
        $linhas = $this->todos("SELECT nota, COUNT(*) n FROM avaliacoes WHERE produto_id = ? AND estado = 'aprovada' GROUP BY nota", [$produtoId]);
        $d = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($linhas as $l) {
            $d[(int) $l['nota']] = (int) $l['n'];
        }
        return $d;
    }

    /** Recalcula a média e o total guardados no produto. */
    public function recalcular(int $produtoId): void
    {
        $this->executar(
            "UPDATE produtos SET
                avaliacao_media  = COALESCE((SELECT ROUND(AVG(nota), 2) FROM avaliacoes WHERE produto_id = ? AND estado = 'aprovada'), 0),
                total_avaliacoes = (SELECT COUNT(*) FROM avaliacoes WHERE produto_id = ? AND estado = 'aprovada')
              WHERE id = ?",
            [$produtoId, $produtoId, $produtoId]
        );
    }

    /** "Pedro Miguel Kiami" -> "Pedro K." */
    public static function nomePublico(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome));
        $primeiro = $partes[0] ?? 'Cliente';
        $ultimo = count($partes) > 1 ? end($partes) : '';
        return $primeiro . ($ultimo ? ' ' . mb_strtoupper(mb_substr($ultimo, 0, 1)) . '.' : '');
    }
}
