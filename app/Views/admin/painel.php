<?php
/** @var array $kpi @var array $accoes @var array $serie @var array $ultimos @var array $topProdutos */
use App\Servicos\Pagamentos;
$tituloAdmin = 'Resumo';
$subtituloAdmin = e(ucfirst(strftime_pt(date('Y-m-d'), true)));
require __DIR__ . '/_titulo.php';

$variacao = $kpi['vendas_mes_anterior_ate_hoje'] > 0 ? round(($kpi['vendas_mes'] / $kpi['vendas_mes_anterior_ate_hoje'] - 1) * 100) : null;
$maxSerie = max(1, max(array_column($serie, 'total')));
$totalSerie = array_sum(array_column($serie, 'total'));
?>

<!-- O que precisa de acção -->
<?php
$tarefas = array_filter([
    [$accoes['transferencias'], $accoes['transferencias'] == 1 ? 'transferência com comprovativo para verificar' : 'transferências com comprovativo para verificar', '/admin/pedidos?estado=transferencias', 'coral'],
    [$accoes['por_preparar'], $accoes['por_preparar'] == 1 ? 'pedido pago por preparar' : 'pedidos pagos por preparar', '/admin/pedidos?estado=pago', 'marca'],
    [$accoes['em_preparacao'], 'em preparação, por enviar', '/admin/pedidos?estado=em_preparacao', 'marca'],
    [$accoes['esgotados'], $accoes['esgotados'] == 1 ? 'produto esgotado' : 'produtos esgotados', '/admin/produtos?stock=esgotado', 'aviso'],
    [$accoes['stock_baixo'] - $accoes['esgotados'], ($accoes['stock_baixo'] - $accoes['esgotados']) == 1 ? 'produto com stock baixo' : 'produtos com stock baixo', '/admin/produtos?stock=baixo&ordem=stock', 'aviso'],
    [$accoes['mensagens'], $accoes['mensagens'] == 1 ? 'mensagem por ler' : 'mensagens por ler', '/admin/mensagens?estado=nao-lidas', 'marca'],
    [$accoes['avaliacoes'], $accoes['avaliacoes'] == 1 ? 'avaliação por moderar' : 'avaliações por moderar', '/admin/avaliacoes', 'marca'],
], fn ($t) => $t[0] > 0);
?>
<?php if ($tarefas): ?>
<section class="mb-6" aria-labelledby="t-accao">
    <h2 id="t-accao" class="mb-3 text-sm font-semibold text-suave">Precisa de atenção</h2>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($tarefas as [$n, $texto, $href, $cor]): ?>
        <a href="<?= url($href) ?>" class="flex items-center gap-4 cartao p-4 hover:border-tinta/25">
            <span class="font-display text-3xl font-extrabold num <?= ['coral' => 'text-coral', 'marca' => 'text-marca', 'aviso' => 'text-aviso'][$cor] ?>"><?= (int) $n ?></span>
            <span class="flex-1 text-sm font-medium"><?= $texto ?></span>
            <?= icone('seta-dir', 'h-4 w-4 text-suave') ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php else: ?>
<p class="mb-6 rounded-xl bg-sucesso/10 p-4 text-sm font-medium text-sucesso">Tudo em dia: não há pedidos nem pagamentos à espera.</p>
<?php endif; ?>

<!-- Números -->
<dl class="grid grid-cols-2 xl:grid-cols-4 gap-3">
    <div class="cartao kpi p-4">
        <dt class="text-sm text-suave">Vendas hoje</dt>
        <dd class="mt-1 preco kpi-valor"><?= kz($kpi['vendas_hoje']) ?></dd>
        <dd class="text-xs text-suave num"><?= plural($kpi['pedidos_hoje'], 'pedido pago', 'pedidos pagos') ?></dd>
    </div>
    <div class="cartao kpi p-4">
        <dt class="text-sm text-suave">Vendas este mês</dt>
        <dd class="mt-1 preco kpi-valor"><?= kz($kpi['vendas_mes']) ?></dd>
        <dd class="text-xs num <?= $variacao === null ? 'text-suave' : ($variacao >= 0 ? 'text-sucesso' : 'text-coral') ?>">
            <?= $variacao === null ? 'Sem vendas no mesmo período do mês anterior' : ($variacao >= 0 ? '+' : '') . $variacao . '% face ao mesmo período do mês anterior' ?>
        </dd>
    </div>
    <div class="cartao kpi p-4">
        <dt class="text-sm text-suave">Valor médio por pedido</dt>
        <dd class="mt-1 preco kpi-valor"><?= kz($kpi['pedidos_mes'] ? $kpi['vendas_mes'] / $kpi['pedidos_mes'] : 0) ?></dd>
        <dd class="text-xs text-suave num"><?= plural($kpi['pedidos_mes'], 'pedido', 'pedidos') ?> este mês</dd>
    </div>
    <div class="cartao kpi p-4">
        <dt class="text-sm text-suave">Aguardam pagamento</dt>
        <dd class="mt-1 font-display kpi-valor font-extrabold num"><?= (int) $accoes['aguardam_pagamento'] ?></dd>
        <dd class="text-xs text-suave num"><?= plural($accoes['clientes_novos'], 'cliente novo', 'clientes novos') ?> em 30 dias</dd>
    </div>
</dl>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
    <!-- Gráfico de 30 dias (SVG gerado no servidor) -->
    <section class="cartao p-5 min-w-0" aria-labelledby="t-grafico">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="t-grafico" class="font-display text-lg font-bold">Vendas dos últimos 30 dias</h2>
            <p class="text-sm text-suave">Total <strong class="text-tinta num"><?= kz($totalSerie) ?></strong></p>
        </div>
        <div class="mt-4 overflow-x-auto">
            <svg viewBox="0 0 600 200" class="w-full min-w-[480px] h-48" role="img" aria-label="Gráfico de barras com as vendas diárias dos últimos 30 dias">
                <?php foreach ([0, 0.5, 1] as $g): $y = 170 - $g * 150; ?>
                <line x1="0" x2="600" y1="<?= $y ?>" y2="<?= $y ?>" stroke="rgb(var(--linha))" stroke-width="1"/>
                <?php if ($g > 0): ?><text x="0" y="<?= $y - 4 ?>" font-size="10" fill="rgb(var(--suave))"><?= number_format($maxSerie * $g / 1000, 0, ',', ' ') ?> mil Kz</text><?php endif; ?>
                <?php endforeach; ?>
                <?php foreach ($serie as $i => $d):
                    $h = $d['total'] / $maxSerie * 150; $x = $i * 20 + 3; $hoje = $i === 29; ?>
                <g>
                    <title><?= date('d/m', strtotime($d['dia'])) ?>: <?= strip_tags(kz($d['total'])) ?> (<?= plural($d['pedidos'], 'pedido', 'pedidos') ?>)</title>
                    <rect x="<?= $x ?>" y="<?= 170 - max($h, $d['total'] > 0 ? 2 : 0) ?>" width="14" height="<?= max($h, $d['total'] > 0 ? 2 : 0) ?>" rx="3"
                          fill="<?= $hoje ? 'rgb(var(--coral))' : 'rgb(var(--marca))' ?>" opacity="<?= $hoje ? 1 : .85 ?>"/>
                </g>
                <?php if ($i % 5 === 0 || $hoje): ?><text x="<?= $x + 7 ?>" y="188" font-size="10" text-anchor="middle" fill="rgb(var(--suave))"><?= date('d/m', strtotime($d['dia'])) ?></text><?php endif; ?>
                <?php endforeach; ?>
            </svg>
        </div>
    </section>

    <section class="cartao p-5 min-w-0" aria-labelledby="t-top">
        <h2 id="t-top" class="font-display text-lg font-bold">Mais vendidos (30 dias)</h2>
        <?php if ($topProdutos): ?>
        <ol class="mt-3 space-y-3">
            <?php $maxTop = max(array_column($topProdutos, 'receita')); foreach ($topProdutos as $t): ?>
            <li>
                <div class="flex justify-between gap-3 text-sm"><span class="truncate font-medium"><?= e($t['nome']) ?></span><span class="shrink-0 font-semibold num"><?= kz($t['receita']) ?></span></div>
                <div class="mt-1 h-1.5 rounded-full bg-elevado"><div class="h-full rounded-full bg-marca" style="width: <?= round($t['receita'] / $maxTop * 100) ?>%"></div></div>
                <p class="mt-0.5 text-xs text-suave num"><?= plural($t['unidades'], 'unidade', 'unidades') ?></p>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php else: ?><p class="mt-3 text-sm text-suave">Ainda sem vendas pagas neste período.</p><?php endif; ?>
    </section>
</div>

<section class="mt-6 cartao overflow-hidden" aria-labelledby="t-ultimos">
    <div class="flex items-center justify-between border-b border-linha px-5 py-4">
        <h2 id="t-ultimos" class="font-display text-lg font-bold">Últimos pedidos</h2>
        <a href="<?= url('/admin/pedidos') ?>" class="link text-sm">Ver todos</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-linha">
                <?php foreach ($ultimos as $u): $estadoPedido = $u['estado']; ?>
                <tr class="hover:bg-elevado/50">
                    <td class="px-5 py-3 font-semibold num"><a href="<?= url('/admin/pedidos/' . $u['numero']) ?>" class="hover:text-marca"><?= e($u['numero']) ?></a></td>
                    <td class="px-5 py-3"><?= e($u['cliente']) ?></td>
                    <td class="px-5 py-3 text-suave hidden md:table-cell"><?= e(Pagamentos::nome($u['metodo_pagamento'])) ?></td>
                    <td class="px-5 py-3 text-suave hidden sm:table-cell num"><?= date('d/m H:i', strtotime($u['criado_em'])) ?></td>
                    <td class="px-5 py-3"><?php require RAIZ . '/app/Views/conta/_estado.php'; ?></td>
                    <td class="px-5 py-3 text-right font-semibold num"><?= kz($u['total']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$ultimos): ?><tr><td class="px-5 py-6 text-center text-suave">Ainda não há pedidos.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
