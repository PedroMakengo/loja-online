<?php
// =====================================================================
// Admin\BlogController — artigos: rascunho, publicação imediata ou
// agendada, capa, produtos associados e campos para o Google.
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;
use Core\HtmlSeguro;
use Core\Upload;
use RuntimeException;

class BlogController extends Base
{
    protected ?string $area = 'marketing';

    public function index(): void
    {
        $estado = $_GET['estado'] ?? '';
        $w = match ($estado) {
            'publicado' => "p.estado = 'publicado' AND p.publicado_em <= NOW()",
            'agendado'  => "p.estado = 'publicado' AND p.publicado_em > NOW()",
            'rascunho'  => "p.estado = 'rascunho'",
            default     => '1=1',
        };
        [$posts, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM blog_posts p WHERE $w",
            "SELECT p.id, p.titulo, p.slug, p.estado, p.publicado_em, p.visualizacoes, p.destaque, p.imagem, c.nome AS categoria, c.slug AS categoria_slug, a.nome AS autor
               FROM blog_posts p JOIN blog_categorias c ON c.id = p.categoria_id LEFT JOIN admins a ON a.id = p.autor_id
              WHERE $w ORDER BY COALESCE(p.publicado_em, p.criado_em) DESC",
            []
        );
        $this->pagina('blog/lista', compact('posts', 'pg', 'estado'), 'Blog');
    }

    public function novo(): void
    {
        $this->formulario(null);
    }

    public function editar(string $id): void
    {
        $st = $this->db()->prepare("SELECT * FROM blog_posts WHERE id = ?");
        $st->execute([(int) $id]);
        $post = $st->fetch();
        if (!$post) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            return;
        }
        $this->formulario($post);
    }

    private function formulario(?array $post): void
    {
        $this->pagina('blog/form', [
            'post'       => $post,
            'categorias' => $this->db()->query("SELECT id, nome FROM blog_categorias ORDER BY ordem, nome")->fetchAll(),
            'produtos'   => $this->db()->query("SELECT id, nome, slug FROM produtos WHERE estado = 'activo' ORDER BY nome")->fetchAll(),
        ], $post ? 'Editar artigo' : 'Novo artigo');
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $db = $this->db();
        $t = fn ($c, $max) => trim(preg_replace('/\s+/u', ' ', mb_substr((string) ($_POST[$c] ?? ''), 0, $max)));

        $d = [
            'titulo'         => $t('titulo', 200),
            'slug'           => slugify($t('slug', 220) ?: $t('titulo', 200)),
            'categoria_id'   => (int) ($_POST['categoria_id'] ?? 0),
            'resumo'         => $t('resumo', 300) ?: null,
            'conteudo'       => HtmlSeguro::limpar((string) ($_POST['conteudo'] ?? '')),
            'produtos_ids'   => implode(',', array_slice(array_filter(array_map('intval', (array) ($_POST['produtos_ids'] ?? []))), 0, 8)) ?: null,
            'estado'         => ($_POST['estado'] ?? '') === 'publicado' ? 'publicado' : 'rascunho',
            'destaque'       => !empty($_POST['destaque']) ? 1 : 0,
            'meta_titulo'    => $t('meta_titulo', 160) ?: null,
            'meta_descricao' => $t('meta_descricao', 255) ?: null,
        ];
        $data = trim((string) ($_POST['publicado_em'] ?? ''));
        $d['publicado_em'] = $data !== '' && strtotime($data) ? date('Y-m-d H:i:s', strtotime($data)) : ($d['estado'] === 'publicado' ? date('Y-m-d H:i:s') : null);

        $erros = [];
        if ($d['titulo'] === '') $erros['titulo'] = 'Escreva o título.';
        if (!$d['categoria_id']) $erros['categoria_id'] = 'Escolha a categoria.';
        if ($d['estado'] === 'publicado' && mb_strlen(strip_tags($d['conteudo'])) < 300) $erros['conteudo'] = 'Para publicar, o artigo precisa de pelo menos 300 caracteres de texto. Pode guardar como rascunho.';
        $st = $db->prepare("SELECT 1 FROM blog_posts WHERE slug = ? AND id <> ?"); $st->execute([$d['slug'], $id ?? 0]);
        if ($st->fetchColumn()) $erros['slug'] = 'Já existe um artigo com este endereço.';

        // Produtos citados no texto que não existem: avisa para não publicar cartões em branco
        preg_match_all('#\[produto:([a-z0-9-]+)\]#', $d['conteudo'], $m);
        if ($m[1]) {
            $marc = implode(',', array_fill(0, count($m[1]), '?'));
            $st = $db->prepare("SELECT slug FROM produtos WHERE estado = 'activo' AND slug IN ($marc)"); $st->execute($m[1]);
            $faltam = array_diff(array_unique($m[1]), $st->fetchAll(\PDO::FETCH_COLUMN));
            if ($faltam) $erros['conteudo'] = 'Estes produtos não existem ou não estão à venda: ' . implode(', ', $faltam) . '.';
        }

        if ($erros) {
            Flash::guardarFormulario($erros, $_POST);
            Flash::definir('erro', 'Corrija os campos assinalados.');
            $this->redirecionar($id ? '/admin/blog/' . $id : '/admin/blog/novo');
        }

        // Capa
        $imagemAntiga = null;
        if ($id) {
            $st = $db->prepare("SELECT imagem FROM blog_posts WHERE id = ?"); $st->execute([$id]);
            $imagemAntiga = $st->fetchColumn() ?: null;
        }
        if ($f = Upload::lista($_FILES['imagem'] ?? null)[0] ?? null) {
            try {
                $d['imagem'] = Upload::imagem($f, 'blog/uploads', $d['titulo']);
                Upload::apagar($imagemAntiga);
            } catch (RuntimeException $e) {
                Flash::definir('erro', $e->getMessage());
            }
        } elseif (!empty($_POST['remover_imagem'])) {
            $d['imagem'] = null;
            Upload::apagar($imagemAntiga);
        }

        if ($id) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($d)));
            $db->prepare("UPDATE blog_posts SET $sets WHERE id = ?")->execute([...array_values($d), $id]);
        } else {
            $d['autor_id'] = AdminAuth::id();
            $db->prepare("INSERT INTO blog_posts (" . implode(',', array_keys($d)) . ") VALUES (" . implode(',', array_fill(0, count($d), '?')) . ")")->execute(array_values($d));
            $id = (int) $db->lastInsertId();
        }
        if ($d['destaque']) {
            $db->prepare("UPDATE blog_posts SET destaque = 0, actualizado_em = actualizado_em WHERE id <> ?")->execute([$id]);
        }
        AdminAuth::registar($_POST['id'] ? 'editou' : 'criou', 'artigo', $id, $d['titulo']);

        $agendado = $d['estado'] === 'publicado' && strtotime($d['publicado_em']) > time();
        Flash::definir('ok', match (true) {
            $agendado                   => 'Artigo agendado para ' . date('d/m/Y \à\s H:i', strtotime($d['publicado_em'])) . '.',
            $d['estado'] === 'publicado' => 'Artigo publicado.',
            default                     => 'Rascunho guardado.',
        });
        $this->redirecionar('/admin/blog/' . $id);
    }

    public function apagar(string $id): void
    {
        $st = $this->db()->prepare("SELECT titulo, imagem FROM blog_posts WHERE id = ?");
        $st->execute([(int) $id]);
        if ($p = $st->fetch()) {
            $this->db()->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([(int) $id]);
            Upload::apagar($p['imagem']);
            AdminAuth::registar('apagou', 'artigo', $id, $p['titulo']);
            Flash::definir('ok', 'Artigo apagado.');
        }
        $this->redirecionar('/admin/blog');
    }
}
