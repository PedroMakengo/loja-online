<?php
// =====================================================================
// Admin\MarketingController — carrinhos abandonados e newsletter
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Email;
use Core\Flash;
use App\Servicos\Lembretes;
use App\Servicos\Marketing;
use App\Servicos\Newsletter;

class MarketingController extends Base
{
    protected ?string $area = 'marketing';

    // ----- Carrinhos abandonados ------------------------------------------

    public function carrinhos(): void
    {
        [$carrinhos, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM carrinhos c WHERE c.convertido = 0 AND c.actualizado_em < NOW() - INTERVAL 1 HOUR
                AND c.actualizado_em > NOW() - INTERVAL 30 DAY AND EXISTS (SELECT 1 FROM carrinho_itens WHERE carrinho_id = c.id)",
            "SELECT c.id, c.actualizado_em, c.lembretes_enviados, c.recuperacao_enviada_em, c.recuperado_em,
                    cl.id AS cliente_id, cl.nome, cl.telefone, cl.recebe_lembretes,
                    (SELECT SUM(ci.quantidade * p.preco) FROM carrinho_itens ci JOIN produtos p ON p.id = ci.produto_id WHERE ci.carrinho_id = c.id) AS valor,
                    (SELECT GROUP_CONCAT(CONCAT(ci.quantidade, '× ', p.nome) SEPARATOR ', ') FROM carrinho_itens ci JOIN produtos p ON p.id = ci.produto_id WHERE ci.carrinho_id = c.id) AS itens
               FROM carrinhos c LEFT JOIN clientes cl ON cl.id = c.cliente_id
              WHERE c.convertido = 0 AND c.actualizado_em < NOW() - INTERVAL 1 HOUR
                AND c.actualizado_em > NOW() - INTERVAL 30 DAY AND EXISTS (SELECT 1 FROM carrinho_itens WHERE carrinho_id = c.id)
              ORDER BY valor DESC",
            []
        );
        $valorEmAberto = (float) $this->db()->query(
            "SELECT COALESCE(SUM(ci.quantidade * p.preco), 0) FROM carrinhos c
               JOIN carrinho_itens ci ON ci.carrinho_id = c.id JOIN produtos p ON p.id = ci.produto_id
              WHERE c.convertido = 0 AND c.actualizado_em < NOW() - INTERVAL 1 HOUR AND c.actualizado_em > NOW() - INTERVAL 30 DAY"
        )->fetchColumn();

        $this->pagina('marketing/carrinhos', [
            'carrinhos'     => $carrinhos,
            'pg'            => $pg,
            'stats'         => Lembretes::estatisticas(30),
            'valorEmAberto' => $valorEmAberto,
            'cfg'           => Marketing::cfg(),
        ], 'Carrinhos abandonados');
    }

    public function processarLembretes(): void
    {
        $r = Lembretes::processar();
        AdminAuth::registar('lembretes_manual', 'carrinhos', null, json_encode($r));
        Flash::definir('ok', sprintf('%s. %s.', plural($r['primeiro'] + $r['segundo'], 'lembrete enviado', 'lembretes enviados'), plural($r['ignorados'], 'carrinho ignorado (valor baixo ou sem stock)', 'carrinhos ignorados (valor baixo ou sem stock)')));
        $this->redirecionar('/admin/marketing/carrinhos');
    }

    // ----- Newsletter ----------------------------------------------------------

    public function newsletter(): void
    {
        $this->pagina('marketing/newsletter', [
            'campanhas'    => $this->db()->query("SELECT * FROM newsletter_campanhas ORDER BY criado_em DESC LIMIT 50")->fetchAll(),
            'destinatarios'=> count(Newsletter::destinatarios()),
            'subscritores' => $this->db()->query("SELECT estado, COUNT(*) FROM newsletter GROUP BY estado")->fetchAll(\PDO::FETCH_KEY_PAIR),
            'clientesAceitam' => (int) $this->db()->query("SELECT COUNT(*) FROM clientes WHERE aceita_marketing = 1 AND estado = 'activo'")->fetchColumn(),
            'pendentes'    => (int) $this->db()->query("SELECT COUNT(*) FROM newsletter_envios WHERE estado = 'pendente'")->fetchColumn(),
            'cfg'          => Marketing::cfg(),
        ], 'Newsletter');
    }

    public function campanha(?string $id = null): void
    {
        $c = $id ? $this->obter((int) $id) : null;
        $this->pagina('marketing/campanha', [
            'c'        => $c,
            'produtos' => $this->db()->query("SELECT id, nome, sku FROM produtos WHERE estado = 'activo' ORDER BY nome")->fetchAll(),
            'destinatarios' => count(Newsletter::destinatarios()),
        ], $c ? $c['assunto'] : 'Nova campanha');
    }

    public function editar(string $id): void
    {
        $this->campanha($id);
    }

    public function guardar(): void
    {
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        if ($id && $this->obter($id)['estado'] !== 'rascunho') {
            Flash::definir('erro', 'Uma campanha já enviada não pode ser alterada. Duplique-a para enviar outra.');
            $this->redirecionar('/admin/newsletter/' . $id);
        }
        $t = fn ($c, $max) => trim(mb_substr((string) ($_POST[$c] ?? ''), 0, $max));
        $d = [
            'assunto'       => $t('assunto', 150),
            'pre_cabecalho' => $t('pre_cabecalho', 150) ?: null,
            'titulo'        => $t('titulo', 150),
            'texto'         => $t('texto', 5000) ?: null,
            'fonte'         => in_array($_POST['fonte'] ?? '', ['promocoes', 'novidades', 'mais_vendidos', 'escolhidos', 'nenhum'], true) ? $_POST['fonte'] : 'promocoes',
            'produtos_ids'  => implode(',', array_slice(array_filter(array_map('intval', (array) ($_POST['produtos_ids'] ?? []))), 0, 6)) ?: null,
            'botao_texto'   => $t('botao_texto', 60) ?: null,
            'botao_link'    => $t('botao_link', 255) ?: null,
        ];
        $erros = [];
        if ($d['assunto'] === '') $erros['assunto'] = 'Escreva o assunto do email.';
        if ($d['titulo'] === '')  $erros['titulo'] = 'Escreva o título.';
        if ($d['fonte'] === 'escolhidos' && !$d['produtos_ids']) $erros['produtos_ids'] = 'Escolha pelo menos um produto.';
        if ($d['botao_link'] && !preg_match('#^(/|https?://)#', $d['botao_link'])) $erros['botao_link'] = 'Use um caminho da loja (ex.: /promocoes) ou um endereço completo.';
        if ($erros) {
            Flash::guardarFormulario($erros, $_POST);
            Flash::definir('erro', 'Corrija os campos assinalados.');
            $this->redirecionar($id ? '/admin/newsletter/' . $id : '/admin/newsletter/nova');
        }

        if ($id) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($d)));
            $this->db()->prepare("UPDATE newsletter_campanhas SET $sets WHERE id = ?")->execute([...array_values($d), $id]);
        } else {
            $d['criado_por'] = AdminAuth::id();
            $this->db()->prepare("INSERT INTO newsletter_campanhas (" . implode(',', array_keys($d)) . ") VALUES (" . implode(',', array_fill(0, count($d), '?')) . ")")->execute(array_values($d));
            $id = (int) $this->db()->lastInsertId();
        }
        Flash::definir('ok', 'Campanha guardada. Veja a pré-visualização e envie um teste antes de enviar a todos.');
        $this->redirecionar('/admin/newsletter/' . $id);
    }

    /** HTML completo do email, para o iframe de pré-visualização. */
    public function previsualizar(string $id): void
    {
        $c = $this->obter((int) $id);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: SAMEORIGIN');
        echo Email::modelo($c['assunto'], Newsletter::corpo($c), $c['pre_cabecalho'], Marketing::linkSairNewsletter('exemplo@exemplo.ao'));
        exit;
    }

    public function teste(string $id): void
    {
        $c = $this->obter((int) $id);
        $para = AdminAuth::admin()['email'];
        Email::enviar($para, '[TESTE] ' . $c['assunto'], Newsletter::corpo($c), ['pre' => $c['pre_cabecalho'], 'cancelar' => Marketing::linkSairNewsletter($para)]);
        Flash::definir('ok', 'Email de teste enviado para ' . $para . '.');
        $this->redirecionar('/admin/newsletter/' . $id);
    }

    public function enviar(string $id): void
    {
        $c = $this->obter((int) $id);
        if ($c['estado'] !== 'rascunho') {
            $this->redirecionar('/admin/newsletter/' . $id);
        }
        if (empty($_POST['confirmo'])) {
            Flash::definir('erro', 'Confirme que reviu a campanha e enviou um teste.');
            $this->redirecionar('/admin/newsletter/' . $id);
        }
        $n = Newsletter::enfileirar((int) $id);
        $this->db()->prepare("UPDATE newsletter_campanhas SET enviada_em = NULL WHERE id = ?")->execute([$id]);
        AdminAuth::registar('enviou_campanha', 'newsletter', $id, $n . ' destinatários');
        $enviados = Newsletter::processarLote();
        Flash::definir('ok', "Campanha em envio para " . plural($n, 'destinatário', 'destinatários') . ". Já saíram $enviados; o resto sai em lotes automáticos (até " . Marketing::cfg()['limite_por_hora'] . " por hora).");
        $this->redirecionar('/admin/newsletter/' . $id);
    }

    public function processarFila(): void
    {
        $n = Newsletter::processarLote();
        Flash::definir('ok', $n ? plural($n, 'email enviado', 'emails enviados') . '.' : 'Nada enviado: a fila está vazia ou o limite por hora foi atingido.');
        $this->redirecionar('/admin/newsletter');
    }

    public function duplicar(string $id): void
    {
        $c = $this->obter((int) $id);
        $this->db()->prepare(
            "INSERT INTO newsletter_campanhas (assunto, pre_cabecalho, titulo, texto, fonte, produtos_ids, botao_texto, botao_link, criado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$c['assunto'] . ' (cópia)', $c['pre_cabecalho'], $c['titulo'], $c['texto'], $c['fonte'], $c['produtos_ids'], $c['botao_texto'], $c['botao_link'], AdminAuth::id()]);
        $this->redirecionar('/admin/newsletter/' . $this->db()->lastInsertId());
    }

    /** Lista de subscritores activos em CSV (para backup ou outra ferramenta). */
    public function exportar(): void
    {
        AdminAuth::registar('exportou_subscritores', 'newsletter');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="subscritores-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Email'], ';');
        foreach (Newsletter::destinatarios() as $e) {
            fputcsv($out, [$e], ';');
        }
        exit;
    }

    private function obter(int $id): array
    {
        $st = $this->db()->prepare("SELECT * FROM newsletter_campanhas WHERE id = ?");
        $st->execute([$id]);
        $c = $st->fetch();
        if (!$c) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            exit;
        }
        return $c;
    }
}
