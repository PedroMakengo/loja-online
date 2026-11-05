<?php
// Base dos controllers do painel: layout admin, paginação, permissões.

namespace App\Controllers\Admin;

use Core\AdminAuth;
use Core\Controller;
use Core\Database;

abstract class Base extends Controller
{
    /** Área de permissão exigida por todo o controller (null = só sessão). */
    protected ?string $area = 'painel';

    public function __construct()
    {
        if ($this->area !== null) {
            AdminAuth::exigir($this->area);
        }
    }

    protected function pagina(string $view, array $dados, string $titulo): void
    {
        $dados['seo'] = ['titulo' => $titulo . ' | Painel ' . NOME_SITE, 'noindex' => true];
        $this->view('admin/' . $view, $dados, 'admin');
    }

    /** Executa uma listagem paginada. $sql sem LIMIT; devolve [linhas, ['pagina','paginas','total']]. */
    protected function paginar(string $sqlContagem, string $sql, array $params, int $porPagina = 25): array
    {
        $db = Database::ligacao();
        $st = $db->prepare($sqlContagem);
        $st->execute($params);
        $total = (int) $st->fetchColumn();
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($paginas, max(1, (int) ($_GET['pagina'] ?? 1)));
        $st = $db->prepare($sql . ' LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina));
        $st->execute($params);
        return [$st->fetchAll(), ['pagina' => $pagina, 'paginas' => $paginas, 'total' => $total]];
    }

    protected function db(): \PDO
    {
        return Database::ligacao();
    }
}
