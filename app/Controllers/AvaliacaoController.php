<?php
// =====================================================================
// AvaliacaoController — publicar uma avaliação (só compradores com o
// pedido entregue) e carregar mais avaliações por AJAX
// =====================================================================

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Flash;
use App\Models\Avaliacao;

class AvaliacaoController extends Controller
{
    public function criar(): void
    {
        Auth::exigir();
        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        $st = Database::ligacao()->prepare("SELECT slug FROM produtos WHERE id = ?");
        $st->execute([$produtoId]);
        $slug = $st->fetchColumn();
        if (!$slug) {
            $this->redirecionar('/');
        }
        $voltar = '/produto/' . $slug . '#avaliacoes';

        $modelo = new Avaliacao();
        $eleg = $modelo->elegibilidade((int) Auth::id(), $produtoId);
        if ($eleg['existente']) {
            Flash::definir('erro', 'Já avaliou este produto. Obrigado!');
            $this->redirecionar($voltar);
        }
        if (!$eleg['pedido_id']) {
            Flash::definir('erro', 'Só pode avaliar produtos que comprou e já recebeu.');
            $this->redirecionar($voltar);
        }

        $nota       = (int) ($_POST['nota'] ?? 0);
        $titulo     = trim(preg_replace('/\s+/u', ' ', mb_substr((string) ($_POST['titulo'] ?? ''), 0, 140)));
        $comentario = trim(mb_substr((string) ($_POST['comentario'] ?? ''), 0, 2000));
        $erros = [];
        if ($nota < 1 || $nota > 5) {
            $erros['nota'] = 'Escolha de 1 a 5 estrelas.';
        }
        if ($comentario !== '' && mb_strlen($comentario) < 10) {
            $erros['comentario'] = 'Escreva pelo menos uma frase, ou deixe o campo vazio.';
        }
        if ($erros) {
            Flash::guardarFormulario($erros, $_POST);
            $this->redirecionar('/produto/' . $slug . '#avaliar');
        }

        $modelo->criar((int) Auth::id(), $produtoId, $eleg['pedido_id'], $nota, $titulo, $comentario);
        Flash::definir('ok', 'Obrigado pela avaliação! Fica publicada depois de revista pela equipa, normalmente em 24 horas.');
        $this->redirecionar($voltar);
    }

    /** GET /produto/{slug}/avaliacoes?pagina=2&ordem=melhores — HTML para "ver mais". */
    public function mais(string $slug): void
    {
        $st = Database::ligacao()->prepare("SELECT id FROM produtos WHERE slug = ? AND estado = 'activo'");
        $st->execute([$slug]);
        $id = (int) $st->fetchColumn();
        if (!$id) {
            (new ErroController())->naoEncontrado();
            return;
        }
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $ordem  = in_array($_GET['ordem'] ?? '', ['recentes', 'melhores', 'piores'], true) ? $_GET['ordem'] : 'recentes';
        $porPagina = 6;
        $lista = (new Avaliacao())->aprovadas($id, $ordem, $porPagina + 1, ($pagina - 1) * $porPagina);
        $temMais = count($lista) > $porPagina;

        $this->json([
            'html'    => $this->parcial('produto/_avaliacoes-lista', ['avaliacoes' => array_slice($lista, 0, $porPagina)]),
            'temMais' => $temMais,
        ]);
    }
}
