<?php
// =====================================================================
// Admin\CrudController — motor genérico para entidades simples,
// definidas em config/crud.php (categorias, marcas, cupões, banners,
// zonas de entrega). Mesmo padrão do projecto Adapec.
// =====================================================================

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Flash;

class CrudController extends Base
{
    protected ?string $area = null;
    private array $cfg = [];
    private string $entidade = '';

    private function carregar(string $entidade): void
    {
        $todas = require RAIZ . '/config/crud.php';
        if (!isset($todas[$entidade])) {
            (new \App\Controllers\ErroController())->naoEncontrado();
            exit;
        }
        $this->entidade = $entidade;
        $this->cfg = $todas[$entidade];
        AdminAuth::exigir($this->cfg['area']);
    }

    public function index(string $entidade): void
    {
        $this->carregar($entidade);
        $w = '1=1'; $p = [];
        if (($q = trim((string) ($_GET['q'] ?? ''))) && !empty($this->cfg['pesquisa'])) {
            $w = '(' . implode(' OR ', array_map(fn ($c) => "$c LIKE ?", $this->cfg['pesquisa'])) . ')';
            $p = array_fill(0, count($this->cfg['pesquisa']), '%' . addcslashes($q, '%_') . '%');
        }
        [$linhas, $pg] = $this->paginar(
            "SELECT COUNT(*) FROM {$this->entidade} WHERE $w",
            "SELECT * FROM {$this->entidade} WHERE $w ORDER BY {$this->cfg['ordem']}",
            $p, 50
        );
        if (!empty($this->cfg['contagem'])) {
            $st = $this->db()->prepare($this->cfg['contagem'][0]);
            foreach ($linhas as &$l) { $st->execute([$l['id']]); $l['_contagem'] = (int) $st->fetchColumn(); }
            unset($l);
        }
        $this->pagina('crud/lista', ['cfg' => $this->cfg, 'entidade' => $entidade, 'linhas' => $linhas, 'pg' => $pg, 'opcoesTabela' => $this->opcoesTabela()], $this->cfg['titulo']);
    }

    public function form(string $entidade, ?string $id = null): void
    {
        $this->carregar($entidade);
        $registo = null;
        if ($id !== null) {
            $st = $this->db()->prepare("SELECT * FROM {$this->entidade} WHERE id = ?");
            $st->execute([(int) $id]);
            $registo = $st->fetch() ?: null;
            if (!$registo) {
                (new \App\Controllers\ErroController())->naoEncontrado();
                return;
            }
        }
        $this->pagina('crud/form', ['cfg' => $this->cfg, 'entidade' => $entidade, 'registo' => $registo, 'opcoesTabela' => $this->opcoesTabela()],
            ($registo ? 'Editar ' : 'Novo ') . $this->cfg['singular']);
    }

    public function editar(string $entidade, string $id): void
    {
        $this->form($entidade, $id);
    }

    public function novo(string $entidade): void
    {
        $this->form($entidade);
    }

    public function guardar(string $entidade): void
    {
        $this->carregar($entidade);
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $dados = []; $erros = [];
        $opcoesTabela = $this->opcoesTabela();
        $novasImagens = []; $imagensAntigas = [];

        foreach ($this->cfg['campos'] as $campo => $f) {
            if (!empty($f['so_leitura'])) {
                continue;
            }
            if ($f['tipo'] === 'imagem') {
                $actual = null;
                if ($id) {
                    $st = $this->db()->prepare("SELECT $campo FROM {$this->entidade} WHERE id = ?");
                    $st->execute([$id]);
                    $actual = $st->fetchColumn() ?: null;
                }
                $ficheiro = \Core\Upload::lista($_FILES[$campo] ?? null)[0] ?? null;
                try {
                    if ($ficheiro) {
                        $dados[$campo] = $novasImagens[] = \Core\Upload::imagem($ficheiro, $f['pasta'], (string) ($_POST['titulo'] ?? ''));
                        $imagensAntigas[] = $actual;
                    } elseif (!empty($_POST['remover_' . $campo])) {
                        $dados[$campo] = null;
                        $imagensAntigas[] = $actual;
                    } elseif (!empty($f['obrigatorio']) && !$actual) {
                        $erros[$campo] = 'Escolha uma imagem.';
                    }
                } catch (\RuntimeException $e) {
                    $erros[$campo] = $e->getMessage();
                }
                continue;
            }
            $bruto = $_POST[$campo] ?? null;
            $valor = is_string($bruto) ? trim($bruto) : $bruto;

            switch ($f['tipo']) {
                case 'booleano':
                    $valor = !empty($valor) ? 1 : 0;
                    break;
                case 'numero':
                    if ($valor === '' || $valor === null) { $valor = null; break; }
                    if (!preg_match('/^-?\d+$/', $valor)) { $erros[$campo] = 'Use um número inteiro.'; }
                    break;
                case 'dinheiro':
                    $valor = str_replace([' ', "\u{202F}", ','], ['', '', '.'], (string) $valor);
                    if ($valor === '') { $valor = null; break; }
                    if (!is_numeric($valor) || $valor < 0) { $erros[$campo] = 'Use um valor numérico.'; }
                    break;
                case 'datahora':
                case 'data':
                    if ($valor === '' || $valor === null) { $valor = null; break; }
                    if (!strtotime($valor)) { $erros[$campo] = 'Data inválida.'; break; }
                    $valor = date($f['tipo'] === 'data' ? 'Y-m-d' : 'Y-m-d H:i:s', strtotime($valor));
                    break;
                case 'select':
                    $permitidos = isset($f['opcoes']) ? array_map('strval', array_keys($f['opcoes'])) : array_map('strval', array_keys($opcoesTabela[$campo] ?? []));
                    if ($valor === '' || $valor === null) { $valor = null; break; }
                    if (!in_array((string) $valor, $permitidos, true)) { $erros[$campo] = 'Opção inválida.'; }
                    break;
                case 'slug':
                    $valor = slugify($valor ?: (string) ($_POST[$f['de']] ?? ''));
                    break;
                default:
                    $valor = preg_replace('/\s+/u', ' ', (string) $valor);
                    if (!empty($f['maiusculas'])) { $valor = mb_strtoupper(str_replace(' ', '', $valor)); }
                    if (isset($f['max']) && mb_strlen($valor) > $f['max']) { $erros[$campo] = "Use no máximo {$f['max']} caracteres."; }
                    if ($valor === '') { $valor = null; }
            }

            if (!empty($f['obrigatorio']) && ($valor === null || $valor === '')) {
                $erros[$campo] = 'Campo obrigatório.';
            }
            if (!empty($f['unico']) && $valor !== null && !isset($erros[$campo])) {
                $st = $this->db()->prepare("SELECT 1 FROM {$this->entidade} WHERE $campo = ? AND id <> ?");
                $st->execute([$valor, $id ?? 0]);
                if ($st->fetchColumn()) { $erros[$campo] = 'Já existe um registo com este valor.'; }
            }
            $dados[$campo] = $valor;
        }

        // Regras específicas
        if ($entidade === 'cupoes' && ($dados['tipo'] ?? '') === 'percentual' && (float) ($dados['valor'] ?? 0) > 90) {
            $erros['valor'] = 'Uma percentagem acima de 90% é quase de certeza um engano.';
        }
        if (isset($dados['inicio'], $dados['fim']) && $dados['inicio'] && $dados['fim'] && strtotime($dados['fim']) <= strtotime($dados['inicio'])) {
            $erros['fim'] = 'A data final tem de ser depois da inicial.';
        }
        if ($entidade === 'zonas_entrega' && (int) ($dados['prazo_max'] ?? 0) < (int) ($dados['prazo_min'] ?? 0)) {
            $erros['prazo_max'] = 'O prazo máximo não pode ser menor que o mínimo.';
        }

        if ($erros) {
            array_map([\Core\Upload::class, 'apagar'], $novasImagens);
            Flash::guardarFormulario($erros, $_POST);
            Flash::definir('erro', 'Corrija os campos assinalados.');
            $this->redirecionar("/admin/c/$entidade/" . ($id ?: 'novo'));
        }

        if ($id) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($dados)));
            $this->db()->prepare("UPDATE {$this->entidade} SET $sets WHERE id = ?")->execute([...array_values($dados), $id]);
        } else {
            $cols = implode(', ', array_keys($dados));
            $this->db()->prepare("INSERT INTO {$this->entidade} ($cols) VALUES (" . implode(',', array_fill(0, count($dados), '?')) . ")")->execute(array_values($dados));
            $id = (int) $this->db()->lastInsertId();
        }
        array_map([\Core\Upload::class, 'apagar'], $imagensAntigas);
        AdminAuth::registar($_POST['id'] ? 'editou' : 'criou', $entidade, $id);
        Flash::definir('ok', ucfirst($this->cfg['singular']) . ' guardado.');
        $this->redirecionar("/admin/c/$entidade");
    }

    public function apagar(string $entidade, string $id): void
    {
        $this->carregar($entidade);
        if (!empty($this->cfg['contagem'])) {
            $st = $this->db()->prepare($this->cfg['contagem'][0]);
            $st->execute([(int) $id]);
            if (($n = (int) $st->fetchColumn()) > 0) {
                Flash::definir('erro', "Não é possível apagar: tem $n {$this->cfg['contagem'][1]} associados. Desactive em vez de apagar.");
                $this->redirecionar("/admin/c/$entidade");
            }
        }
        $imagens = array_keys(array_filter($this->cfg['campos'], fn ($f) => $f['tipo'] === 'imagem'));
        $ficheiros = [];
        if ($imagens) {
            $st = $this->db()->prepare('SELECT ' . implode(', ', $imagens) . " FROM {$this->entidade} WHERE id = ?");
            $st->execute([(int) $id]);
            $ficheiros = array_values($st->fetch() ?: []);
        }
        try {
            $this->db()->prepare("DELETE FROM {$this->entidade} WHERE id = ?")->execute([(int) $id]);
            array_map([\Core\Upload::class, 'apagar'], $ficheiros);
            AdminAuth::registar('apagou', $entidade, $id);
            Flash::definir('ok', ucfirst($this->cfg['singular']) . ' apagado.');
        } catch (\PDOException $e) {
            Flash::definir('erro', 'Não é possível apagar porque está a ser usado noutros registos. Desactive em vez de apagar.');
        }
        $this->redirecionar("/admin/c/$entidade");
    }

    /** Opções dos selects ligados a outra tabela. */
    private function opcoesTabela(): array
    {
        $opcoes = [];
        foreach ($this->cfg['campos'] as $campo => $f) {
            if (($f['tipo'] === 'select') && isset($f['tabela'])) {
                [$t, $col] = $f['tabela'];
                $opcoes[$campo] = $this->db()->query("SELECT id, $col FROM $t ORDER BY $col")->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }
        return $opcoes;
    }
}
