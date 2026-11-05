<?php
// =====================================================================
// CarrinhoController — página do carrinho + endpoints AJAX
// Todas as acções POST devolvem o mesmo formato JSON:
// { ok, mensagem?, quantidade, html (gaveta) }
// =====================================================================

namespace App\Controllers;

use Core\Controller;
use App\Models\Carrinho;
use App\Models\ZonaEntrega;

class CarrinhoController extends Controller
{
    private Carrinho $carrinho;

    public function __construct()
    {
        $this->carrinho = new Carrinho();
    }

    public function index(): void
    {
        $itens = $this->carrinho->itens();
        $this->view('carrinho/index', [
            'itens'  => $itens,
            'resumo' => $this->carrinho->resumo($itens),
            'zonas'  => (new ZonaEntrega())->todas(),
            'seo'    => ['titulo' => 'Carrinho', 'noindex' => true],
        ]);
    }

    /** GET /carrinho/mini — conteúdo da gaveta lateral. */
    public function mini(): void
    {
        $this->responder(true);
    }

    public function adicionar(): void
    {
        $erro = $this->carrinho->adicionar((int) ($_POST['produto_id'] ?? 0), (int) ($_POST['quantidade'] ?? 1));
        $this->responder($erro === null || str_starts_with($erro, 'Atingiu'), $erro ?? 'Adicionado ao carrinho.');
    }

    public function actualizar(): void
    {
        $this->carrinho->definirQuantidade((int) ($_POST['produto_id'] ?? 0), (int) ($_POST['quantidade'] ?? 1));
        $this->responder(true);
    }

    public function remover(): void
    {
        $this->carrinho->remover((int) ($_POST['produto_id'] ?? 0));
        $this->responder(true, 'Produto removido.');
    }

    public function aplicarCupao(): void
    {
        $erro = $this->carrinho->aplicarCupao((string) ($_POST['codigo'] ?? ''));
        $this->responder($erro === null, $erro ?? 'Cupão aplicado.');
    }

    public function removerCupao(): void
    {
        $this->carrinho->removerCupao();
        $this->responder(true, 'Cupão removido.');
    }

    private function responder(bool $ok, ?string $mensagem = null): void
    {
        $itens  = $this->carrinho->itens();
        $resumo = $this->carrinho->resumo($itens);
        $this->json([
            'ok'         => $ok,
            'mensagem'   => $mensagem,
            'quantidade' => $resumo['quantidade'],
            'html'       => $this->parcial('partials/carrinho-mini', ['itens' => $itens, 'resumo' => $resumo]),
        ], $ok ? 200 : 422);
    }
}
