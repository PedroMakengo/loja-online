<?php
/**
 * Análise de Dados — painel analítico com gráficos (assets/js/graficos.js).
 * @var string $de @var string $ate @var string $antDe @var string $antAte @var bool $mensal
 * @var array $actual @var array $anterior @var array $tempo @var array $metodos @var array $categorias
 * @var array $semana @var array $mapaHoras @var array $provincias @var array $estados @var array $clientes @var array $produtos
 */
$tituloAdmin = 'Análise de Dados';
$accoesAdmin = '<a href="' . url('/admin/relatorios?de=' . $de . '&ate=' . $ate) . '" class="btn-secundario">Relatório detalhado</a>';
require __DIR__ . '/_titulo.php';

$atalhos = [
    'Últimos 7 dias'  => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'Últimos 30 dias' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    'Este mês'        => [date('Y-m-01'), date('Y-m-d')],
    'Mês passado'     => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Este ano'        => [date('Y-01-01'), date('Y-m-d')],
];
$dataPt = fn (string $d) => date('d/m/Y', strtotime($d));

/** Variação percentual face ao período anterior (null quando não há base). */
$variacao = fn (float $a, float $b) => $b > 0 ? round(($a - $b) / $b * 100, 1) : null;

/** Distintivo de variação: cor = direcção × se subir é bom; com seta e texto (nunca só cor). */
$delta = function (?float $v, bool $subirEBom = true, string $sufixo = '%'): string {
    if ($v === null) return '<span class="text-xs text-suave">sem base de comparação</span>';
    $bom = $v == 0 ? null : (($v > 0) === $subirEBom);
    $cls = $bom === null ? 'bg-elevado text-suave' : ($bom ? 'bg-sucesso/10 text-sucesso' : 'bg-coral/10 text-coral');
    $seta = $v > 0 ? '↑' : ($v < 0 ? '↓' : '→');
    return '<span class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-xs font-semibold num ' . $cls . '">' . $seta . ' '
        . number_format(abs($v), 1, ',', '.') . $sufixo . '</span>';
};

/** Cartão de gráfico (cabeçalho + gráfico + legenda + rodapé), no padrão dos charts do shadcn. */
$cartao = function (string $titulo, string $descricao, array $cfg, ?string $rodape = null, string $classes = '', array $legenda = []): string {
    $cfg['titulo'] = $titulo;
    $h = '<section class="cartao flex min-w-0 flex-col p-5 ' . $classes . '" data-cartao-grafico>'
       . '<header class="mb-4 flex items-start justify-between gap-3"><div class="min-w-0">'
       . '<h2 class="font-display text-base font-bold leading-tight">' . e($titulo) . '</h2>'
       . '<p class="mt-0.5 text-sm text-suave">' . e($descricao) . '</p></div>'
       . '<button type="button" class="shrink-0 rounded-lg border border-linha px-2.5 h-8 text-xs font-medium text-suave hover:text-tinta hover:bg-elevado" data-grafico-tabela aria-pressed="false">Ver tabela</button>'
       . '</header>'
       . '<div class="flex-1" data-grafico="' . e(json_encode($cfg, JSON_UNESCAPED_UNICODE)) . '"></div>';
    if ($legenda) {
        $h .= '<div class="grafico-legenda mt-3">';
        foreach ($legenda as [$corL, $nomeL]) $h .= '<span><span class="grafico-indicador" style="background:var(--' . $corL . ')"></span>' . e($nomeL) . '</span>';
        $h .= '</div>';
    }
    if ($rodape) $h .= '<footer class="mt-4 border-t border-linha pt-3 text-sm">' . $rodape . '</footer>';
    return $h . '</section>';
};

$vReceita = $variacao($actual['receita'], $anterior['receita']);
$vPedidos = $variacao($actual['pedidos'], $anterior['pedidos']);
$kpis = [
    ['Receita',            kz($actual['receita']),                         $vReceita, true,  array_column($tempo, 'receita')],
    ['Pedidos pagos',      number_format($actual['pedidos'], 0, ',', '.'), $vPedidos, true,  array_column($tempo, 'pedidos')],
    ['Valor médio',        kz($actual['ticket']),                          $variacao($actual['ticket'], $anterior['ticket']), true, null],
    ['Clientes compradores', number_format($actual['clientes'], 0, ',', '.'), $variacao($actual['clientes'], $anterior['clientes']), true, null],
    ['Unidades vendidas',  number_format($actual['unidades'], 0, ',', '.'), $variacao($actual['unidades'], $anterior['unidades']), true, null],
    ['Taxa de conversão',  $actual['conversao'] === null ? '—' : number_format($actual['conversao'], 1, ',', '.') . '%',
        $actual['conversao'] !== null && $anterior['conversao'] ? round($actual['conversao'] - $anterior['conversao'], 1) : null, true, null],
];
$totalSemana = array_sum(array_column($semana, 'receita'));
$melhorDia = $totalSemana > 0 ? array_reduce($semana, fn ($c, $d) => $c === null || $d['receita'] > $c['receita'] ? $d : $c) : null;
$horas = $mapaHoras['linhas'] ? array_map(fn ($h) => array_sum(array_column(array_column($mapaHoras['linhas'], 'valores'), $h)), range(0, 23)) : [];
$horaPico = $horas && max($horas) > 0 ? array_search(max($horas), $horas) : null;
$novosTotal = array_sum(array_column($clientes, 'novos'));
$recTotal = array_sum(array_column($clientes, 'recorrentes'));
$taxaEntrega = $estados[1]['valor'] ? round($estados[3]['valor'] / $estados[1]['valor'] * 100, 1) : null;
?>

<!-- Filtro de período (uma linha, acima dos gráficos) -->
<form method="get" class="mb-6 flex flex-wrap items-end gap-2">
    <div><label for="de" class="rotulo text-xs">De</label><input id="de" type="date" name="de" value="<?= e($de) ?>" class="campo h-10"></div>
    <div><label for="ate" class="rotulo text-xs">Até</label><input id="ate" type="date" name="ate" value="<?= e($ate) ?>" class="campo h-10"></div>
    <button class="btn-primario h-10">Aplicar</button>
    <div class="flex flex-wrap gap-1 sm:ml-3">
        <?php foreach ($atalhos as $t => [$d1, $d2]): ?><a href="?de=<?= $d1 ?>&ate=<?= $d2 ?>" class="chip h-10 <?= $de === $d1 && $ate === $d2 ? 'chip-activo' : '' ?>"><?= $t ?></a><?php endforeach; ?>
    </div>
    <p class="w-full text-xs text-suave">Comparado com o período anterior: <?= $dataPt($antDe) ?> a <?= $dataPt($antAte) ?><?= $mensal ? ' · agrupado por mês' : '' ?></p>
</form>

<!-- Indicadores -->
<dl class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
    <?php foreach ($kpis as $i => [$rotulo, $valor, $var, $subirBom, $faisca]): ?>
    <div class="cartao kpi flex flex-col p-4">
        <dt class="text-sm text-suave"><?= e($rotulo) ?></dt>
        <dd class="mt-1 preco kpi-valor"><?= $valor ?></dd>
        <dd class="mt-1.5 flex items-center gap-1.5"><?= $delta($var, $subirBom, $i === 5 ? ' p.p.' : '%') ?><?php if ($var !== null): ?><span class="text-xs text-suave">vs anterior</span><?php endif; ?></dd>
        <?php if ($faisca): ?><dd class="mt-auto pt-2" data-grafico="<?= e(json_encode(['tipo' => 'faisca', 'valores' => $faisca, 'cor' => 'serie-1'])) ?>"></dd><?php endif; ?>
    </div>
    <?php endforeach; ?>
</dl>

<!-- Evolução: receita / pedidos, actual vs anterior (separadores ao estilo do shadcn) -->
<section class="cartao mt-6 overflow-hidden" data-cartao-grafico data-separadores>
    <header class="flex flex-col border-b border-linha sm:flex-row">
        <div class="flex-1 px-5 py-4">
            <h2 class="font-display text-base font-bold">Evolução das vendas</h2>
            <p class="mt-0.5 text-sm text-suave"><?= $mensal ? 'Por mês' : 'Por dia' ?>, período actual comparado com o anterior</p>
        </div>
        <div class="flex border-t border-linha sm:border-t-0" role="tablist">
            <?php foreach ([['receita', 'Receita', kz($actual['receita'])], ['pedidos', 'Pedidos', number_format($actual['pedidos'], 0, ',', '.')]] as $k => [$id, $nome, $total]): ?>
            <button type="button" role="tab" data-separador="<?= $id ?>" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>"
                    class="flex flex-1 flex-col justify-center gap-0.5 border-linha px-5 py-3 text-left sm:border-l sm:min-w-[11rem] aria-selected:bg-elevado/60 hover:bg-elevado/40">
                <span class="text-xs text-suave"><?= $nome ?></span>
                <span class="font-display text-lg font-extrabold leading-tight num"><?= $total ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </header>
    <div class="p-5">
        <div class="mb-3 flex justify-end">
            <button type="button" class="rounded-lg border border-linha px-2.5 h-8 text-xs font-medium text-suave hover:text-tinta hover:bg-elevado" data-grafico-tabela aria-pressed="false">Ver tabela</button>
        </div>
        <?php foreach (['receita' => ['Receita', 'kz'], 'pedidos' => ['Pedidos', 'num']] as $id => [$nome, $fmt]): ?>
        <div data-painel-separador="<?= $id ?>" <?= $id === 'pedidos' ? 'hidden' : '' ?>>
            <div data-grafico="<?= e(json_encode([
                'tipo' => 'area', 'altura' => 280, 'formato' => $fmt, 'titulo' => 'Evolução: ' . $nome, 'rotuloX' => $mensal ? 'Mês' : 'Dia',
                'series' => [
                    ['chave' => $id, 'nome' => 'Período actual', 'cor' => 'serie-1'],
                    ['chave' => $id . '_anterior', 'nome' => 'Período anterior', 'cor' => 'anterior', 'tracejado' => true],
                ],
                'dados' => $tempo,
            ], JSON_UNESCAPED_UNICODE)) ?>"></div>
        </div>
        <?php endforeach; ?>
        <div class="grafico-legenda mt-3">
            <span><span class="grafico-indicador" style="background:var(--serie-1)"></span>Período actual</span>
            <span><span class="grafico-indicador" style="background:var(--anterior)"></span>Período anterior</span>
        </div>
    </div>
    <footer class="border-t border-linha px-5 py-3 text-sm">
        <?php if ($vReceita !== null): ?>
            <span class="font-semibold"><?= $vReceita >= 0 ? 'Receita subiu' : 'Receita desceu' ?> <?= number_format(abs($vReceita), 1, ',', '.') ?>%</span>
            <span class="text-suave">face ao período anterior (<?= kz($anterior['receita']) ?>).</span>
        <?php else: ?>
            <span class="text-suave">Sem vendas no período anterior para comparar.</span>
        <?php endif; ?>
    </footer>
</section>

<!-- Composição -->
<div class="mt-6 grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <?= $cartao('Formas de pagamento', 'Receita por método', [
        'tipo' => 'donut', 'formato' => 'kz', 'centro' => 'Receita', 'nomeValor' => 'Receita', 'dados' => $metodos,
        'extras' => [['chave' => 'pedidos', 'nome' => 'Pedidos', 'formato' => 'num']],
    ], $metodos ? '<span class="font-semibold">' . e($metodos[0]['rotulo']) . '</span> <span class="text-suave">é o método mais usado.</span>' : null) ?>

    <?= $cartao('Categorias', 'Receita por categoria', [
        'tipo' => 'barras-h', 'formato' => 'kz', 'nomeValor' => 'Receita', 'dados' => $categorias, 'cor' => 'serie-1',
        'extras' => [['chave' => 'unidades', 'nome' => 'Unidades', 'formato' => 'num']],
    ], $categorias ? '<span class="font-semibold">' . e($categorias[0]['rotulo']) . '</span> <span class="text-suave">lidera com ' . kz($categorias[0]['valor']) . '.</span>' : null) ?>

    <?= $cartao('Dias da semana', 'Receita acumulada por dia', [
        'tipo' => 'barras', 'formato' => 'kz', 'altura' => 240, 'rotuloX' => 'Dia', 'larguraMax' => 32,
        'series' => [['chave' => 'receita', 'nome' => 'Receita', 'cor' => 'serie-1']], 'dados' => $semana,
    ], $melhorDia ? '<span class="font-semibold">' . e($melhorDia['rotulo']) . '</span> <span class="text-suave">é o dia mais forte (' . round($melhorDia['receita'] / $totalSemana * 100) . '% da receita).</span>' : null, 'lg:col-span-2 xl:col-span-1') ?>
</div>

<!-- Comportamento -->
<div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
    <?= $cartao('Horas de compra', 'Pedidos pagos por dia da semana e hora', [
        'tipo' => 'calor', 'formato' => 'num', 'nomeValor' => 'Pedidos', 'dados' => $mapaHoras,
    ], $horaPico !== null ? '<span class="text-suave">Pico às</span> <span class="font-semibold">' . sprintf('%02dh', $horaPico) . '</span><span class="text-suave"> — bom momento para campanhas e promoções do dia.</span>' : null) ?>

    <?= $cartao('Funil de pedidos', 'Pedidos criados no período e até onde chegaram', [
        'tipo' => 'barras-h', 'formato' => 'num', 'nomeValor' => 'Pedidos', 'dados' => $estados, 'pctDoPrimeiro' => true,
        'cores' => ['serie-1', 'serie-1', 'serie-1', 'serie-1', 'grafico-perda'],
    ], $actual['conversao'] !== null ? '<span class="font-semibold">' . number_format($actual['conversao'], 1, ',', '.') . '%</span> <span class="text-suave">dos pedidos foram pagos' . ($taxaEntrega !== null ? '; ' . number_format($taxaEntrega, 1, ',', '.') . '% dos pagos já foram entregues.' : '.') . '</span>' : null) ?>
</div>

<!-- Clientes e geografia -->
<div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
    <?= $cartao('Clientes novos vs recorrentes', 'Receita por tipo de cliente', [
        'tipo' => 'barras', 'empilhado' => true, 'formato' => 'kz', 'altura' => 260, 'rotuloX' => $mensal ? 'Mês' : 'Dia',
        'series' => [['chave' => 'novos', 'nome' => 'Novos', 'cor' => 'serie-1'], ['chave' => 'recorrentes', 'nome' => 'Recorrentes', 'cor' => 'serie-2']],
        'dados' => $clientes,
    ], ($novosTotal + $recTotal) > 0 ? '<span class="font-semibold">' . round($recTotal / ($novosTotal + $recTotal) * 100) . '%</span> <span class="text-suave">da receita veio de clientes que já tinham comprado.</span>' : null, '', [['serie-1', 'Novos'], ['serie-2', 'Recorrentes']]) ?>

    <?= $cartao('Províncias', 'Receita por província de entrega', [
        'tipo' => 'barras-h', 'formato' => 'kz', 'nomeValor' => 'Receita', 'dados' => $provincias, 'cor' => 'serie-3',
        'extras' => [['chave' => 'pedidos', 'nome' => 'Pedidos', 'formato' => 'num']],
    ]) ?>
</div>

<div class="mt-6">
    <?= $cartao('Produtos mais vendidos', 'Top 8 por receita no período', [
        'tipo' => 'barras-h', 'formato' => 'kz', 'nomeValor' => 'Receita', 'dados' => $produtos, 'cor' => 'serie-1',
        'extras' => [['chave' => 'unidades', 'nome' => 'Unidades', 'formato' => 'num']],
    ]) ?>
</div>

<script src="<?= asset('js/graficos.js') ?>" defer></script>
