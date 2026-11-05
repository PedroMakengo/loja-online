<?php /** Seletor de quantidade AJAX. Espera $produtoQtd com id, quantidade, stock */ ?>
<div class="inline-flex items-center rounded-xl border border-linha" data-quantidade="<?= (int) $produtoQtd['id'] ?>">
    <button type="button" class="h-9 w-9 inline-flex items-center justify-center text-tinta hover:bg-elevado rounded-l-xl" data-qtd="-1" aria-label="Diminuir quantidade"><?= icone('menos', 'h-4 w-4') ?></button>
    <span class="w-8 text-center text-sm font-semibold num" data-qtd-valor><?= (int) $produtoQtd['quantidade'] ?></span>
    <button type="button" class="h-9 w-9 inline-flex items-center justify-center text-tinta hover:bg-elevado rounded-r-xl disabled:opacity-40"
            data-qtd="1" aria-label="Aumentar quantidade" <?= (int) $produtoQtd['quantidade'] >= min((int) $produtoQtd['stock'], \App\Models\Carrinho::QTD_MAX) ? 'disabled' : '' ?>><?= icone('mais', 'h-4 w-4') ?></button>
</div>
