<?php
// =====================================================================
// Admin\ProdutosController — catálogo, stock, imagens e especificações
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;
use Core\HtmlSeguro;
use Core\Upload;
use Core\Validador;
use RuntimeException;

class ProdutosController extends Base
{
    protected ?string $area = 'produtos';

    public function index(): void
    {
        $w = ['1=1']; $p = [];
        if ($q = trim((string) ($_GET['q'] ?? ''))) {
            $w[] = '(p.nome LIKE ? OR p.sku LIKE ?)';
            $like = '%' . addcslashes($q, '%_') . '%';
            array_push($p, $like, $like);
        }
        if ($c = (int) ($_GET['categoria'] ?? 0)) { $w[] = 'p.categoria_id = ?'; $p[] = $c; }
        if ($m = (int) ($_GET['marca'] ?? 0))     { $w[] = 'p.marca_id = ?';     $p[] = $m; }
        $estado = $_GET['estado'] ?? 'activo';
        if (in_array($estado, ['activo', 'rascunho', 'inactivo'], true)) { $w[] = 'p.estado = ?'; $p[] = $estado; }
        if (($_GET['stock'] ?? '') === 'baixo')    { $w[] = 'p.stock <= p.stock_minimo'; }
        if (($_GET['stock'] ?? '') === 'esgotado') { $w[] = 'p.stock = 0'; }
        if (!empty($_GET['promo']))                { $w[] = 'p.preco_anterior > p.preco'; }
        $where = implode(' AND ', $w);

        $ordem = match ($_GET['ordem'] ?? '') {
            'stock'   => 'p.stock ASC',
            'vendas'  => 'p.vendas DESC',
            'preco'   => 'p.preco DESC',
            'nome'    => 'p.nome',
            default   => 'p.actualizado_em DESC',
        };

        [$produtos, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM produtos p WHERE $where",
            "SELECT p.id, p.nome, p.slug, p.sku, p.preco, p.preco_anterior, p.stock, p.stock_minimo, p.imagem,
                    p.estado, p.vendas, p.destaque, p.novidade, c.nome AS categoria, c.slug AS categoria_slug, m.nome AS marca
               FROM produtos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN marcas m ON m.id = p.marca_id
              WHERE $where ORDER BY $ordem",
            $p, 30
        );

        $this->pagina('produtos/lista', [
            'produtos'   => $produtos,
            'pg'         => $pg,
            'categorias' => $this->db()->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll(),
            'marcas'     => $this->db()->query("SELECT id, nome FROM marcas ORDER BY nome")->fetchAll(),
            'estado'     => $estado,
        ], 'Produtos');
    }

    public function novo(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id): void
    {
        $st = $this->db()->prepare("SELECT * FROM produtos WHERE id = ?");
        $st->execute([(int) $id]);
        $produto = $st->fetch() ?: null;
        if (!$produto) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            return;
        }
        $this->formulario($produto);
    }

    private function formulario(?array $produto): void
    {
        $specs = $imagens = [];
        if ($produto) {
            $st = $this->db()->prepare("SELECT grupo, nome, valor FROM produto_especificacoes WHERE produto_id = ? ORDER BY ordem, id");
            $st->execute([$produto['id']]);
            $specs = $st->fetchAll();
            $st = $this->db()->prepare("SELECT * FROM produto_imagens WHERE produto_id = ? ORDER BY ordem, id");
            $st->execute([$produto['id']]);
            $imagens = $st->fetchAll();
        }
        $this->pagina('produtos/form', [
            'produto'    => $produto,
            'specs'      => $specs,
            'imagens'    => $imagens,
            'categorias' => $this->db()->query("SELECT id, nome, slug FROM categorias ORDER BY nome")->fetchAll(),
            'marcas'     => $this->db()->query("SELECT id, nome FROM marcas ORDER BY nome")->fetchAll(),
        ], $produto ? 'Editar ' . $produto['nome'] : 'Novo produto');
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $db = $this->db();
        $voltar = $id ? '/admin/produtos/' . $id : '/admin/produtos/novo';

        $v = (new Validador($_POST))
            ->texto('nome', 200, true, 'Indique o nome do produto.')
            ->texto('sku', 60, true, 'Indique a referência interna (SKU).')
            ->texto('resumo', 300, false)
            ->texto('meta_titulo', 160, false)
            ->texto('meta_descricao', 255, false)
            ->emLista('estado', ['activo', 'rascunho', 'inactivo'], 'Escolha o estado.');

        $num = fn (string $c) => ($x = str_replace([' ', "\u{202F}", ','], ['', '', '.'], (string) ($_POST[$c] ?? ''))) === '' ? null : $x;
        $preco    = $num('preco');
        $anterior = $num('preco_anterior');
        $custo    = $num('custo');
        if (!is_numeric($preco) || $preco <= 0) {
            $v->adicionarErro('preco', 'Indique o preço de venda em Kwanzas.');
        }
        if ($anterior !== null && (!is_numeric($anterior) || $anterior <= $preco)) {
            $v->adicionarErro('preco_anterior', 'O preço anterior tem de ser maior do que o preço actual (ou ficar vazio).');
        }
        foreach (['stock', 'stock_minimo', 'garantia_meses'] as $c) {
            if (!preg_match('/^\d+$/', (string) ($_POST[$c] ?? ''))) {
                $v->adicionarErro($c, 'Use um número inteiro igual ou maior que zero.');
            }
        }

        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $st = $db->prepare("SELECT 1 FROM categorias WHERE id = ?"); $st->execute([$categoriaId]);
        if (!$st->fetchColumn()) {
            $v->adicionarErro('categoria_id', 'Escolha a categoria.');
        }
        $marcaId = (int) ($_POST['marca_id'] ?? 0) ?: null;

        $slug = slugify(trim((string) ($_POST['slug'] ?? '')) ?: (string) ($_POST['nome'] ?? ''));
        $st = $db->prepare("SELECT 1 FROM produtos WHERE slug = ? AND id <> ?"); $st->execute([$slug, $id ?? 0]);
        if ($st->fetchColumn()) {
            $v->adicionarErro('slug', 'Já existe um produto com este endereço. Altere-o (ex.: acrescente a cor ou a capacidade).');
        }
        $st = $db->prepare("SELECT 1 FROM produtos WHERE sku = ? AND id <> ?"); $st->execute([trim((string) ($_POST['sku'] ?? '')), $id ?? 0]);
        if ($st->fetchColumn()) {
            $v->adicionarErro('sku', 'Já existe um produto com este SKU.');
        }

        $ofertaAte = trim((string) ($_POST['oferta_ate'] ?? ''));
        if ($ofertaAte !== '' && !strtotime($ofertaAte)) {
            $v->adicionarErro('oferta_ate', 'Data inválida.');
        }

        if ($v->falhou()) {
            Flash::guardarFormulario($v->erros(), $_POST);
            Flash::definir('erro', 'Corrija os campos assinalados.');
            $this->redirecionar($voltar);
        }

        $d = $v->dados();
        $campos = [
            'categoria_id'   => $categoriaId,
            'marca_id'       => $marcaId,
            'nome'           => $d['nome'],
            'slug'           => $slug,
            'sku'            => strtoupper($d['sku']),
            'resumo'         => $d['resumo'] ?: null,
            'descricao'      => HtmlSeguro::limpar((string) ($_POST['descricao'] ?? '')),
            'preco'          => round((float) $preco, 2),
            'preco_anterior' => $anterior !== null ? round((float) $anterior, 2) : null,
            'custo'          => is_numeric($custo) ? round((float) $custo, 2) : null,
            'stock'          => (int) $_POST['stock'],
            'stock_minimo'   => (int) $_POST['stock_minimo'],
            'garantia_meses' => (int) $_POST['garantia_meses'],
            'destaque'       => !empty($_POST['destaque']) ? 1 : 0,
            'novidade'       => !empty($_POST['novidade']) ? 1 : 0,
            'oferta_ate'     => $ofertaAte !== '' ? date('Y-m-d H:i:s', strtotime($ofertaAte)) : null,
            'estado'         => $d['estado'],
            'meta_titulo'    => $d['meta_titulo'] ?: null,
            'meta_descricao' => $d['meta_descricao'] ?: null,
        ];

        $db->beginTransaction();
        try {
            if ($id) {
                $antigo = $db->prepare("SELECT preco, stock FROM produtos WHERE id = ?"); $antigo->execute([$id]); $antigo = $antigo->fetch();
                $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($campos)));
                $db->prepare("UPDATE produtos SET $sets WHERE id = ?")->execute([...array_values($campos), $id]);
            } else {
                $cols = implode(', ', array_keys($campos));
                $db->prepare("INSERT INTO produtos ($cols) VALUES (" . implode(',', array_fill(0, count($campos), '?')) . ")")->execute(array_values($campos));
                $id = (int) $db->lastInsertId();
            }

            // Especificações: substitui o conjunto
            $db->prepare("DELETE FROM produto_especificacoes WHERE produto_id = ?")->execute([$id]);
            $ins = $db->prepare("INSERT INTO produto_especificacoes (produto_id, grupo, nome, valor, ordem) VALUES (?, ?, ?, ?, ?)");
            foreach ((array) ($_POST['spec_nome'] ?? []) as $i => $nome) {
                $nome = trim(mb_substr((string) $nome, 0, 100));
                $valor = trim(mb_substr((string) ($_POST['spec_valor'][$i] ?? ''), 0, 255));
                if ($nome !== '' && $valor !== '') {
                    $grupo = trim(mb_substr((string) ($_POST['spec_grupo'][$i] ?? ''), 0, 80)) ?: 'Geral';
                    $ins->execute([$id, $grupo, $nome, $valor, $i]);
                }
            }

            // Imagens novas
            $erros = [];
            foreach (Upload::lista($_FILES['imagens'] ?? null) as $f) {
                try {
                    $rel = Upload::imagem($f, 'produtos/uploads', $d['nome']);
                    $ficheiro = substr($rel, strlen('produtos/'));
                    $ordem = (int) $db->query("SELECT COALESCE(MAX(ordem), -1) + 1 FROM produto_imagens WHERE produto_id = $id")->fetchColumn();
                    $db->prepare("INSERT INTO produto_imagens (produto_id, ficheiro, alt, ordem) VALUES (?, ?, ?, ?)")->execute([$id, $ficheiro, $d['nome'], $ordem]);
                } catch (RuntimeException $e) {
                    $erros[] = $e->getMessage();
                }
            }
            // Imagens a remover
            foreach ((array) ($_POST['remover_imagem'] ?? []) as $imgId) {
                $st = $db->prepare("SELECT ficheiro FROM produto_imagens WHERE id = ? AND produto_id = ?");
                $st->execute([(int) $imgId, $id]);
                if ($f = $st->fetchColumn()) {
                    $db->prepare("DELETE FROM produto_imagens WHERE id = ?")->execute([(int) $imgId]);
                    Upload::apagar('produtos/' . $f);
                }
            }
            // A primeira imagem da galeria é a principal
            $principal = $db->query("SELECT ficheiro FROM produto_imagens WHERE produto_id = $id ORDER BY ordem, id LIMIT 1")->fetchColumn();
            $db->prepare("UPDATE produtos SET imagem = ? WHERE id = ?")->execute([$principal ?: null, $id]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $detalhe = isset($antigo) ? sprintf('preço %s → %s; stock %d → %d', $antigo['preco'], $campos['preco'], $antigo['stock'], $campos['stock']) : 'criado';
        AdminAuth::registar(isset($antigo) ? 'editou' : 'criou', 'produto', $id, $detalhe);

        if ($erros) {
            Flash::definir('erro', implode(' ', $erros));
        }
        Flash::definir('ok', 'Produto guardado.');
        $this->redirecionar('/admin/produtos/' . $id);
    }

    /** POST AJAX — actualização rápida de stock a partir da lista. */
    public function stock(string $id): void
    {
        $stock = (string) ($_POST['stock'] ?? '');
        if (!preg_match('/^\d{1,6}$/', $stock)) {
            $this->json(['ok' => false, 'mensagem' => 'Indique um número inteiro.'], 422);
        }
        $st = $this->db()->prepare("SELECT stock, stock_minimo FROM produtos WHERE id = ?");
        $st->execute([(int) $id]);
        $antes = $st->fetch();
        if (!$antes) {
            $this->json(['ok' => false, 'mensagem' => 'Produto não encontrado.'], 404);
        }
        $this->db()->prepare("UPDATE produtos SET stock = ? WHERE id = ?")->execute([(int) $stock, (int) $id]);
        AdminAuth::registar('stock', 'produto', $id, $antes['stock'] . ' → ' . $stock);
        $this->json(['ok' => true, 'mensagem' => 'Stock actualizado.', 'baixo' => (int) $stock <= (int) $antes['stock_minimo']]);
    }
}
