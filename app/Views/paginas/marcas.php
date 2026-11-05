<?php /** @var array $marcas */ ?>
<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Marcas'; $introPagina = 'Somos revendedores das principais marcas. Todos os produtos têm garantia oficial em Angola.'; require __DIR__ . '/_cabecalho.php'; ?>
    <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <?php foreach ($marcas as $m): ?>
        <li>
            <a href="<?= url('/marca/' . $m['slug']) ?>" class="flex flex-col items-center justify-center gap-1 h-28 cartao hover:border-tinta/25 transition-colors">
                <span class="font-display text-xl font-bold"><?= e($m['nome']) ?></span>
                <span class="text-xs text-suave num"><?= (int) $m['total_produtos'] ?> produtos</span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
