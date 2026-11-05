<?php
// =====================================================================
// app/Servicos/Lembretes.php — Recuperação de carrinho abandonado
//
//   1.º email: X horas depois da última alteração ao carrinho.
//   2.º email: Y horas depois do 1.º, com um cupão pessoal de uso único
//              (já aplicado ao carrinho, o cliente não precisa de o escrever).
//   Pára quando: o cliente compra, esvazia o carrinho, mexe nele (recomeça
//   a contagem), desliga os lembretes ou o carrinho fica demasiado antigo.
//
// Só clientes com conta (a loja só tem o email de quem tem conta).
// IMPORTANTE: carrinhos.actualizado_em tem ON UPDATE CURRENT_TIMESTAMP;
// todas as escritas aqui fixam actualizado_em = actualizado_em para não
// "rejuvenescer" o carrinho.
// =====================================================================

namespace App\Servicos;

use Core\Database;
use Core\Email;
use PDO;

class Lembretes
{
    /** Envia os lembretes devidos. Devolve ['primeiro' => n, 'segundo' => n, 'ignorados' => n]. */
    public static function processar(): array
    {
        $db  = Database::ligacao();
        $cfg = Marketing::cfg();
        $r   = ['primeiro' => 0, 'segundo' => 0, 'ignorados' => 0];

        // Evita duas execuções em simultâneo (cron + botão do painel)
        if (!(int) $db->query("SELECT GET_LOCK('dtk_lembretes', 0)")->fetchColumn()) {
            return $r;
        }
        try {
            $disponiveis = max(0, $cfg['limite_por_hora'] - Marketing::enviadosUltimaHora());

            $st = $db->prepare(
                "SELECT c.id, c.token, c.lembretes_enviados, c.recuperacao_token, c.cupao_id,
                        cl.id AS cliente_id, cl.nome, cl.email
                   FROM carrinhos c
                   JOIN clientes cl ON cl.id = c.cliente_id
                  WHERE c.convertido = 0
                    AND cl.estado = 'activo' AND cl.recebe_lembretes = 1
                    AND c.actualizado_em > NOW() - INTERVAL ? DAY
                    AND EXISTS (SELECT 1 FROM carrinho_itens ci WHERE ci.carrinho_id = c.id)
                    AND (
                         (c.lembretes_enviados = 0 AND c.actualizado_em < NOW() - INTERVAL ? HOUR)
                      OR (c.lembretes_enviados = 1 AND c.recuperacao_enviada_em < NOW() - INTERVAL ? HOUR
                                                   AND c.actualizado_em <= c.recuperacao_enviada_em)
                    )
                  ORDER BY c.actualizado_em
                  LIMIT " . (int) min($disponiveis, $cfg['lote_por_execucao'])
            );
            $st->execute([$cfg['carrinho_max_dias'], $cfg['lembrete1_horas'], $cfg['lembrete2_horas']]);

            foreach ($st->fetchAll() as $c) {
                $itens = self::itens($db, (int) $c['id']);
                $valor = array_sum(array_map(fn ($i) => $i['preco'] * $i['quantidade'], $itens));
                $disponivel = array_filter($itens, fn ($i) => (int) $i['stock'] > 0);

                if (!$disponivel || $valor < $cfg['carrinho_minimo']) {
                    // Não vale a pena lembrar: marca como tratado para não voltar a avaliar
                    $db->prepare("UPDATE carrinhos SET lembretes_enviados = 2, actualizado_em = actualizado_em WHERE id = ?")->execute([$c['id']]);
                    $r['ignorados']++;
                    continue;
                }

                $token = $c['recuperacao_token'] ?: bin2hex(random_bytes(16));
                $segundo = (int) $c['lembretes_enviados'] === 1;
                $cupao = $segundo && $cfg['cupao_lembrete2'] > 0 && !$c['cupao_id'] ? self::criarCupao($db, (int) $c['id'], (int) $c['cliente_id']) : null;

                $html = self::email($c, $itens, $valor, $token, $segundo, $cupao);
                $assunto = $segundo
                    ? ($cupao ? 'Guardámos o seu carrinho, com ' . $cfg['cupao_lembrete2'] . '% de desconto' : 'O seu carrinho continua à sua espera')
                    : 'Esqueceu-se de alguma coisa?';
                Email::enviar($c['email'], $assunto, $html, [
                    'cancelar' => Marketing::linkSairLembretes((int) $c['cliente_id']),
                    'pre'      => count($itens) === 1 ? $itens[0]['nome'] . ' ainda está no seu carrinho.' : count($itens) . ' produtos ainda estão no seu carrinho.',
                ]);

                $db->prepare(
                    "UPDATE carrinhos SET lembretes_enviados = lembretes_enviados + 1, recuperacao_enviada_em = NOW(),
                            recuperacao_token = ?, actualizado_em = actualizado_em WHERE id = ?"
                )->execute([$token, $c['id']]);
                $r[$segundo ? 'segundo' : 'primeiro']++;
            }
        } finally {
            $db->query("SELECT RELEASE_LOCK('dtk_lembretes')");
        }
        return $r;
    }

    private static function itens(PDO $db, int $carrinhoId): array
    {
        $st = $db->prepare(
            "SELECT ci.quantidade, p.nome, p.slug, p.preco, p.preco_anterior, p.stock, p.imagem, m.nome AS marca_nome
               FROM carrinho_itens ci JOIN produtos p ON p.id = ci.produto_id AND p.estado = 'activo'
          LEFT JOIN marcas m ON m.id = p.marca_id
              WHERE ci.carrinho_id = ?"
        );
        $st->execute([$carrinhoId]);
        return $st->fetchAll();
    }

    /** Cupão pessoal: uso único, validade curta, aplicado directamente ao carrinho. */
    private static function criarCupao(PDO $db, int $carrinhoId, int $clienteId): array
    {
        $cfg = Marketing::cfg();
        $codigo = 'VOLTA-' . strtoupper(bin2hex(random_bytes(3)));
        $fim = date('Y-m-d H:i:s', strtotime('+' . $cfg['cupao_validade_horas'] . ' hours'));
        $db->prepare("INSERT INTO cupoes (codigo, descricao, tipo, valor, minimo_compra, utilizacoes_max, fim, activo) VALUES (?, ?, 'percentual', ?, 0, 1, ?, 1)")
           ->execute([$codigo, 'Recuperação de carrinho (cliente ' . $clienteId . ')', $cfg['cupao_lembrete2'], $fim]);
        $id = (int) $db->lastInsertId();
        $db->prepare("UPDATE carrinhos SET cupao_id = ?, cupao_recuperacao_id = ?, actualizado_em = actualizado_em WHERE id = ?")->execute([$id, $id, $carrinhoId]);
        return ['codigo' => $codigo, 'valor' => $cfg['cupao_lembrete2'], 'fim' => $fim];
    }

    private static function email(array $c, array $itens, float $valor, string $token, bool $segundo, ?array $cupao): string
    {
        $nome = e(explode(' ', $c['nome'])[0]);
        $link = URL_BASE . '/carrinho/recuperar/' . $token . '?utm_source=email&utm_medium=lembrete&utm_campaign=carrinho' . ($segundo ? '2' : '1');

        $linhas = '';
        foreach ($itens as $i) {
            $poucas = (int) $i['stock'] > 0 && (int) $i['stock'] <= 3;
            $linhas .= '<tr><td style="padding:10px 0;border-bottom:1px solid #DEE2EC;font-size:14px">'
                . '<strong>' . e($i['nome']) . '</strong>' . ((int) $i['quantidade'] > 1 ? ' × ' . (int) $i['quantidade'] : '')
                . ((int) $i['stock'] < 1 ? '<br><span style="color:#5D6680;font-size:12px">Esgotou entretanto</span>' : ($poucas ? '<br><span style="color:#C47800;font-size:12px">Restam ' . (int) $i['stock'] . ' em stock</span>' : ''))
                . '</td><td align="right" style="padding:10px 0;border-bottom:1px solid #DEE2EC;font-size:14px;white-space:nowrap">' . kz($i['preco'] * $i['quantidade']) . '</td></tr>';
        }

        $intro = $segundo
            ? '<p>Olá ' . $nome . ', os produtos que escolheu continuam reservados no seu carrinho.</p>'
            : '<p>Olá ' . $nome . ', vimos que deixou produtos no carrinho. Guardámo-los para si.</p>';

        $oferta = $cupao
            ? '<div style="margin:16px 0;padding:14px 16px;border-radius:12px;background:#FDE8E6;color:#141A2E"><strong>' . (int) $cupao['valor'] . '% de desconto</strong> com o código <strong>' . e($cupao['codigo']) . '</strong>, já aplicado ao seu carrinho. Válido até ' . date('d/m \à\s H:i', strtotime($cupao['fim'])) . '.</div>'
            : '';

        return $intro . $oferta
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $linhas
            . '<tr><td style="padding:12px 0;font-weight:bold">Total</td><td align="right" style="padding:12px 0;font-weight:bold;font-size:18px">' . kz($valor) . '</td></tr></table>'
            . Marketing::botao('Voltar ao carrinho', $link)
            . '<p style="font-size:13px;color:#5D6680">Pague com Multicaixa Express, referência ou transferência. Dúvidas? Responda a este email ou fale connosco no WhatsApp ' . e(TELEFONE_LOJA) . '.</p>';
    }

    /** Números para o painel (últimos N dias). */
    public static function estatisticas(int $dias = 30): array
    {
        $db = Database::ligacao();
        $st = $db->prepare(
            "SELECT
                COUNT(DISTINCT CASE WHEN c.lembretes_enviados BETWEEN 1 AND 2 AND c.recuperacao_enviada_em IS NOT NULL THEN c.id END) AS lembrados,
                COUNT(DISTINCT CASE WHEN c.recuperado_em IS NOT NULL THEN c.id END) AS clicados,
                COUNT(DISTINCT CASE WHEN c.recuperacao_enviada_em IS NOT NULL AND c.convertido = 1 THEN c.id END) AS convertidos
               FROM carrinhos c WHERE c.criado_em > NOW() - INTERVAL ? DAY"
        );
        $st->execute([$dias]);
        $r = $st->fetch();
        $st = $db->prepare("SELECT COUNT(*) n, COALESCE(SUM(total),0) receita FROM pedidos WHERE carrinho_recuperado = 1 AND pago_em IS NOT NULL AND estado NOT IN ('cancelado','reembolsado') AND criado_em > NOW() - INTERVAL ? DAY");
        $st->execute([$dias]);
        return $r + $st->fetch();
    }
}
