<?php
// =====================================================================
// app/Servicos/Newsletter.php — Campanhas de email
//
// Destinatários: subscritores activos da newsletter + clientes que
// aceitaram promoções (sem repetir emails). O envio é feito em lotes
// pelo cron, respeitando o limite por hora do alojamento.
// =====================================================================

namespace App\Servicos;

use App\Models\Produto;
use Core\Database;
use Core\Email;

class Newsletter
{
    public static function destinatarios(): array
    {
        return Database::ligacao()->query(
            "SELECT LOWER(email) FROM newsletter WHERE estado = 'activo'
             UNION
             SELECT LOWER(email) FROM clientes WHERE aceita_marketing = 1 AND estado = 'activo'"
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function produtos(array $c): array
    {
        $p = new Produto();
        return match ($c['fonte']) {
            'promocoes'     => array_slice(array_merge($p->ofertasDoDia(6), $p->listar(['promo' => 1, 'stock' => 1, 'ordem' => 'desconto'], 6)['itens']), 0, 6),
            'novidades'     => $p->novidades(6),
            'mais_vendidos' => $p->maisVendidos(6),
            'escolhidos'    => (function () use ($p, $c) {
                $ids = array_filter(array_map('intval', explode(',', (string) $c['produtos_ids'])));
                $porId = array_column($p->porIds($ids), null, 'id');
                return array_values(array_filter(array_map(fn ($id) => $porId[$id] ?? null, $ids)));
            })(),
            default => [],
        };
    }

    /** HTML do corpo (o invólucro com o rodapé de cancelamento é aplicado pelo Email). */
    public static function corpo(array $c, ?array $produtos = null): string
    {
        $utm = 'utm_source=newsletter&utm_medium=email&utm_campaign=' . rawurlencode('c' . $c['id']);
        $produtos ??= self::produtos($c);
        $texto = $c['texto'] ? implode('', array_map(fn ($par) => '<p>' . nl2br(e(trim($par)), false) . '</p>', preg_split('/\R{2,}/', trim($c['texto'])))) : '';
        $botao = $c['botao_texto'] && $c['botao_link']
            ? Marketing::botao($c['botao_texto'], (str_starts_with($c['botao_link'], 'http') ? $c['botao_link'] : URL_BASE . '/' . ltrim($c['botao_link'], '/')) . (str_contains($c['botao_link'], '?') ? '&' : '?') . $utm)
            : '';
        return '<h1 style="margin:0 0 12px;font-size:24px;line-height:1.2">' . e($c['titulo']) . '</h1>'
            . $texto . Marketing::blocoProdutos($produtos, $utm) . $botao;
    }

    /** Coloca a campanha na fila de envio. Devolve o número de destinatários. */
    public static function enfileirar(int $campanhaId): int
    {
        $db = Database::ligacao();
        $emails = self::destinatarios();
        $db->beginTransaction();
        $ins = $db->prepare("INSERT IGNORE INTO newsletter_envios (campanha_id, email) VALUES (?, ?)");
        foreach ($emails as $e) {
            $ins->execute([$campanhaId, $e]);
        }
        $db->prepare("UPDATE newsletter_campanhas SET estado = 'a_enviar', total = (SELECT COUNT(*) FROM newsletter_envios WHERE campanha_id = ?) WHERE id = ?")
           ->execute([$campanhaId, $campanhaId]);
        $db->commit();
        return count($emails);
    }

    /** Envia um lote da fila. Devolve quantos enviou. */
    public static function processarLote(): int
    {
        $db  = Database::ligacao();
        $cfg = Marketing::cfg();
        if (!(int) $db->query("SELECT GET_LOCK('dtk_newsletter', 0)")->fetchColumn()) {
            return 0;
        }
        $enviados = 0;
        try {
            $limite = (int) min($cfg['lote_por_execucao'], max(0, $cfg['limite_por_hora'] - Marketing::enviadosUltimaHora()));
            if ($limite === 0) {
                return 0;
            }
            $fila = $db->query(
                "SELECT e.id, e.email, e.campanha_id FROM newsletter_envios e
                   JOIN newsletter_campanhas c ON c.id = e.campanha_id AND c.estado = 'a_enviar'
                  WHERE e.estado = 'pendente' ORDER BY e.id LIMIT $limite"
            )->fetchAll();

            $campanhas = []; $corpos = [];
            foreach ($fila as $f) {
                $cid = (int) $f['campanha_id'];
                if (!isset($campanhas[$cid])) {
                    $st = $db->prepare("SELECT * FROM newsletter_campanhas WHERE id = ?");
                    $st->execute([$cid]);
                    $campanhas[$cid] = $st->fetch();
                    $corpos[$cid] = self::corpo($campanhas[$cid]); // produtos e preços do momento do envio
                }
                // Quem cancelou depois de a campanha entrar na fila não recebe
                $st = $db->prepare("SELECT 1 FROM newsletter WHERE email = ? AND estado = 'cancelado'");
                $st->execute([$f['email']]);
                $cancelou = $st->fetchColumn() && !self::clienteAceita($f['email']);

                $ok = !$cancelou && Email::enviar($f['email'], $campanhas[$cid]['assunto'], $corpos[$cid], [
                    'cancelar' => Marketing::linkSairNewsletter($f['email']),
                    'pre'      => $campanhas[$cid]['pre_cabecalho'],
                ]);
                $db->prepare("UPDATE newsletter_envios SET estado = ?, enviado_em = NOW() WHERE id = ?")->execute([$ok ? 'enviado' : 'falhado', $f['id']]);
                $ok ? $enviados++ : null;
            }

            // Actualiza contadores e fecha campanhas terminadas
            $db->exec(
                "UPDATE newsletter_campanhas c SET
                    enviados = (SELECT COUNT(*) FROM newsletter_envios WHERE campanha_id = c.id AND estado = 'enviado'),
                    falhados = (SELECT COUNT(*) FROM newsletter_envios WHERE campanha_id = c.id AND estado = 'falhado'),
                    estado   = IF(EXISTS (SELECT 1 FROM newsletter_envios WHERE campanha_id = c.id AND estado = 'pendente'), 'a_enviar', 'enviada'),
                    enviada_em = IF(EXISTS (SELECT 1 FROM newsletter_envios WHERE campanha_id = c.id AND estado = 'pendente'), NULL, COALESCE(enviada_em, NOW()))
                  WHERE c.estado = 'a_enviar'"
            );
        } finally {
            $db->query("SELECT RELEASE_LOCK('dtk_newsletter')");
        }
        return $enviados;
    }

    private static function clienteAceita(string $email): bool
    {
        $st = Database::ligacao()->prepare("SELECT 1 FROM clientes WHERE email = ? AND aceita_marketing = 1 AND estado = 'activo'");
        $st->execute([$email]);
        return (bool) $st->fetchColumn();
    }

    /** Cancela a subscrição em ambas as origens (newsletter e conta de cliente). */
    public static function cancelar(string $email): void
    {
        $db = Database::ligacao();
        $email = mb_strtolower($email);
        $db->prepare("INSERT INTO newsletter (email, origem, estado, cancelado_em) VALUES (?, 'cancelamento', 'cancelado', NOW())
                      ON DUPLICATE KEY UPDATE estado = 'cancelado', cancelado_em = NOW()")->execute([$email]);
        $db->prepare("UPDATE clientes SET aceita_marketing = 0 WHERE email = ?")->execute([$email]);
        $db->prepare("UPDATE newsletter_envios SET estado = 'falhado' WHERE email = ? AND estado = 'pendente'")->execute([$email]);
    }
}
