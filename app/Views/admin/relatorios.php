<?php
/** @var string $de @var string $ate @var array $totais @var array $porDia @var array $porMetodo @var array $porCategoria @var array $porProvincia @var array $topProdutos */
use App\Servicos\Pagamentos;
$tituloAdmin = 'Relatórios de vendas';
$accoesAdmin = '<a href="' . url('/admin/relatorios/csv?de=' . $de . '&ate=' . $ate) . '" class="btn-secundario">Exportar CSV (Excel)</a>';
require __DIR__ . '/_titulo.php';
$atalhos = [
    'Este mês' => [date('Y-m-01'), date('Y-m-d')],
    'Mês passado' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Últimos 7 dias' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'Este ano' => [date('Y-01-01'), date('Y-m-d')],
];
$margem = $totais['custo'] > 0 ? $totais['receita'] - $totais['portes'] - $totais['custo'] : null;
$barra = function (array $linhas, string $rotulo, callable $nome) {
    if (!$linhas) return '<p class="text-sm text-suave">Sem vendas no período.</p>';
    $max = max(array_column($linhas, 'receita')) ?: 1;
    $h = '<ul class="space-y-3">';
    foreach ($linhas as $l) {
        $h .= '<li><div class="flex justify-between gap-3 text-sm"><span class="truncate">' . e($nome($l)) . '</span><span class="shrink-0 font-semibold num">' . kz($l['receita']) . '</span></div>'
            . '<div class="mt-1 h-1.5 rounded-full bg-elevado"><div class="h-full rounded-full bg-marca" style="width:' . round($l['receita'] / $max * 100) . '%"></div></div>'
            . '<p class="mt-0.5 text-xs text-suave num">' . ($rotulo === 'unidades' ? plural($l[$rotulo] ?? 0, 'unidade', 'unidades') : plural($l[$rotulo] ?? 0, 'pedido', 'pedidos')) . '</p></li>';
    }
    return $h . '</ul>';
};
?>
<form method="get" class="mb-6 flex flex-wrap items-end gap-2">
    <div><label for="de" class="rotulo text-xs">De</label><input id="de" type="date" name="de" value="<?= e($de) ?>" class="campo h-10"></div>
    <div><label for="ate" class="rotulo text-xs">Até</label><input id="ate" type="date" name="ate" value="<?= e($ate) ?>" class="campo h-10"></div>
    <button class="btn-primario h-10">Ver</button>
    <div class="flex flex-wrap gap-1 sm:ml-3">
        <?php foreach ($atalhos as $t => [$d1, $d2]): ?><a href="?de=<?= $d1 ?>&ate=<?= $d2 ?>" class="chip h-10 <?= $de === $d1 && $ate === $d2 ? 'chip-activo' : '' ?>"><?= $t ?></a><?php endforeach; ?>
    </div>
</form>

<dl class="grid grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="cartao p-4"><dt class="text-sm text-suave">Receita (pedidos pagos)</dt><dd class="preco text-2xl"><?= kz($totais['receita']) ?></dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">Pedidos</dt><dd class="font-display text-2xl font-extrabold num"><?= (int) $totais['pedidos'] ?></dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">Valor médio</dt><dd class="preco text-2xl"><?= kz($totais['ticket']) ?></dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">IVA incluído</dt><dd class="preco text-2xl"><?= kz($totais['iva']) ?></dd><dd class="text-xs text-suave">Descontos: <?= kz($totais['descontos']) ?></dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">Margem bruta estimada</dt>
        <dd class="preco text-2xl"><?= $margem !== null ? kz($margem) : '—' ?></dd>
        <dd class="text-xs text-suave"><?= $totais['sem_custo'] > 0 ? kz($totais['sem_custo']) . ' vendidos sem custo registado' : 'Receita sem portes, menos custo dos produtos' ?></dd></div>
</dl>

<div class="mt-6 grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <section class="cartao p-5"><h2 class="mb-4 font-display text-lg font-bold">Por forma de pagamento</h2><?= $barra($porMetodo, 'pedidos', fn ($l) => Pagamentos::nome($l['metodo'])) ?></section>
    <section class="cartao p-5"><h2 class="mb-4 font-display text-lg font-bold">Por categoria</h2><?= $barra($porCategoria, 'unidades', fn ($l) => $l['categoria']) ?></section>
    <section class="cartao p-5"><h2 class="mb-4 font-display text-lg font-bold">Por província</h2><?= $barra($porProvincia, 'pedidos', fn ($l) => $l['provincia']) ?></section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
    <section class="cartao overflow-hidden">
        <h2 class="border-b border-linha px-5 py-4 font-display text-lg font-bold">Produtos mais vendidos</h2>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="text-left text-xs text-suave"><tr><th class="px-5 py-2 font-semibold">Produto</th><th class="px-5 py-2 font-semibold text-right">Unid.</th><th class="px-5 py-2 font-semibold text-right">Receita</th></tr></thead>
            <tbody class="divide-y divide-linha">
                <?php foreach ($topProdutos as $t): ?><tr><td class="px-5 py-2.5"><?= e($t['nome']) ?> <span class="text-xs text-suave num"><?= e($t['sku']) ?></span></td><td class="px-5 py-2.5 text-right num"><?= (int) $t['unidades'] ?></td><td class="px-5 py-2.5 text-right font-semibold num"><?= kz($t['receita']) ?></td></tr><?php endforeach; ?>
                <?php if (!$topProdutos): ?><tr><td colspan="3" class="px-5 py-6 text-center text-suave">Sem vendas no período.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
    <section class="cartao overflow-hidden">
        <h2 class="border-b border-linha px-5 py-4 font-display text-lg font-bold">Por dia</h2>
        <div class="max-h-96 overflow-y-auto"><table class="w-full text-sm"><tbody class="divide-y divide-linha">
            <?php foreach (array_reverse($porDia) as $d): ?><tr><td class="px-5 py-2 num"><?= date('d/m/Y', strtotime($d['dia'])) ?></td><td class="px-5 py-2 text-right text-suave num"><?= (int) $d['pedidos'] ?> ped.</td><td class="px-5 py-2 text-right font-semibold num"><?= kz($d['receita']) ?></td></tr><?php endforeach; ?>
            <?php if (!$porDia): ?><tr><td class="px-5 py-6 text-center text-suave">Sem vendas no período.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
