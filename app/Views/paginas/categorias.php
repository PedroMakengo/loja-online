<?php /** @var array $categorias */ ?>
<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Categorias'; $introPagina = 'Tudo o que vendemos, organizado por tipo de produto.'; require __DIR__ . '/_cabecalho.php'; ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach ($categorias as $c): ?>
        <a href="<?= url('/categoria/' . $c['slug']) ?>" class="group cartao p-3 hover:border-tinta/25 transition-colors">
            <div class="aspect-[4/3] rounded-xl bg-white flex items-center justify-center">
                <img src="<?= imagemCategoria($c['slug']) ?>" alt="" class="h-[80%] w-[80%] object-contain transition-transform duration-300 group-hover:scale-105" loading="lazy" width="200" height="150">
            </div>
            <p class="mt-3 font-display text-lg font-bold"><?= e($c['nome']) ?></p>
            <p class="text-sm text-suave line-clamp-2"><?= e($c['descricao']) ?></p>
            <p class="mt-1 text-xs text-suave num"><?= (int) $c['total_produtos'] ?> produtos</p>
        </a>
        <?php endforeach; ?>
    </div>
</div>
