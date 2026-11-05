<?php /** @var array $produtos */ ?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>
        <div class="min-w-0 space-y-6">
            <div>
                <h1 class="font-display text-3xl font-extrabold">Favoritos</h1>
                <?php if ($produtos): ?><p class="mt-1 text-suave num"><?= count($produtos) ?> <?= count($produtos) == 1 ? 'produto guardado' : 'produtos guardados' ?></p><?php endif; ?>
            </div>
            <?php if ($produtos): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-4" data-lista-favoritos>
                <?php foreach ($produtos as $p) { require RAIZ . '/app/Views/partials/produto-card.php'; } ?>
            </div>
            <?php else: ?>
            <div class="cartao p-8 text-center">
                <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('coracao', 'h-7 w-7') ?></span>
                <p class="font-display text-lg font-bold">Ainda não guardou favoritos</p>
                <p class="mt-1 text-sm text-suave">Toque no coração de qualquer produto para o guardar aqui.</p>
                <a href="<?= url('/produtos') ?>" class="btn-primario mt-5">Explorar produtos</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
