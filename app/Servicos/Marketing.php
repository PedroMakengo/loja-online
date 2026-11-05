<?php
// =====================================================================
// app/Servicos/Marketing.php — utilitários comuns: configuração, links
// de cancelamento assinados (HMAC) e blocos HTML para emails.
// =====================================================================

namespace App\Servicos;

class Marketing
{
    private static ?array $cfg = null;

    public static function cfg(): array
    {
        return self::$cfg ??= require RAIZ . '/config/marketing.php';
    }

    /** Assinatura curta de um valor (ex.: email ou id de cliente). */
    public static function assinar(string $valor): string
    {
        return substr(hash_hmac('sha256', $valor, self::cfg()['segredo_links']), 0, 32);
    }

    public static function valida(string $valor, string $assinatura): bool
    {
        return hash_equals(self::assinar($valor), $assinatura);
    }

    public static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function deB64(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/'));
    }

    public static function linkSairNewsletter(string $email): string
    {
        $email = mb_strtolower($email);
        return URL_BASE . '/newsletter/sair/' . self::b64($email) . '/' . self::assinar('nl:' . $email);
    }

    public static function linkSairLembretes(int $clienteId): string
    {
        return URL_BASE . '/lembretes/sair/' . $clienteId . '/' . self::assinar('lb:' . $clienteId);
    }

    /** Envios na última hora (newsletter + lembretes), para respeitar o limite do alojamento. */
    public static function enviadosUltimaHora(): int
    {
        $db = \Core\Database::ligacao();
        return (int) $db->query("SELECT COUNT(*) FROM newsletter_envios WHERE enviado_em > NOW() - INTERVAL 1 HOUR")->fetchColumn()
             + (int) $db->query("SELECT COUNT(*) FROM carrinhos WHERE recuperacao_enviada_em > NOW() - INTERVAL 1 HOUR")->fetchColumn();
    }

    /**
     * Grelha de produtos em HTML de email (tabelas e estilos inline).
     * As ilustrações SVG não aparecem no Gmail/Outlook, por isso só se
     * mostram fotografias reais; sem fotografia, o cartão fica só com texto.
     */
    public static function blocoProdutos(array $produtos, string $utm): string
    {
        if (!$produtos) {
            return '';
        }
        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0">';
        foreach (array_chunk($produtos, 2) as $linha) {
            $html .= '<tr>';
            foreach ($linha as $p) {
                $link = URL_BASE . '/produto/' . $p['slug'] . '?' . $utm;
                $img = (!empty($p['imagem']) && preg_match('/\.(jpe?g|png|webp)$/i', $p['imagem']))
                    ? '<a href="' . e($link) . '"><img src="' . e(URL_ORIGEM . asset('img/produtos/' . $p['imagem'])) . '" alt="" width="220" style="display:block;width:100%;max-width:220px;height:auto;border-radius:10px;background:#FFFFFF;margin-bottom:10px"></a>'
                    : '';
                $promo = $p['preco_anterior'] > $p['preco'];
                $html .= '<td width="50%" valign="top" style="padding:6px">'
                    . '<div style="border:1px solid #DEE2EC;border-radius:12px;padding:12px">' . $img
                    . '<p style="margin:0;font-size:12px;color:#5D6680">' . e($p['marca_nome'] ?? '') . '</p>'
                    . '<p style="margin:2px 0 8px;font-size:14px;font-weight:bold;line-height:1.3"><a href="' . e($link) . '" style="color:#141A2E;text-decoration:none">' . e($p['nome']) . '</a></p>'
                    . ($promo ? '<p style="margin:0;font-size:12px;color:#5D6680;text-decoration:line-through">' . kz($p['preco_anterior']) . '</p>' : '')
                    . '<p style="margin:0;font-size:18px;font-weight:bold;color:' . ($promo ? '#E83F34' : '#141A2E') . '">' . kz($p['preco']) . '</p>'
                    . '</div></td>';
            }
            if (count($linha) === 1) {
                $html .= '<td width="50%"></td>';
            }
            $html .= '</tr>';
        }
        return $html . '</table>';
    }

    public static function botao(string $texto, string $link): string
    {
        return '<p style="margin:20px 0"><a href="' . e($link) . '" style="display:inline-block;background:#2346F5;color:#ffffff;padding:13px 22px;border-radius:10px;text-decoration:none;font-weight:bold">' . e($texto) . '</a></p>';
    }
}
