<?php
/**
 * Painel de subcategorias de uma categoria de topo, ao estilo:
 *   Informática
 *   Computadores Desktops | Computadores Portáteis | Acessórios… | Armazenamento
 *   StandAlone            | Macbook                | Cabos…      | Impressão
 * Subcategorias com filhas viram colunas (título + links); as que não têm
 * filhas juntam-se na última coluna, a negrito.
 * Usado no mega-menu da barra (largura total, 4 colunas) e, com $compacto = true,
 * dentro de "Todas as categorias" (ao lado da lista, 3 colunas).
 * @var array $raiz  nó da árvore (Categoria::arvoreMenu()) com 'filhos'
 * @var bool|null $compacto
 */
$compacto = !empty($compacto);
$porLinha = $compacto ? 3 : 4;
$colunas  = array_values(array_filter($raiz['filhos'], fn ($f) => $f['filhos']));
$soltas   = array_values(array_filter($raiz['filhos'], fn ($f) => !$f['filhos']));
if (!$colunas) { // nenhuma tem filhas (ex.: Gaming): cada subcategoria vira uma coluna
    [$colunas, $soltas] = [$soltas, []];
}
$separador = fn (int $i) => $i % $porLinha ? 'border-l border-linha ' . ($compacto ? 'pl-6' : 'pl-8') : '';
?>
<div class="<?= $compacto ? 'p-6' : 'container py-6' ?>">
    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
        <a href="<?= url('/categoria/' . $raiz['slug']) ?>" class="group/titulo inline-flex items-center gap-3">
            <?php if (!$compacto): ?>
            <img src="<?= imagemCategoria($raiz['imagem'] ?? $raiz['slug']) ?>" alt="" class="h-9 w-9 rounded-lg bg-white p-1" loading="lazy" width="36" height="36">
            <?php endif; ?>
            <span class="font-display text-2xl font-extrabold group-hover/titulo:text-marca"><?= e($raiz['nome']) ?></span>
        </a>
        <a href="<?= url('/categoria/' . $raiz['slug']) ?>" class="text-sm font-semibold text-marca hover:underline">Ver os <?= (int) $raiz['total_produtos'] ?> produtos</a>
    </div>
    <div class="mt-5 grid <?= $compacto ? 'grid-cols-3 gap-x-6' : 'grid-cols-4 gap-x-8' ?> gap-y-6">
        <?php foreach ($colunas as $i => $col): ?>
        <div class="<?= $separador($i) ?>">
            <a href="<?= url('/categoria/' . $col['slug']) ?>" class="block font-display text-base font-bold leading-snug text-tinta hover:text-marca"><?= e($col['nome']) ?></a>
            <?php if (!$col['filhos']): // subcategoria sem filhas (ex.: Gaming › Consolas) ?>
            <a href="<?= url('/categoria/' . $col['slug']) ?>" class="mt-1 block text-sm text-suave hover:text-marca"><?= (int) $col['total_produtos'] ?> <?= (int) $col['total_produtos'] === 1 ? 'produto' : 'produtos' ?></a>
            <?php else: ?>
            <ul class="mt-3 space-y-2 text-sm">
                <?php foreach ($col['filhos'] as $f): ?>
                <li><a href="<?= url('/categoria/' . $f['slug']) ?>" class="text-suave hover:text-tinta hover:underline"><?= e($f['nome']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= url('/categoria/' . $col['slug']) ?>" class="font-semibold text-marca hover:underline">Ver todos os produtos</a></li>
            </ul>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if ($soltas): ?>
        <ul class="space-y-3 <?= $separador(count($colunas)) ?>">
            <?php foreach ($soltas as $s): ?>
            <li><a href="<?= url('/categoria/' . $s['slug']) ?>" class="font-display text-base font-bold text-tinta hover:text-marca"><?= e($s['nome']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
