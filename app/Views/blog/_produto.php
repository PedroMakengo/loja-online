<?php /** Cartão de produto dentro do artigo ([produto:slug]). Preço e stock actuais. Espera $p */
$desc = descontoPercent($p['preco_anterior'] ? (float) $p['preco_anterior'] : null, (float) $p['preco']); $esgotado = (int) $p['stock'] < 1; ?>
<aside class="my-6 flex items-center gap-4 rounded-2xl border border-linha bg-superficie p-3 sm:p-4 cursor-pointer hover:border-tinta/25 transition-colors" aria-label="<?= e($p['nome']) ?>" data-card="<?= url('/produto/' . $p['slug']) ?>">
    <a href="<?= url('/produto/' . $p['slug']) ?>" class="shrink-0" tabindex="-1" aria-hidden="true"><img src="<?= imagemProduto($p) ?>" alt="" class="h-20 w-20 sm:h-24 sm:w-24 rounded-xl bg-white object-contain p-2" width="96" height="96" loading="lazy"></a>
    <div class="min-w-0 flex-1">
        <p class="text-xs text-suave"><?= e($p['marca_nome'] ?? '') ?></p>
        <a href="<?= url('/produto/' . $p['slug']) ?>" class="font-semibold leading-snug hover:text-marca line-clamp-2"><?= e($p['nome']) ?></a>
        <p class="mt-1 flex flex-wrap items-baseline gap-x-2">
            <span class="preco text-lg <?= $desc ? 'text-coral' : '' ?>"><?= kz($p['preco']) ?></span>
            <?php if ($desc): ?><span class="preco-anterior"><?= kz($p['preco_anterior']) ?></span><?php endif; ?>
        </p>
        <?php if ($esgotado): ?><p class="text-xs font-semibold text-suave">Esgotado de momento</p><?php endif; ?>
    </div>
    <?php if (!$esgotado): ?>
    <button type="button" data-adicionar="<?= (int) $p['id'] ?>" class="btn-primario h-10 px-3 shrink-0" aria-label="Adicionar <?= e($p['nome']) ?> ao carrinho">
        <?= icone('carrinho', 'h-5 w-5') ?><span class="hidden sm:inline">Adicionar</span>
    </button>
    <?php endif; ?>
</aside>
