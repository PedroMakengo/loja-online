<?php
// =====================================================================
// core/Email.php — Envio de emails transaccionais.
// Drivers (EMAIL_DRIVER):
//   'smtp' — SMTP autenticado (Gmail: smtp.gmail.com + senha de app). Recomendado em produção.
//   'mail' — mail() do servidor (só onde há sendmail, ex.: cPanel).
//   'log'  — grava em storage/logs/emails.log (desenvolvimento).
// Uma falha de envio nunca interrompe o pedido: fica em storage/logs/emails-erros.log.
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

        $corpo = ($opcoes['modelo'] ?? true) ? self::modelo($assunto, $html, $opcoes['pre'] ?? null, $opcoes['cancelar'] ?? null) : $html;
        $cabecalhos = [
            'From: ' . self::cabecalho(NOME_SITE) . ' <' . EMAIL_REMETENTE . '>',
            'Reply-To: ' . EMAIL_LOJA,
        ];
        if (!empty($opcoes['cancelar'])) {
            $cabecalhos[] = 'List-Unsubscribe: <' . $opcoes['cancelar'] . '>';
            $cabecalhos[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }

        try {
            if (EMAIL_DRIVER === 'smtp') {
                [$mime, $tipo] = self::mime($corpo);
                $mensagem = implode("\r\n", array_merge([
                    'Date: ' . date('r'),
                    'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', EMAIL_REMETENTE)[1] ?? 'localhost') . '>',
                    'To: ' . $para,
                    'Subject: ' . self::cabecalho($assunto),
                    'MIME-Version: 1.0',
                    'Content-Type: ' . $tipo,
                ], $cabecalhos)) . "\r\n\r\n" . $mime;
                (new Smtp(SMTP_HOST, SMTP_PORTA, SMTP_UTILIZADOR, SMTP_SENHA, SMTP_SEGURANCA))
                    ->enviar(EMAIL_REMETENTE, $para, $mensagem);
                return true;
            }

            $ok = mail($para, self::cabecalho($assunto), $corpo, implode("\r\n", array_merge(
                ['MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8'], $cabecalhos)));
            if (!$ok) {
                throw new \RuntimeException('mail() devolveu false (o servidor tem sendmail?).');
            }
            return true;
        } catch (\Throwable $e) {
            self::registarFalha($para, $assunto, $e->getMessage());
            return false;
        }
    }

    /** Corpo multipart/alternative (texto + HTML): entrega melhor e lê-se em clientes simples. */
    private static function mime(string $html): array
    {
        $fronteira = '=_dtk_' . bin2hex(random_bytes(8));
        $mime = "--$fronteira\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode(self::textoSimples($html)))
            . "--$fronteira\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$fronteira--\r\n";
        return [$mime, 'multipart/alternative; boundary="' . $fronteira . '"'];
    }

    /** Codifica em UTF-8 (RFC 2047) só quando há acentos. */
    private static function cabecalho(string $texto): string
    {
        $texto = str_replace(["\r", "\n"], ' ', $texto); // nunca deixar quebrar cabeçalhos
        return preg_match('/[^\x20-\x7E]/', $texto) ? '=?UTF-8?B?' . base64_encode($texto) . '?=' : $texto;
    }

    private static function registarFalha(string $para, string $assunto, string $erro): void
    {
        $linha = sprintf("[%s] %s | Para: %s | Assunto: %s | %s\n", date('c'), EMAIL_DRIVER, $para, $assunto, $erro);
        @file_put_contents(RAIZ . '/storage/logs/emails-erros.log', $linha, FILE_APPEND);
        error_log('[email] ' . trim($linha));
    }

    /** Versão em texto para o registo de desenvolvimento, com os links visíveis: "texto [URL]". */
    private static function textoSimples(string $html): string
    {
        $html = preg_replace(['#<head\b.*?</head>#is', '#<div style="display:none[^>]*>.*?</div>#is'], '', $html);
        $html = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 [$1]', $html);
        $html = str_replace(['<br>', '</p>', '</tr>', '</h1>', '</h2>', '</li>'], "\n", $html);
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
