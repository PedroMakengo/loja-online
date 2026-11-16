<?php
/**
 * Painel do mega-menu de uma categoria de topo (desktop), ao estilo:
 *   Informática
 *   Computadores Desktops | Computadores Portáteis | Acessórios… | Armazenamento +
 *   StandAlone            | Macbook                | Cabos…      | Impressão +
 * Subcategorias com filhas viram colunas (título + links); as que não têm
 * filhas juntam-se na última coluna, a negrito.
 * @var array $raiz  nó da árvore (Categoria::arvore()) com 'filhos'
 */
$colunas = array_values(array_filter($raiz['filhos'], fn ($f) => $f['filhos']));
$soltas  = array_values(array_filter($raiz['filhos'], fn ($f) => !$f['filhos']));
?>
<div class="container py-6">
    <a href="<?= url('/categoria/' . $raiz['slug']) ?>" class="group/titulo inline-flex items-center gap-3">
        <img src="<?= imagemCategoria($raiz['slug']) ?>" alt="" class="h-9 w-9 rounded-lg bg-white p-1" loading="lazy" width="36" height="36">
        <span class="font-display text-2xl font-extrabold group-hover/titulo:text-marca"><?= e($raiz['nome']) ?></span>
        <span class="text-sm text-suave num"><?= (int) $raiz['total_produtos'] ?> produtos</span>
    </a>
    <div class="mt-5 grid gap-x-8 gap-y-6 grid-cols-4">
        <?php foreach ($colunas as $i => $col): ?>
        <div class="<?= $i % 4 ? 'border-l border-linha pl-8' : '' ?>">
            <a href="<?= url('/categoria/' . $col['slug']) ?>" class="block font-display text-base font-bold leading-snug text-tinta hover:text-marca"><?= e($col['nome']) ?></a>
            <ul class="mt-3 space-y-1.5">
                <?php foreach ($col['filhos'] as $f): ?>
                <li><a href="<?= url('/categoria/' . $f['slug']) ?>" class="text-suave hover:text-tinta hover:underline"><?= e($f['nome']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= url('/categoria/' . $col['slug']) ?>" class="font-semibold text-marca hover:underline">Ver todos os produtos</a></li>
            </ul>
        </div>
        <?php endforeach; ?>
        <?php if ($soltas): ?>
        <ul class="space-y-2.5 <?= count($colunas) % 4 ? 'border-l border-linha pl-8' : '' ?>">
            <?php foreach ($soltas as $s): ?>
            <li><a href="<?= url('/categoria/' . $s['slug']) ?>" class="font-display text-base font-bold text-tinta hover:text-marca"><?= e($s['nome']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
