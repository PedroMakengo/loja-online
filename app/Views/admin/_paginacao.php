<?php /** Paginação. Espera $pg = ['pagina','paginas','total'] */ if (($pg['paginas'] ?? 1) > 1): ?>
<nav class="mt-4 flex items-center justify-between gap-3 text-sm" aria-label="Paginação">
    <p class="text-suave num"><?= (int) $pg['total'] ?> registos, página <?= (int) $pg['pagina'] ?> de <?= (int) $pg['paginas'] ?></p>
    <div class="flex gap-1">
        <?php if ($pg['pagina'] > 1): ?><a class="btn-secundario h-9 px-3" href="<?= e(urlActual(['pagina' => $pg['pagina'] - 1])) ?>">Anterior</a><?php endif; ?>
        <?php if ($pg['pagina'] < $pg['paginas']): ?><a class="btn-secundario h-9 px-3" href="<?= e(urlActual(['pagina' => $pg['pagina'] + 1])) ?>">Seguinte</a><?php endif; ?>
    </div>
</nav>
<?php endif; ?>
