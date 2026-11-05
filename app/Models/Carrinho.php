<?php
// =====================================================================
// app/Models/Carrinho.php — Carrinho persistente em base de dados.
// O visitante é identificado por um cookie (token). Ao fazer login,
// o carrinho é associado ao cliente (módulo Conta). Persistir na BD
// permite a recuperação de carrinho abandonado.
// =====================================================================

namespace App\Models;

use Core\Model;

class Carrinho extends Model
{
    protected string $tabela = 'carrinhos';
    private const COOKIE = 'dtk_carrinho';
    public const QTD_MAX = 10;

    private static ?int $idActual = null;

    /** ID do carrinho do visitante; cria só quando $criar = true. */
    public function idActual(bool $criar = false): ?int
    {
        if (self::$idActual !== null) {
            return self::$idActual;
        }

        $clienteId = \Core\Auth::id();

        $token = $_COOKIE[self::COOKIE] ?? '';
        if (preg_match('/^[a-f0-9]{40}$/', $token)) {
            // Um carrinho de outro cliente nunca é reutilizado (ex.: computador partilhado)
            $id = $this->valor(
                "SELECT id FROM carrinhos WHERE token = ? AND convertido = 0 AND (cliente_id IS NULL OR cliente_id = ?)",
                [$token, $clienteId ?? 0]
            );
            if ($id) {
                return self::$idActual = (int) $id;
            }
        }

        // Cliente com sessão noutro dispositivo: recupera o carrinho da conta
        if ($clienteId) {
            $c = $this->um("SELECT id, token FROM carrinhos WHERE cliente_id = ? AND convertido = 0 ORDER BY actualizado_em DESC LIMIT 1", [$clienteId]);
            if ($c) {
                $this->definirCookie($c['token']);
                return self::$idActual = (int) $c['id'];
            }
        }

        if (!$criar) {
            return null;
        }

        $token = bin2hex(random_bytes(20));
        $id = $this->inserir(['token' => $token, 'cliente_id' => $clienteId]);
        $this->definirCookie($token);
        return self::$idActual = $id;
    }

    /**
     * Chamado no login: junta o carrinho do visitante com o carrinho
     * que o cliente já tinha na conta (quantidades somadas, com limites).
     */
    public function associarCliente(int $clienteId): void
    {
        self::$idActual = null;
        $token = $_COOKIE[self::COOKIE] ?? '';
        $visitante = preg_match('/^[a-f0-9]{40}$/', $token)
            ? $this->um("SELECT id FROM carrinhos WHERE token = ? AND convertido = 0 AND cliente_id IS NULL", [$token])
            : null;
        $daConta = $this->um("SELECT id, token FROM carrinhos WHERE cliente_id = ? AND convertido = 0 ORDER BY actualizado_em DESC LIMIT 1", [$clienteId]);

        if ($visitante && !$daConta) {
            $this->executar("UPDATE carrinhos SET cliente_id = ? WHERE id = ?", [$clienteId, $visitante['id']]);
            return;
        }

        if ($visitante && $daConta) {
            $this->executar(
                "INSERT INTO carrinho_itens (carrinho_id, produto_id, quantidade)
                 SELECT ?, v.produto_id, v.quantidade FROM carrinho_itens v WHERE v.carrinho_id = ?
                 ON DUPLICATE KEY UPDATE quantidade = LEAST(carrinho_itens.quantidade + VALUES(quantidade), " . self::QTD_MAX . ")",
                [$daConta['id'], $visitante['id']]
            );
            $this->executar("DELETE FROM carrinhos WHERE id = ?", [$visitante['id']]);
            $this->tocar((int) $daConta['id']);
        }

        if ($daConta) {
            $this->definirCookie($daConta['token']);
            $_COOKIE[self::COOKIE] = $daConta['token'];
        }
    }

    /** Ao sair: o carrinho fica na conta e este dispositivo começa vazio. */
    public function esquecerDispositivo(): void
    {
        self::$idActual = null;
        $this->definirCookie('', time() - 3600);
    }

    private function definirCookie(string $token, ?int $expira = null): void
    {
        setcookie(self::COOKIE, $token, [
            'expires'  => $expira ?? time() + 60 * 60 * 24 * 30,
            'path'     => BASE_PATH === '' ? '/' : BASE_PATH . '/',
            'secure'   => str_starts_with(URL_ORIGEM, 'https'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public function itens(): array
    {
        $id = $this->idActual();
        if (!$id) {
            return [];
        }
        return $this->todos(
            "SELECT ci.quantidade, p.id, p.nome, p.slug, p.sku, p.preco, p.preco_anterior, p.stock, p.imagem,
                    c.slug AS categoria_slug, m.nome AS marca_nome
               FROM carrinho_itens ci
               JOIN produtos p ON p.id = ci.produto_id AND p.estado = 'activo'
               JOIN categorias c ON c.id = p.categoria_id
          LEFT JOIN marcas m ON m.id = p.marca_id
              WHERE ci.carrinho_id = ?
              ORDER BY ci.adicionado_em DESC",
            [$id]
        );
    }

    /** Adiciona (soma à quantidade existente). Devolve mensagem de erro ou null. */
    public function adicionar(int $produtoId, int $qtd = 1): ?string
    {
        $produto = $this->um("SELECT id, stock FROM produtos WHERE id = ? AND estado = 'activo'", [$produtoId]);
        if (!$produto) {
            return 'Este produto já não está disponível.';
        }
        if ((int) $produto['stock'] < 1) {
            return 'Produto esgotado. Fale connosco no WhatsApp para saber quando volta.';
        }

        $id = $this->idActual(true);
        $actual = (int) $this->valor("SELECT quantidade FROM carrinho_itens WHERE carrinho_id = ? AND produto_id = ?", [$id, $produtoId]);
        $nova = min($actual + max(1, $qtd), (int) $produto['stock'], self::QTD_MAX);

        $this->executar(
            "INSERT INTO carrinho_itens (carrinho_id, produto_id, quantidade) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade)",
            [$id, $produtoId, $nova]
        );
        $this->tocar($id);

        return $nova === $actual ? 'Atingiu a quantidade máxima disponível para este produto.' : null;
    }

    public function definirQuantidade(int $produtoId, int $qtd): void
    {
        $id = $this->idActual();
        if (!$id) {
            return;
        }
        if ($qtd < 1) {
            $this->remover($produtoId);
            return;
        }
        $stock = (int) $this->valor("SELECT stock FROM produtos WHERE id = ?", [$produtoId]);
        $qtd = min($qtd, max(1, $stock), self::QTD_MAX);
        $this->executar("UPDATE carrinho_itens SET quantidade = ? WHERE carrinho_id = ? AND produto_id = ?", [$qtd, $id, $produtoId]);
        $this->tocar($id);
    }

    public function remover(int $produtoId): void
    {
        $id = $this->idActual();
        if ($id) {
            $this->executar("DELETE FROM carrinho_itens WHERE carrinho_id = ? AND produto_id = ?", [$id, $produtoId]);
            $this->tocar($id);
        }
    }

    /** Totais do carrinho. Os preços já incluem IVA. */
    public function resumo(?array $itens = null): array
    {
        $itens ??= $this->itens();
        $subtotal = 0; $poupanca = 0; $qtd = 0;

        foreach ($itens as $i) {
            $q = (int) $i['quantidade'];
            $subtotal += $i['preco'] * $q;
            if ($i['preco_anterior'] > $i['preco']) {
                $poupanca += ($i['preco_anterior'] - $i['preco']) * $q;
            }
            $qtd += $q;
        }

        $faltaPortesGratis = max(0, PORTES_GRATIS_LUANDA - $subtotal);

        // Cupão aplicado (revalidado a cada cálculo)
        $cupao = $this->cupaoActual();
        $desconto = 0;
        if ($cupao && $subtotal >= (float) $cupao['minimo_compra']) {
            $desconto = $cupao['tipo'] === 'percentual'
                ? round($subtotal * (float) $cupao['valor'] / 100)
                : min((float) $cupao['valor'], $subtotal);
        } elseif ($cupao) {
            $cupao['aviso'] = 'Este cupão exige compras a partir de ' . kz($cupao['minimo_compra']) . '.';
        }
        $total = $subtotal - $desconto;

        return [
            'quantidade'          => $qtd,
            'subtotal'            => $subtotal,
            'poupanca'            => $poupanca,
            'cupao'               => $cupao,
            'desconto'            => $desconto,
            'total'               => $total,
            'iva_incluido'        => round($total - $total / (1 + IVA_TAXA / 100), 2),
            'falta_portes_gratis' => $faltaPortesGratis,
            'progresso_portes'    => $subtotal > 0 ? min(100, (int) round($subtotal / PORTES_GRATIS_LUANDA * 100)) : 0,
        ];
    }

    // ----- Cupões -------------------------------------------------------------

    private function cupaoValido(string $codigo): ?array
    {
        return $this->um(
            "SELECT * FROM cupoes
              WHERE codigo = ? AND activo = 1
                AND (inicio IS NULL OR inicio <= NOW())
                AND (fim IS NULL OR fim >= NOW())
                AND (utilizacoes_max IS NULL OR utilizacoes < utilizacoes_max)
              LIMIT 1",
            [mb_strtoupper(trim($codigo))]
        );
    }

    /** Aplica um cupão ao carrinho. Devolve mensagem de erro ou null. */
    public function aplicarCupao(string $codigo): ?string
    {
        $id = $this->idActual();
        if (!$id || $this->contar() === 0) {
            return 'Adicione produtos ao carrinho antes de usar um cupão.';
        }
        $cupao = $this->cupaoValido($codigo);
        if (!$cupao) {
            return 'Este código não é válido ou já expirou. Confirme as letras e tente de novo.';
        }
        $this->executar("UPDATE carrinhos SET cupao_id = ? WHERE id = ?", [$cupao['id'], $id]);
        return null;
    }

    public function removerCupao(): void
    {
        if ($id = $this->idActual()) {
            $this->executar("UPDATE carrinhos SET cupao_id = NULL WHERE id = ?", [$id]);
        }
    }

    public function cupaoActual(): ?array
    {
        $id = $this->idActual();
        if (!$id) {
            return null;
        }
        $codigo = $this->valor("SELECT cp.codigo FROM carrinhos c JOIN cupoes cp ON cp.id = c.cupao_id WHERE c.id = ?", [$id]);
        return $codigo ? $this->cupaoValido($codigo) : null;
    }

    public function contar(): int
    {
        $id = $this->idActual();
        return $id ? (int) $this->valor("SELECT COALESCE(SUM(quantidade),0) FROM carrinho_itens WHERE carrinho_id = ?", [$id]) : 0;
    }

    private function tocar(int $id): void
    {
        $this->executar("UPDATE carrinhos SET actualizado_em = NOW() WHERE id = ?", [$id]);
    }
}
