<?php /** @var array $categorias  árvore: categorias de topo com 'filhos' */ ?>
<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Categorias'; $introPagina = 'Tudo o que vendemos, organizado por categoria e subcategoria.'; require __DIR__ . '/_cabecalho.php'; ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach ($categorias as $c): ?>
        <div class="group cartao p-3 hover:border-tinta/25 transition-colors flex flex-col" data-card="<?= url('/categoria/' . $c['slug']) ?>">
            <a href="<?= url('/categoria/' . $c['slug']) ?>" tabindex="-1" aria-hidden="true" class="aspect-[4/3] rounded-xl bg-white flex items-center justify-center">
                <img src="<?= imagemCategoria($c['slug']) ?>" alt="" class="h-[80%] w-[80%] object-contain transition-transform duration-300 group-hover:scale-105" loading="lazy" width="200" height="150">
            </a>
            <a href="<?= url('/categoria/' . $c['slug']) ?>" class="mt-3 font-display text-lg font-bold hover:text-marca"><?= e($c['nome']) ?></a>
            <?php if ($c['filhos']): ?>
            <ul class="mt-1 flex flex-wrap gap-x-2 gap-y-0.5 text-sm text-suave">
                <?php foreach ($c['filhos'] as $j => $f): ?>
                <li><a href="<?= url('/categoria/' . $f['slug']) ?>" class="hover:text-tinta hover:underline"><?= e($f['nome']) ?></a><?= $j < count($c['filhos']) - 1 ? ' ·' : '' ?></li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-sm text-suave line-clamp-2"><?= e($c['descricao']) ?></p>
            <?php endif; ?>
            <p class="mt-auto pt-1 text-xs text-suave num"><?= (int) $c['total_produtos'] ?> produtos</p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
