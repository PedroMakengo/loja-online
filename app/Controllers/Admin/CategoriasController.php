<?php
// =====================================================================
// Admin\CategoriasController — gestão da árvore de categorias:
// categoria › subcategoria › sub-subcategoria (máx. Categoria::NIVEIS).
// Regras: sem ciclos, sem passar o limite de níveis, slug único, e não
// se apaga uma categoria com subcategorias ou produtos (mover primeiro).
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;
use Core\Validador;
use App\Models\Categoria;

class CategoriasController extends Base
{
    protected ?string $area = 'catalogo';

    public function index(): void
    {
        $arvore = (new Categoria())->arvoreAdmin();
        $q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
        if ($q !== '') {
            $arvore = array_values(array_filter($arvore, fn ($c) => str_contains(mb_strtolower($c['nome']), $q)));
        }
        $this->pagina('categorias/lista', ['categorias' => $arvore, 'q' => $q], 'Categorias');
    }

    public function novo(): void
    {
        $pai = (int) ($_GET['pai'] ?? 0) ?: null;
        $this->form(null, $pai);
    }

    public function editar(string $id): void
    {
        $st = $this->db()->prepare("SELECT * FROM categorias WHERE id = ?");
        $st->execute([(int) $id]);
        $registo = $st->fetch();
        if (!$registo) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            return;
        }
        $this->form($registo, $registo['parent_id'] !== null ? (int) $registo['parent_id'] : null);
    }

    public function guardar(): void
    {
        $modelo = new Categoria();
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $_POST['slug'] = slugify(trim((string) ($_POST['slug'] ?? '')) ?: (string) ($_POST['nome'] ?? ''));

        $v = (new Validador($_POST))
            ->texto('nome', 120, true, 'Indique o nome.')
            ->texto('descricao', 255, false)
            ->texto('meta_titulo', 160, false)
            ->texto('meta_descricao', 255, false);

        foreach (['nome', 'descricao', 'meta_titulo', 'meta_descricao'] as $campo) {
            if (!mb_check_encoding((string) ($_POST[$campo] ?? ''), 'UTF-8')) {
                $_POST[$campo] = '';
                $v->adicionarErro($campo, 'O texto tem caracteres inválidos. Escreva-o de novo.');
            }
        }

        $slug = $_POST['slug'];
        if ($slug === '') {
            $v->adicionarErro('slug', 'Indique o endereço.');
        } else {
            $st = $this->db()->prepare("SELECT 1 FROM categorias WHERE slug = ? AND id <> ?");
            $st->execute([$slug, $id ?? 0]);
            if ($st->fetchColumn()) {
                $v->adicionarErro('slug', 'Já existe uma categoria com este endereço.');
            }
        }

        $ordem = trim((string) ($_POST['ordem'] ?? ''));
        if ($ordem !== '' && !preg_match('/^-?\d{1,4}$/', $ordem)) {
            $v->adicionarErro('ordem', 'Use um número inteiro.');
        }

        // Categoria-mãe: existe, não é a própria nem descendente, e não passa o limite de níveis
        $pai = (int) ($_POST['parent_id'] ?? 0) ?: null;
        if ($pai !== null) {
            $existe = $this->db()->prepare("SELECT 1 FROM categorias WHERE id = ?");
            $existe->execute([$pai]);
            if (!$existe->fetchColumn()) {
                $v->adicionarErro('parent_id', 'A categoria-mãe escolhida não existe.');
            } elseif ($id && in_array($pai, $modelo->descendentes($id), true)) {
                $v->adicionarErro('parent_id', 'Não pode ficar dentro de si própria nem de uma das suas subcategorias.');
            } else {
                $altura = $id ? $modelo->alturaDoRamo($id) : 1;
                if ($modelo->nivel($pai) + $altura > Categoria::NIVEIS) {
                    $v->adicionarErro('parent_id', 'Assim passaria de ' . Categoria::NIVEIS . ' níveis (categoria › subcategoria › sub-subcategoria). Escolha uma categoria mais acima.');
                }
            }
        }

        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            Flash::definir('erro', 'Corrija os campos assinalados.');
            $this->redirecionar('/admin/categorias/' . ($id ?: 'novo' . ($pai ? '?pai=' . $pai : '')));
        }

        $dados = [
            'parent_id'      => $pai,
            'nome'           => preg_replace('/\s+/u', ' ', trim((string) $_POST['nome'])),
            'slug'           => $slug,
            'descricao'      => trim((string) ($_POST['descricao'] ?? '')) ?: null,
            'ordem'          => (int) $ordem,
            'destaque'       => !empty($_POST['destaque']) ? 1 : 0,
            'activo'         => !empty($_POST['activo']) ? 1 : 0,
            'meta_titulo'    => trim((string) ($_POST['meta_titulo'] ?? '')) ?: null,
            'meta_descricao' => trim((string) ($_POST['meta_descricao'] ?? '')) ?: null,
        ];

        if ($id) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($dados)));
            $this->db()->prepare("UPDATE categorias SET $sets WHERE id = ?")->execute([...array_values($dados), $id]);
        } else {
            $cols = implode(', ', array_keys($dados));
            $this->db()->prepare("INSERT INTO categorias ($cols) VALUES (" . implode(',', array_fill(0, count($dados), '?')) . ")")
                 ->execute(array_values($dados));
            $id = (int) $this->db()->lastInsertId();
        }

        AdminAuth::registar(!empty($_POST['id']) ? 'editou' : 'criou', 'categorias', $id, $dados['nome']);
        $tipo = Categoria::NOMES_NIVEL[$pai ? $modelo->nivel($pai) + 1 : 1] ?? 'Categoria';
        Flash::definir('ok', $tipo . ' "' . $dados['nome'] . '" guardada.'
            . (!$dados['activo'] ? ' Está inactiva: não aparece na loja (nem as suas subcategorias).' : ''));
        $this->redirecionar('/admin/categorias#cat-' . $id);
    }

    public function apagar(string $id): void
    {
        $id = (int) $id;
        $db = $this->db();
        $filhos = (int) $db->query("SELECT COUNT(*) FROM categorias WHERE parent_id = $id")->fetchColumn();
        $produtos = (int) $db->query("SELECT COUNT(*) FROM produtos WHERE categoria_id = $id")->fetchColumn();

        if ($filhos > 0) {
            Flash::definir('erro', "Não é possível apagar: tem $filhos subcategoria(s). Apague-as ou mova-as para outra categoria primeiro.");
        } elseif ($produtos > 0) {
            Flash::definir('erro', "Não é possível apagar: tem $produtos produto(s). Mova-os para outra categoria (botão \"Mover produtos\") ou desactive-a.");
        } else {
            $nome = (string) $db->query("SELECT nome FROM categorias WHERE id = $id")->fetchColumn();
            $db->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
            AdminAuth::registar('apagou', 'categorias', $id, $nome);
            Flash::definir('ok', 'Categoria "' . $nome . '" apagada.');
        }
        $this->redirecionar('/admin/categorias');
    }

    /** Move todos os produtos de uma categoria para outra (ex.: antes de apagar ou ao reorganizar). */
    public function moverProdutos(string $id): void
    {
        $origem = (int) $id;
        $destino = (int) ($_POST['destino'] ?? 0);
        $st = $this->db()->prepare("SELECT nome FROM categorias WHERE id = ?");
        $st->execute([$destino]);
        $nomeDestino = $st->fetchColumn();

        if (!$nomeDestino || $destino === $origem) {
            Flash::definir('erro', 'Escolha a categoria de destino.');
        } else {
            $upd = $this->db()->prepare("UPDATE produtos SET categoria_id = ? WHERE categoria_id = ?");
            $upd->execute([$destino, $origem]);
            AdminAuth::registar('moveu_produtos', 'categorias', $origem, $upd->rowCount() . ' produto(s) para ' . $nomeDestino);
            Flash::definir('ok', $upd->rowCount() . ' produto(s) movido(s) para "' . $nomeDestino . '".');
        }
        $this->redirecionar('/admin/categorias/' . $origem);
    }

    // ---------------------------------------------------------------------

    private function form(?array $registo, ?int $pai): void
    {
        $modelo = new Categoria();
        $arvore = $modelo->arvoreAdmin();

        // Mães possíveis: não a própria nem descendentes, e que deixem o ramo dentro do limite de níveis
        $excluir = $registo ? $modelo->descendentes((int) $registo['id']) : [];
        $altura  = $registo ? $modelo->alturaDoRamo((int) $registo['id']) : 1;
        $maes = array_filter($arvore, fn ($c) => !in_array((int) $c['id'], $excluir, true) && $c['nivel'] + $altura <= Categoria::NIVEIS);

        $produtos = 0;
        if ($registo) {
            $st = $this->db()->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id = ?");
            $st->execute([$registo['id']]);
            $produtos = (int) $st->fetchColumn();
        }

        $this->pagina('categorias/form', [
            'registo'   => $registo,
            'pai'       => $pai,
            'maes'      => Categoria::opcoesIndentadas($maes),
            'destinos'  => Categoria::opcoesIndentadas(array_filter($arvore, fn ($c) => !$registo || (int) $c['id'] !== (int) $registo['id'])),
            'produtos'  => $produtos,
            'filhas'    => $registo ? array_values(array_filter($arvore, fn ($c) => (int) $c['parent_id'] === (int) $registo['id'])) : [],
            'caminho'   => $pai ? array_column($this->caminhoAdmin($pai), 'nome') : [],
            'podeTerFilhas' => !$registo || $modelo->nivel((int) $registo['id']) < Categoria::NIVEIS,
        ], $registo ? 'Editar categoria' : 'Nova categoria');
    }

    /** Caminho (inclui inactivas) da raiz até $id. */
    private function caminhoAdmin(int $id): array
    {
        $caminho = [];
        $st = $this->db()->prepare("SELECT id, nome, parent_id FROM categorias WHERE id = ?");
        while ($id && count($caminho) <= Categoria::NIVEIS) {
            $st->execute([$id]);
            if (!$c = $st->fetch()) {
                break;
            }
            array_unshift($caminho, $c);
            $id = (int) $c['parent_id'];
        }
        return $caminho;
    }
}
