<?php
// =====================================================================
// core/Email.php — Envio de emails transaccionais.
// Em 'dev' grava em storage/logs/emails.log em vez de enviar.
// Em produção usa mail() do servidor (cPanel). Para SMTP autenticado,
// substituir apenas o método enviar().
// =====================================================================

namespace Core;

class Email
{
    /**
     * $opcoes: 'cancelar' => URL de cancelamento (cabeçalhos List-Unsubscribe, exigidos pelo Gmail
     * para envios em massa), 'pre' => texto de pré-visualização, 'modelo' => false para HTML já completo.
     */
    public static function enviar(string $para, string $assunto, string $html, array $opcoes = []): bool
    {
        if (EMAIL_DRIVER === 'log') {
            $registo = sprintf("[%s] Para: %s\nAssunto: %s\n%s%s\n\n", date('c'), $para, $assunto,
                !empty($opcoes['cancelar']) ? 'Cancelar: ' . $opcoes['cancelar'] . "\n" : '',
                self::textoSimples($html));
            @file_put_contents(RAIZ . '/storage/logs/emails.log', $registo, FILE_APPEND);
            return true;
        }

        $linhas = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . NOME_SITE . ' <' . EMAIL_LOJA . '>',
            'Reply-To: ' . EMAIL_LOJA,
        ];
        if (!empty($opcoes['cancelar'])) {
            $linhas[] = 'List-Unsubscribe: <' . $opcoes['cancelar'] . '>';
            $linhas[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }
        $corpo = ($opcoes['modelo'] ?? true) ? self::modelo($assunto, $html, $opcoes['pre'] ?? null, $opcoes['cancelar'] ?? null) : $html;
        return mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo, implode("\r\n", $linhas));
    }

    /** Invólucro HTML simples, compatível com clientes de email. */
    /** Versão em texto para o registo de desenvolvimento, com os links visíveis: "texto [URL]". */
    private static function textoSimples(string $html): string
    {
        $html = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 [$1]', $html);
        $html = str_replace(['<br>', '</p>', '</tr>', '</h1>'], "\n", $html);
        return trim(preg_replace("/\n{3,}/", "\n\n", strip_tags($html)));
    }

    public static function modelo(string $titulo, string $corpo, ?string $pre = null, ?string $cancelar = null): string
    {
        return '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"><title>' . e($titulo) . '</title></head>'
            . '<body style="margin:0;background:#F3F5FA;font-family:Arial,sans-serif;color:#141A2E">'
            . ($pre ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . e($pre) . '</div>' : '')
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:16px 8px">'
            // Largura fluida até 560px: no telemóvel ocupa o ecrã sem deslizar para o lado
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;background:#fff;border-radius:16px;border:1px solid #DEE2EC">'
            . '<tr><td style="padding:24px 28px;border-bottom:1px solid #DEE2EC;font-size:20px;font-weight:bold">' . NOME_SITE . '</td></tr>'
            . '<tr><td style="padding:24px 20px;font-size:15px;line-height:1.6">' . $corpo . '</td></tr>'
            . '<tr><td style="padding:16px 28px;border-top:1px solid #DEE2EC;font-size:12px;color:#5D6680">' . NOME_SITE . ', ' . MORADA_LOJA . '.'
            . ($cancelar ? ' <a href="' . e($cancelar) . '" style="color:#5D6680">Deixar de receber estes emails</a>.' : '') . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
