<?php
// =====================================================================
// core/Router.php — Router central com parâmetros {nome}
// =====================================================================

namespace Core;

class Router
{
    private array $rotas = ['GET' => [], 'POST' => []];
    private array $semCsrf = [];

    public function get(string $caminho, array $accao): void
    {
        $this->rotas['GET'][$caminho] = $accao;
    }

    /** $csrf = false só para notificações externas (webhooks), que são validadas por assinatura. */
    public function post(string $caminho, array $accao, bool $csrf = true): void
    {
        $this->rotas['POST'][$caminho] = $accao;
        if (!$csrf) {
            $this->semCsrf[$caminho] = true;
        }
    }

    public function despachar(string $uri, string $metodo): void
    {
        $caminho = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Remove a subpasta (se o projecto não estiver na raiz do domínio)
        if (BASE_PATH !== '' && str_starts_with($caminho, BASE_PATH)) {
            $caminho = substr($caminho, strlen(BASE_PATH)) ?: '/';
        }
        $caminho = '/' . trim(rawurldecode($caminho), '/');

        foreach ($this->rotas[$metodo] ?? [] as $padrao => $accao) {
            $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $padrao) . '$#u';
            if (preg_match($regex, $caminho, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                [$classe, $metodoAccao] = $accao;

                if ($metodo === 'POST' && empty($this->semCsrf[$padrao])) {
                    Csrf::verificar();
                }

                (new $classe())->$metodoAccao(...array_values($params));
                return;
            }
        }

        (new \App\Controllers\ErroController())->naoEncontrado();
    }
}
