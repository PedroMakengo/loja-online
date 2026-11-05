<?php /** Etiqueta de preço do banner. Espera $b, $desc e $foto (com fotografia fica no canto, fora da imagem do produto). */ ?>
<div class="etiqueta-grande -rotate-3 origin-left <?= $foto ? '' : 'absolute -bottom-8 -left-6 sm:-left-14 scale-[0.85] sm:scale-100 origin-bottom-left' ?>">
    <?php if ($desc): ?>
    <span class="text-sm text-grafite/55 line-through num"><?= kz($b['produto_preco_anterior']) ?></span>
    <?php endif; ?>
    <span class="font-display text-2xl sm:text-[2.25rem] font-extrabold leading-none num whitespace-nowrap"><?= kz($b['produto_preco']) ?></span>
    <?php if ($desc): ?>
    <span class="mt-1.5 text-xs font-bold text-[#C9352B]">Poupa <?= kz($b['produto_preco_anterior'] - $b['produto_preco']) ?></span>
    <?php endif; ?>
</div>
