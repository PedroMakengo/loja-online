<?php /** $tituloAdmin, $subtituloAdmin?, $accoesAdmin? (html) */ ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl sm:text-3xl font-extrabold"><?= e($tituloAdmin) ?></h1>
        <?php if (!empty($subtituloAdmin)): ?><p class="mt-1 text-sm text-suave"><?= $subtituloAdmin ?></p><?php endif; ?>
    </div>
    <?php if (!empty($accoesAdmin)): ?><div class="flex flex-wrap gap-2"><?= $accoesAdmin ?></div><?php endif; ?>
</div>
<?php $subtituloAdmin = $accoesAdmin = null; ?>
