<?php
// =====================================================================
// core/Controller.php — Controller base: views, JSON, redirect, SEO
// =====================================================================

namespace Core;

abstract class Controller
{
    /**
     * Renderiza uma view dentro de um layout.
     * $seo: ['titulo','descricao','imagem','canonical','tipo','jsonld'=>[], 'noindex'=>bool]
     */
    protected function view(string $view, array $dados = [], string $layout = 'main'): void
    {
        $dados['seo'] = Seo::montar($dados['seo'] ?? []);
        extract($dados, EXTR_SKIP);

        ob_start();
        require RAIZ . '/app/Views/' . $view . '.php';
        $conteudo = ob_get_clean();

        require RAIZ . '/app/Views/layouts/' . $layout . '.php';
    }

    /** Renderiza um fragmento sem layout (útil para respostas AJAX em HTML). */
    protected function parcial(string $view, array $dados = []): string
    {
        extract($dados, EXTR_SKIP);
        ob_start();
        require RAIZ . '/app/Views/' . $view . '.php';
        return ob_get_clean();
    }

    protected function json(array $dados, int $estado = 200): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirecionar(string $caminho): void
    {
        header('Location: ' . url($caminho));
        exit;
    }
}
