<?php
// =====================================================================
// CheckoutController — página única de checkout (morada, entrega,
// pagamento, NIF) com recálculo AJAX e confirmação transaccional
// =====================================================================

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Flash;
use Core\Validador;
use App\Models\Carrinho;
use App\Servicos\Checkout;
use App\Servicos\Pagamentos;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct()
    {
        Auth::exigir();
    }

    public function index(): void
    {
        Pagamentos::expirarPendentes();

        if ((new Carrinho())->contar() === 0) {
            Flash::definir('ok', 'O carrinho está vazio. Adicione produtos para finalizar a compra.');
            $this->redirecionar('/carrinho');
        }

        $antigo = Flash::antigo();
        $dados = Checkout::montar(
            (int) Auth::id(),
            isset($antigo['endereco_id']) ? (int) $antigo['endereco_id'] : (isset($_GET['morada']) ? (int) $_GET['morada'] : null),
            $antigo['metodo_entrega'] ?? null,
            !empty($antigo['usar_pontos'])
        );

        // Chave de idempotência: um formulário = no máximo um pedido
        $_SESSION['checkout_chave'] ??= bin2hex(random_bytes(16));

        $this->view('checkout/index', $dados + [
            'cliente'   => Auth::cliente(),
            'metodos'   => Pagamentos::disponiveis(),
            'chave'     => $_SESSION['checkout_chave'],
            'antigo'    => $antigo,
            'seo'       => ['titulo' => 'Finalizar compra', 'noindex' => true],
        ], 'checkout');
    }

    /** POST AJAX — recalcula entrega e totais quando muda a morada ou o tipo de entrega. */
    public function calcular(): void
    {
        $d = Checkout::montar(
            (int) Auth::id(),
            (int) ($_POST['endereco_id'] ?? 0) ?: null,
            (string) ($_POST['metodo_entrega'] ?? ''),
            !empty($_POST['usar_pontos'])
        );
        $this->json([
            'ok'      => true,
            'entrega' => $this->parcial('checkout/_entrega', $d),
            'resumo'  => $this->parcial('checkout/_resumo', $d),
        ]);
    }

    public function confirmar(): void
    {
        $cliente = Auth::cliente();
        $metodos = Pagamentos::disponiveis();

        $v = (new Validador($_POST))
            ->emLista('metodo_pagamento', array_keys($metodos), 'Escolha uma forma de pagamento.')
            ->emLista('metodo_entrega', ['normal', 'expressa', 'levantamento'], 'Escolha como quer receber a encomenda.')
            ->nif('nif_factura')
            ->texto('notas', 500, false);

        $metodo = $v->dados()['metodo_pagamento'] ?? null;
        if ($metodo && $metodos[$metodo]['pede_telefone']) {
            $v->telefone('telefone_pagamento');
        }
        if (($v->dados()['metodo_entrega'] ?? '') !== 'levantamento' && empty($_POST['endereco_id'])) {
            $v->adicionarErro('endereco_id', 'Escolha a morada de entrega ou opte por levantar na loja.');
        }
        if (empty($_POST['aceita_termos'])) {
            $v->adicionarErro('aceita_termos', 'Para concluir, aceite os termos e condições.');
        }
        $chave = (string) ($_POST['chave'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $chave)) {
            $v->adicionarErro('metodo_pagamento', 'O formulário expirou. Confirme os dados e tente de novo.');
        }

        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            $this->redirecionar('/checkout');
        }

        $d = $v->dados();
        try {
            $numero = Checkout::criarPedido($cliente, [
                'endereco_id'        => (int) ($_POST['endereco_id'] ?? 0),
                'metodo_entrega'     => $d['metodo_entrega'],
                'metodo_pagamento'   => $d['metodo_pagamento'],
                'telefone_pagamento' => $d['telefone_pagamento'] ?? null,
                'nif_factura'        => $d['nif_factura'] ?? '',
                'notas'              => $d['notas'] ?? '',
                'chave'              => $chave,
                'usar_pontos'        => !empty($_POST['usar_pontos']),
            ]);
        } catch (RuntimeException $e) {
            Flash::guardarFormulario([], $_POST);
            Flash::definir('erro', $e->getMessage());
            $this->redirecionar('/checkout');
        }

        unset($_SESSION['checkout_chave']);
        Flash::definir('ok', 'Pedido ' . $numero . ' criado. Falta só o pagamento.');
        $this->redirecionar('/conta/pedidos/' . $numero);
    }
}
