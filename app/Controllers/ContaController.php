<?php
// =====================================================================
// ContaController — área "A minha conta" (exige sessão)
// =====================================================================

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Flash;
use Core\Validador;
use App\Models\Cliente;
use App\Models\Endereco;
use App\Models\Favorito;
use App\Models\Pedido;
use App\Models\ZonaEntrega;
use App\Models\Avaliacao;
use App\Servicos\Fidelidade;

class ContaController extends Controller
{
    private int $clienteId;

    public function __construct()
    {
        Auth::exigir();
        $this->clienteId = (int) Auth::id();
    }

    public function painel(): void
    {
        $this->view('conta/painel', [
            'resumo'    => (new Cliente())->resumo($this->clienteId),
            'pedidos'   => (new Pedido())->doCliente($this->clienteId, 3),
            'endereco'  => (new Endereco())->principal($this->clienteId),
            'favoritos' => array_slice((new Favorito())->produtos($this->clienteId), 0, 4),
            'porAvaliar'=> array_slice((new Avaliacao())->porAvaliar($this->clienteId), 0, 4),
            'seccao'    => 'painel',
            'seo'       => ['titulo' => 'A minha conta', 'noindex' => true],
        ]);
    }

    // ----- Dados pessoais e senha -------------------------------------------

    public function dados(): void
    {
        $this->view('conta/dados', [
            'cliente' => Auth::cliente(),
            'seccao'  => 'dados',
            'seo'     => ['titulo' => 'Dados pessoais', 'noindex' => true],
        ]);
    }

    public function guardarDados(): void
    {
        $modelo = new Cliente();
        $v = (new Validador($_POST))
            ->nome('nome')
            ->email('email')
            ->telefone('telefone')
            ->nif('nif')
            ->booleano('aceita_marketing')
            ->booleano('recebe_lembretes');

        if (!$v->falhou() && $modelo->emailEmUso($v->dados()['email'], $this->clienteId)) {
            $v->adicionarErro('email', 'Este email já pertence a outra conta.');
        }
        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            $this->redirecionar('/conta/dados');
        }

        $modelo->actualizarDados($this->clienteId, $v->dados());
        Flash::definir('ok', 'Dados guardados.');
        $this->redirecionar('/conta/dados');
    }

    public function alterarSenha(): void
    {
        $modelo = new Cliente();
        $v = (new Validador($_POST))->senha('senha', 'senha_confirmacao');

        if (!$modelo->senhaCorrecta($this->clienteId, (string) ($_POST['senha_actual'] ?? ''))) {
            $v->adicionarErro('senha_actual', 'A senha actual não está correcta.');
        }
        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), []);
            $this->redirecionar('/conta/dados#senha');
        }

        $modelo->alterarSenha($this->clienteId, $v->dados()['senha']);
        Auth::entrar($this->clienteId); // nova sessão
        Flash::definir('ok', 'Senha alterada. As sessões noutros dispositivos foram terminadas.');
        $this->redirecionar('/conta/dados');
    }

    // ----- Moradas ---------------------------------------------------------------

    public function enderecos(): void
    {
        $modelo  = new Endereco();
        $editar  = isset($_GET['editar']) ? $modelo->doClientePorId($this->clienteId, (int) $_GET['editar']) : null;

        $this->view('conta/enderecos', [
            'enderecos'  => $modelo->doCliente($this->clienteId),
            'editar'     => $editar,
            'novo'       => isset($_GET['novo']) || $editar !== null,
            'provincias' => (new ZonaEntrega())->provincias(),
            'maximo'     => Endereco::MAXIMO,
            'cliente'    => Auth::cliente(),
            'seccao'     => 'enderecos',
            'seo'        => ['titulo' => 'Moradas', 'noindex' => true],
        ]);
    }

    public function guardarEndereco(): void
    {
        $modelo = new Endereco();
        $id     = (int) ($_POST['id'] ?? 0) ?: null;

        if ($id && !$modelo->doClientePorId($this->clienteId, $id)) {
            $this->redirecionar('/conta/enderecos');
        }
        if (!$id && $modelo->contar($this->clienteId) >= Endereco::MAXIMO) {
            Flash::definir('erro', 'Atingiu o limite de ' . Endereco::MAXIMO . ' moradas. Remova uma para adicionar outra.');
            $this->redirecionar('/conta/enderecos');
        }

        $v = (new Validador($_POST))
            ->texto('nome_destinatario', 140, true, 'Indique quem recebe a encomenda.')
            ->telefone('telefone')
            ->emLista('provincia', (new ZonaEntrega())->provincias(), 'Escolha a província.')
            ->texto('municipio', 80, true, 'Indique o município. Exemplo: Talatona')
            ->texto('bairro', 120, true, 'Indique o bairro.')
            ->texto('rua', 200, false)
            ->texto('referencia', 255, false)
            ->booleano('principal');

        $voltar = (string) ($_POST['voltar'] ?? '');
        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            $this->redirecionar('/conta/enderecos?' . ($id ? 'editar=' . $id : 'novo=1'));
        }

        $modelo->guardar($this->clienteId, $v->dados(), $id);
        Flash::definir('ok', $id ? 'Morada actualizada.' : 'Morada adicionada.');
        $this->redirecionar(caminhoSeguro($voltar, '/conta/enderecos'));
    }

    public function removerEndereco(string $id): void
    {
        (new Endereco())->remover($this->clienteId, (int) $id);
        Flash::definir('ok', 'Morada removida.');
        $this->redirecionar('/conta/enderecos');
    }

    public function enderecoPrincipal(string $id): void
    {
        $modelo = new Endereco();
        if ($modelo->doClientePorId($this->clienteId, (int) $id)) {
            $modelo->definirPrincipal($this->clienteId, (int) $id);
            Flash::definir('ok', 'Morada principal alterada.');
        }
        $this->redirecionar('/conta/enderecos');
    }

    // ----- Pedidos e favoritos -----------------------------------------------------

    public function pedidos(): void
    {
        $this->view('conta/pedidos', [
            'pedidos' => (new Pedido())->doCliente($this->clienteId),
            'seccao'  => 'pedidos',
            'seo'     => ['titulo' => 'Os meus pedidos', 'noindex' => true],
        ]);
    }

    public function pontos(): void
    {
        $cliente = Auth::cliente();
        $this->view('conta/pontos', [
            'saldo'     => (int) $cliente['pontos'],
            'historico' => Fidelidade::historico($this->clienteId),
            'regras'    => Fidelidade::cfg(),
            'porAvaliar'=> (new Avaliacao())->porAvaliar($this->clienteId),
            'seccao'    => 'pontos',
            'seo'       => ['titulo' => 'Os meus pontos', 'noindex' => true],
        ]);
    }

    public function favoritos(): void
    {
        $this->view('conta/favoritos', [
            'produtos' => (new Favorito())->produtos($this->clienteId),
            'seccao'   => 'favoritos',
            'seo'      => ['titulo' => 'Favoritos', 'noindex' => true],
        ]);
    }
}
