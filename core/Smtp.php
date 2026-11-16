<?php
// =====================================================================
// core/Smtp.php — Cliente SMTP mínimo (sem bibliotecas externas).
//  Gmail / Google Workspace: smtp.gmail.com, porta 587 (STARTTLS) ou
//  465 (SSL), utilizador = endereço completo, senha = "senha de app"
//  (Conta Google → Segurança → Verificação em 2 passos → Senhas de app).
//  Lança \RuntimeException com a resposta do servidor se algo falhar.
// =====================================================================

namespace Core;

class Smtp
{
    /** @var resource|null */
    private $ligacao = null;

    public function __construct(
        private string $host,
        private int $porta,
        private string $utilizador,
        private string $senha,
        private string $seguranca = 'tls', // 'tls' (STARTTLS), 'ssl' ou 'nenhuma'
        private int $tempoLimite = 15,
    ) {
    }

    /** Envia uma mensagem MIME já montada (cabeçalhos + corpo, linhas CRLF). */
    public function enviar(string $de, string $para, string $mensagem): void
    {
        try {
            $this->ligar();
            $this->comando('MAIL FROM:<' . $de . '>', [250]);
            $this->comando('RCPT TO:<' . $para . '>', [250, 251]);
            $this->comando('DATA', [354]);
            // "Dot-stuffing": linhas que começam por "." levam um ponto extra (RFC 5321 4.5.2)
            $corpo = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $mensagem));
            $this->comando(str_replace("\n", "\r\n", rtrim($corpo, "\n")) . "\r\n.", [250]);
            $this->comando('QUIT', [221]);
        } finally {
            if (is_resource($this->ligacao)) {
                fclose($this->ligacao);
            }
            $this->ligacao = null;
        }
    }

    private function ligar(): void
    {
        $esquema = $this->seguranca === 'ssl' ? 'ssl://' : 'tcp://';
        $contexto = stream_context_create(['ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
            'peer_name'        => $this->host,
        ]]);
        $ligacao = @stream_socket_client($esquema . $this->host . ':' . $this->porta, $num, $erro, $this->tempoLimite, STREAM_CLIENT_CONNECT, $contexto);
        if (!$ligacao) {
            throw new \RuntimeException("Sem ligação a {$this->host}:{$this->porta} — $erro ($num)");
        }
        stream_set_timeout($ligacao, $this->tempoLimite);
        $this->ligacao = $ligacao;

        $this->ler([220]);
        $nome = gethostname() ?: 'localhost';
        $this->comando('EHLO ' . $nome, [250]);

        if ($this->seguranca === 'tls') {
            $this->comando('STARTTLS', [220]);
            $metodo = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);
            if (!stream_socket_enable_crypto($this->ligacao, true, $metodo)) {
                throw new \RuntimeException('Não foi possível activar TLS (STARTTLS).');
            }
            $this->comando('EHLO ' . $nome, [250]);
        }

        if ($this->utilizador !== '') {
            $this->comando('AUTH LOGIN', [334]);
            $this->comando(base64_encode($this->utilizador), [334], 'utilizador');
            $this->comando(base64_encode($this->senha), [235], 'senha');
        }
    }

    /** $oculto: o que aparece nas mensagens de erro em vez do conteúdo (credenciais). */
    private function comando(string $linha, array $esperados, ?string $oculto = null): string
    {
        if (fwrite($this->ligacao, $linha . "\r\n") === false) {
            throw new \RuntimeException('Ligação SMTP interrompida.');
        }
        try {
            return $this->ler($esperados);
        } catch (\RuntimeException $e) {
            $mostrar = $oculto !== null ? "[$oculto]" : strtok($linha, "\r\n");
            throw new \RuntimeException(mb_substr($mostrar, 0, 60) . ' → ' . $e->getMessage());
        }
    }

    private function ler(array $esperados): string
    {
        $resposta = '';
        while (($linha = fgets($this->ligacao, 1024)) !== false) {
            $resposta .= $linha;
            // Resposta multilinha: "250-..." continua, "250 ..." termina
            if (strlen($linha) < 4 || $linha[3] === ' ') {
                break;
            }
        }
        if ($resposta === '') {
            throw new \RuntimeException('O servidor SMTP não respondeu (tempo esgotado).');
        }
        $codigo = (int) substr($resposta, 0, 3);
        if (!in_array($codigo, $esperados, true)) {
            throw new \RuntimeException(preg_replace('/\s+/', ' ', trim($resposta)));
        }
        return $resposta;
    }
}
