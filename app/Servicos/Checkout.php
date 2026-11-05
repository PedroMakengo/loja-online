<?php
// =====================================================================
// app/Servicos/Checkout.php — Cálculo do resumo e criação do pedido
//
// Regras de ouro:
//   * os preços vêm SEMPRE da base de dados, nunca do formulário;
//   * o stock é bloqueado (SELECT ... FOR UPDATE) e descontado dentro
//     da mesma transacção que cria o pedido — dois clientes não
//     compram a última unidade ao mesmo tempo;
//   * a chave de idempotência impede pedidos duplicados por duplo
//     clique ou por reenvio do formulário.
// =====================================================================

namespace App\Servicos;

use Core\Database;
use Core\Email;
use App\Models\Carrinho;
use App\Models\Endereco;
use RuntimeException;

class Checkout
{
    private static function restam(int $stock, string $nome): string
    {
        return ($stock === 1 ? "Só resta 1 unidade de $nome." : "Só restam $stock unidades de $nome.") . ' Ajuste a quantidade no carrinho.';
    }

    /** Monta tudo o que a página de checkout precisa. */
    public static function montar(int $clienteId, ?int $enderecoId, ?string $metodoEntrega, bool $usarPontos = false): array
    {
        $carrinho  = new Carrinho();
        $itens     = $carrinho->itens();
        $resumo    = $carrinho->resumo($itens);
        $enderecos = (new Endereco())->doCliente($clienteId);

        $endereco = null;
        foreach ($enderecos as $e) {
            if ($enderecoId ? (int) $e['id'] === $enderecoId : (int) $e['principal'] === 1) {
                $endereco = $e;
            }
        }
        $endereco ??= $enderecos[0] ?? null;

        // Sem morada só é possível levantar na loja
        $opcoes = Entrega::opcoes($endereco['provincia'] ?? '', (float) $resumo['subtotal']);
        if (!$endereco) {
            $opcoes = ['levantamento' => $opcoes['levantamento']];
        }
        $metodoEntrega = isset($opcoes[$metodoEntrega]) ? $metodoEntrega : array_key_first($opcoes);
        $entrega = $opcoes[$metodoEntrega];

        $problemas = [];
        foreach ($itens as $i) {
            if ((int) $i['stock'] < (int) $i['quantidade']) {
                $problemas[] = (int) $i['stock'] > 0
                    ? self::restam((int) $i['stock'], $i['nome'])
                    : "{$i['nome']} esgotou entretanto. Remova-o do carrinho para continuar.";
            }
        }

        // Pontos: máximo permitido nesta compra e desconto se o cliente os quiser usar
        $st = Database::ligacao()->prepare("SELECT pontos FROM clientes WHERE id = ?");
        $st->execute([$clienteId]);
        $saldo = (int) $st->fetchColumn();
        $pontosMax = Fidelidade::maximoResgatavel($saldo, (float) $resumo['subtotal']);
        $pontosUsar = $usarPontos ? $pontosMax : 0;
        $descontoPontos = min(Fidelidade::valorEmKz($pontosUsar), max(0, $resumo['total']));

        $total = $resumo['total'] - $descontoPontos + $entrega['preco'];

        return [
            'saldoPontos'    => $saldo,
            'pontosMax'      => $pontosMax,
            'pontosUsar'     => $pontosUsar,
            'descontoPontos' => $descontoPontos,
            'pontosGanhar'   => Fidelidade::pontosPorValor($total - $entrega['preco']),
            'itens'          => $itens,
            'resumo'         => $resumo,
            'enderecos'      => $enderecos,
            'endereco'       => $endereco,
            'opcoesEntrega'  => $opcoes,
            'metodoEntrega'  => $metodoEntrega,
            'entrega'        => $entrega,
            'total'          => $total,
            'ivaIncluido'    => round($total - $total / (1 + IVA_TAXA / 100), 2),
            'problemas'      => $problemas,
        ];
    }

    /**
     * Cria o pedido. $dados: endereco_id, metodo_entrega, metodo_pagamento,
     * telefone_pagamento, nif_factura, notas, chave.
     * Devolve o número do pedido. Lança RuntimeException com mensagem para o cliente.
     */
    public static function criarPedido(array $cliente, array $dados): string
    {
        $db = Database::ligacao();
        $carrinho = new Carrinho();
        $carrinhoId = $carrinho->idActual();
        if (!$carrinhoId) {
            throw new RuntimeException('O carrinho está vazio.');
        }

        // Pedido já criado com esta chave (duplo clique / reenvio)?
        $existente = $db->prepare("SELECT numero FROM pedidos WHERE chave_idempotencia = ? AND cliente_id = ?");
        $existente->execute([$dados['chave'], $cliente['id']]);
        if ($numero = $existente->fetchColumn()) {
            return $numero;
        }

        if (!isset(Pagamentos::disponiveis()[$dados['metodo_pagamento']])) {
            throw new RuntimeException('Escolha uma forma de pagamento.');
        }

        $db->beginTransaction();
        try {
            // 1. Itens do carrinho com bloqueio das linhas de produto
            $st = $db->prepare(
                "SELECT ci.quantidade, p.id, p.nome, p.sku, p.preco, p.stock, p.estado
                   FROM carrinho_itens ci JOIN produtos p ON p.id = ci.produto_id
                  WHERE ci.carrinho_id = ? ORDER BY p.id FOR UPDATE"
            );
            $st->execute([$carrinhoId]);
            $itens = $st->fetchAll();
            if (!$itens) {
                throw new RuntimeException('O carrinho está vazio.');
            }

            $subtotal = 0;
            foreach ($itens as $i) {
                if ($i['estado'] !== 'activo') {
                    throw new RuntimeException("{$i['nome']} deixou de estar à venda. Remova-o do carrinho.");
                }
                if ((int) $i['stock'] < (int) $i['quantidade']) {
                    throw new RuntimeException((int) $i['stock'] > 0
                        ? self::restam((int) $i['stock'], $i['nome'])
                        : "{$i['nome']} esgotou entretanto. Remova-o do carrinho.");
                }
                $subtotal += $i['preco'] * $i['quantidade'];
            }

            // 2. Cupão (revalidado e bloqueado)
            $desconto = 0; $cupaoId = null;
            $cp = $db->prepare(
                "SELECT cp.* FROM carrinhos c JOIN cupoes cp ON cp.id = c.cupao_id
                  WHERE c.id = ? AND cp.activo = 1
                    AND (cp.inicio IS NULL OR cp.inicio <= NOW()) AND (cp.fim IS NULL OR cp.fim >= NOW())
                    AND (cp.utilizacoes_max IS NULL OR cp.utilizacoes < cp.utilizacoes_max)
                  FOR UPDATE"
            );
            $cp->execute([$carrinhoId]);
            if (($cupao = $cp->fetch()) && $subtotal >= (float) $cupao['minimo_compra']) {
                $desconto = $cupao['tipo'] === 'percentual'
                    ? round($subtotal * (float) $cupao['valor'] / 100)
                    : min((float) $cupao['valor'], $subtotal);
                $cupaoId = (int) $cupao['id'];
            }

            // 3. Entrega
            $endereco = null;
            if ($dados['metodo_entrega'] !== 'levantamento') {
                $endereco = (new Endereco())->doClientePorId((int) $cliente['id'], (int) $dados['endereco_id']);
                if (!$endereco) {
                    throw new RuntimeException('Escolha a morada de entrega.');
                }
            }
            $opcoes = Entrega::opcoes($endereco['provincia'] ?? '', $subtotal);
            if (!$endereco) {
                $opcoes = ['levantamento' => $opcoes['levantamento']];
            }
            $entrega = $opcoes[$dados['metodo_entrega']] ?? throw new RuntimeException('Escolha como quer receber a encomenda.');

            // Pontos (saldo bloqueado até ao fim da transacção)
            $pontosUsados = 0; $descontoPontos = 0;
            if (!empty($dados['usar_pontos'])) {
                $st = $db->prepare("SELECT pontos FROM clientes WHERE id = ? FOR UPDATE");
                $st->execute([$cliente['id']]);
                $pontosUsados = Fidelidade::maximoResgatavel((int) $st->fetchColumn(), $subtotal);
                $descontoPontos = min(Fidelidade::valorEmKz($pontosUsados), $subtotal - $desconto);
            }

            $total = $subtotal - $desconto - $descontoPontos + $entrega['preco'];
            $iva   = round($total - $total / (1 + IVA_TAXA / 100), 2);
            $loja  = Pagamentos::config()['levantamento'];

            // 4. Pedido
            $db->prepare(
                "INSERT INTO pedidos
                    (numero, chave_idempotencia, cliente_id, cupao_id,
                     entrega_nome, entrega_telefone, entrega_provincia, entrega_municipio, entrega_bairro, entrega_rua, entrega_referencia,
                     metodo_entrega, prazo_entrega, subtotal, desconto, pontos_usados, desconto_pontos, portes, iva, total, metodo_pagamento, nif_factura, notas)
                 VALUES (?, ?, ?, ?,  ?, ?, ?, ?, ?, ?, ?,  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                'TMP-' . bin2hex(random_bytes(6)), $dados['chave'], $cliente['id'], $cupaoId,
                $endereco['nome_destinatario'] ?? $cliente['nome'],
                $endereco['telefone'] ?? $cliente['telefone'],
                $endereco['provincia'] ?? 'Luanda',
                $endereco['municipio'] ?? 'Ingombota',
                $endereco['bairro'] ?? 'Levantamento na loja',
                $endereco['rua'] ?? ($endereco ? null : $loja['morada']),
                $endereco['referencia'] ?? null,
                $dados['metodo_entrega'], $entrega['prazo'],
                $subtotal, $desconto, $pontosUsados, $descontoPontos, $entrega['preco'], $iva, $total,
                $dados['metodo_pagamento'], $dados['nif_factura'] ?: null, $dados['notas'] ?: null,
            ]);
            $pedidoId = (int) $db->lastInsertId();
            $numero   = sprintf('DTK-%s-%06d', date('Y'), $pedidoId);
            $db->prepare("UPDATE pedidos SET numero = ?, carrinho_recuperado = (SELECT recuperado_em IS NOT NULL FROM carrinhos WHERE id = ?) WHERE id = ?")
               ->execute([$numero, $carrinhoId, $pedidoId]);

            // 5. Itens (cópia do nome e preço) e reserva de stock
            $insItem = $db->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, nome, sku, preco_unitario, quantidade, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $updStock = $db->prepare("UPDATE produtos SET stock = stock - ?, vendas = vendas + ? WHERE id = ?");
            foreach ($itens as $i) {
                $insItem->execute([$pedidoId, $i['id'], $i['nome'], $i['sku'], $i['preco'], $i['quantidade'], $i['preco'] * $i['quantidade']]);
                $updStock->execute([$i['quantidade'], $i['quantidade'], $i['id']]);
            }

            if ($pontosUsados > 0) {
                Fidelidade::movimento($db, (int) $cliente['id'], $pedidoId, 'resgate', -$pontosUsados, 'Pontos usados no pedido ' . $numero);
            }

            if ($cupaoId) {
                $db->prepare("UPDATE cupoes SET utilizacoes = utilizacoes + 1 WHERE id = ?")->execute([$cupaoId]);
            }

            $db->prepare("INSERT INTO pedido_historico (pedido_id, estado, nota) VALUES (?, 'pendente', ?)")
               ->execute([$pedidoId, 'Pedido recebido. A aguardar pagamento por ' . Pagamentos::nome($dados['metodo_pagamento']) . '.']);

            // 6. Pagamento
            Pagamentos::iniciar($db, $pedidoId, $dados['metodo_pagamento'], (float) $total, $dados['telefone_pagamento']);

            // 7. O carrinho fica marcado como convertido (sai da recuperação de abandonados)
            $db->prepare("UPDATE carrinhos SET convertido = 1 WHERE id = ?")->execute([$carrinhoId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $carrinho->esquecerDispositivo();

        Email::enviar($cliente['email'], 'Recebemos o seu pedido ' . $numero,
            '<p>Olá ' . e(explode(' ', $cliente['nome'])[0]) . ',</p>'
            . '<p>Recebemos o pedido <strong>' . $numero . '</strong> no valor de <strong>' . kz($total) . '</strong>.</p>'
            . '<p>Forma de pagamento: ' . e(Pagamentos::nome($dados['metodo_pagamento'])) . '. As instruções estão na página do pedido:</p>'
            . '<p><a href="' . URL_BASE . '/conta/pedidos/' . $numero . '">Ver pedido e pagar</a></p>');

        return $numero;
    }
}
