<?php
// =====================================================================
// core/Google.php — "Entrar com o Google" (OAuth 2.0 / OpenID Connect)
//  Fluxo "authorization code" no servidor, sem bibliotecas externas:
//   1. urlAutorizacao() → o cliente é enviado para accounts.google.com
//   2. o Google volta a /entrar/google/retorno?code=…&state=…
//   3. trocarCodigo() troca o code pelo id_token (pedido servidor→Google
//      por HTTPS com o client_secret) e valida iss/aud/exp/nonce.
//  Como o id_token chega directamente do endpoint de token do Google por
//  TLS, a assinatura não precisa de ser verificada (OIDC Core 3.1.3.7).
// =====================================================================

namespace Core;

class Google
{
    private const URL_AUTORIZACAO = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const URL_TOKEN       = 'https://oauth2.googleapis.com/token';
    private const EMISSORES       = ['https://accounts.google.com', 'accounts.google.com'];

    /** O botão só aparece quando GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET estão definidos. */
    public static function activo(): bool
    {
        return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
    }

    public static function urlRetorno(): string
    {
        return GOOGLE_REDIRECT_URI !== '' ? GOOGLE_REDIRECT_URI : URL_BASE . '/entrar/google/retorno';
    }

    public static function urlAutorizacao(string $estado, string $nonce): string
    {
        return self::URL_AUTORIZACAO . '?' . http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => self::urlRetorno(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $estado,
            'nonce'         => $nonce,
            'prompt'        => 'select_account',
            'hl'            => 'pt-PT',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Troca o código pelo id_token e devolve os dados validados:
     * ['sub', 'email', 'email_verified', 'nome', 'foto'].
     * Lança \RuntimeException se algo falhar.
     */
    public static function trocarCodigo(string $codigo, string $nonce): array
    {
        $resposta = self::post(self::URL_TOKEN, [
            'code'          => $codigo,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => self::urlRetorno(),
            'grant_type'    => 'authorization_code',
        ]);

        $idToken = $resposta['id_token'] ?? '';
        $partes  = explode('.', $idToken);
        if (count($partes) !== 3) {
            throw new \RuntimeException('Resposta do Google sem id_token.');
        }
        $dados = json_decode(self::base64url($partes[1]), true);
        if (!is_array($dados)) {
            throw new \RuntimeException('id_token ilegível.');
        }

        if (!in_array($dados['iss'] ?? '', self::EMISSORES, true)) {
            throw new \RuntimeException('Emissor do token inválido.');
        }
        $aud = $dados['aud'] ?? '';
        if (is_array($aud) ? !in_array(GOOGLE_CLIENT_ID, $aud, true) : $aud !== GOOGLE_CLIENT_ID) {
            throw new \RuntimeException('Token emitido para outra aplicação.');
        }
        if ((int) ($dados['exp'] ?? 0) < time() - 60) {
            throw new \RuntimeException('Token expirado.');
        }
        if (!hash_equals($nonce, (string) ($dados['nonce'] ?? ''))) {
            throw new \RuntimeException('Nonce inválido.');
        }
        if (empty($dados['sub']) || empty($dados['email'])) {
            throw new \RuntimeException('Token sem identificador ou email.');
        }

        $verificado = $dados['email_verified'] ?? false;
        return [
            'sub'            => (string) $dados['sub'],
            'email'          => mb_strtolower(trim((string) $dados['email'])),
            'email_verified' => $verificado === true || $verificado === 'true',
            'nome'           => trim((string) ($dados['name'] ?? trim(($dados['given_name'] ?? '') . ' ' . ($dados['family_name'] ?? '')))),
            'foto'           => (string) ($dados['picture'] ?? ''),
        ];
    }

    private static function post(string $url, array $campos): array
    {
        $corpo = http_build_query($campos);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $corpo,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            ]);
            $texto  = curl_exec($ch);
            $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro   = curl_error($ch);
            curl_close($ch);
        } else {
            $texto = @file_get_contents($url, false, stream_context_create(['http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                'content'       => $corpo,
                'timeout'       => 15,
                'ignore_errors' => true,
            ]]));
            $codigo = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) ? (int) $m[1] : 0;
            $erro   = $texto === false ? 'sem ligação' : '';
        }

        if ($texto === false || $texto === '') {
            throw new \RuntimeException('Sem ligação ao Google: ' . $erro);
        }
        $json = json_decode($texto, true);
        if ($codigo !== 200 || !is_array($json)) {
            throw new \RuntimeException('Google recusou o código (' . $codigo . '): ' . ($json['error_description'] ?? $json['error'] ?? substr($texto, 0, 200)));
        }
        return $json;
    }

    private static function base64url(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }
}
