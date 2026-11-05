<?php
// =====================================================================
// CarrinhoController — página do carrinho + endpoints AJAX
// Todas as acções POST devolvem o mesmo formato JSON:
// { ok, mensagem?, quantidade, html (gaveta), adicionado? (painel pós-adição) }
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
        $id   = (int) ($_POST['produto_id'] ?? 0);
        $erro = $this->carrinho->adicionar($id, (int) ($_POST['quantidade'] ?? 1));
        $ok   = $erro === null || str_starts_with($erro, 'Atingiu');
        $this->responder($ok, $erro ?? 'Adicionado ao carrinho.', $ok ? $id : null);
    }

    public function actualizar(): void
    {
        $id = (int) ($_POST['produto_id'] ?? 0);
        $this->carrinho->definirQuantidade($id, (int) ($_POST['quantidade'] ?? 1));
        $this->responder(true, null, $this->painelPedido($id));
    }

    public function remover(): void
    {
        $id = (int) ($_POST['produto_id'] ?? 0);
        $this->carrinho->remover($id);
        $this->responder(true, 'Produto removido.', $this->painelPedido($id));
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

    /** O painel "Adicionado ao carrinho" pede o seu conteúdo actualizado ao mudar a quantidade. */
    private function painelPedido(int $id): ?int
    {
        return !empty($_POST['painel']) ? $id : null;
    }

    /** $destaque: id do produto a mostrar no painel "Adicionado ao carrinho" (null = sem painel). */
    private function responder(bool $ok, ?string $mensagem = null, ?int $destaque = null): void
    {
        $itens  = $this->carrinho->itens();
        $resumo = $this->carrinho->resumo($itens);
        $dados  = [
            'ok'         => $ok,
            'mensagem'   => $mensagem,
            'quantidade' => $resumo['quantidade'],
            'html'       => $this->parcial('partials/carrinho-mini', ['itens' => $itens, 'resumo' => $resumo]),
        ];
        if ($destaque !== null) {
            $item = null;
            foreach ($itens as $i) {
                if ((int) $i['id'] === $destaque) { $item = $i; break; }
            }
            // String vazia = o produto saiu do carrinho; o JS fecha o painel
            $dados['adicionado'] = $item ? $this->parcial('partials/carrinho-adicionado', [
                'item'   => $item,
                'resumo' => $resumo,
                'aviso'  => $mensagem && $mensagem !== 'Adicionado ao carrinho.' && $mensagem !== 'Produto removido.' ? $mensagem : null,
            ]) : '';
        }
        $this->json($dados, $ok ? 200 : 422);
    }
}
