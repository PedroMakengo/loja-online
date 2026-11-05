<?php /** Conteúdo da gaveta. Espera $itens e $resumo */ ?>
<?php if (!$itens): ?>
    <div class="flex h-full flex-col items-center justify-center p-8 text-center">
        <span class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('carrinho', 'h-8 w-8') ?></span>
        <p class="font-display text-lg font-bold">O carrinho está vazio</p>
        <p class="mt-1 text-sm text-suave">Veja as promoções do dia ou pesquise o que precisa.</p>
        <a href="<?= url('/promocoes') ?>" class="btn-primario mt-5">Ver promoções</a>
    </div>
<?php else: ?>
    <div class="px-4 py-3 border-b border-linha">
        <?php if ($resumo['falta_portes_gratis'] > 0): ?>
            <p class="text-sm">Faltam <strong class="num"><?= kz($resumo['falta_portes_gratis']) ?></strong> para entrega grátis em Luanda.</p>
        <?php else: ?>
            <p class="text-sm font-semibold text-sucesso">Tem entrega grátis em Luanda.</p>
        <?php endif; ?>
        <div class="mt-2 h-1.5 rounded-full bg-elevado overflow-hidden" role="progressbar" aria-valuenow="<?= $resumo['progresso_portes'] ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full <?= $resumo['falta_portes_gratis'] > 0 ? 'bg-marca' : 'bg-sucesso' ?> transition-all" style="width: <?= $resumo['progresso_portes'] ?>%"></div>
        </div>
    </div>

    <ul class="divide-y divide-linha">
        <?php foreach ($itens as $i): ?>
        <li class="flex gap-3 p-4" data-item="<?= (int) $i['id'] ?>">
            <a href="<?= url('/produto/' . $i['slug']) ?>" class="shrink-0">
                <img src="<?= imagemProduto($i) ?>" alt="" class="h-20 w-20 rounded-xl bg-white object-contain p-2" width="80" height="80">
            </a>
            <div class="min-w-0 flex-1">
                <a href="<?= url('/produto/' . $i['slug']) ?>" class="line-clamp-2 text-sm font-semibold leading-snug hover:text-marca"><?= e($i['nome']) ?></a>
                <p class="preco mt-1 text-base"><?= kz($i['preco'] * $i['quantidade']) ?></p>
                <div class="mt-2 flex items-center justify-between">
                    <?php $produtoQtd = $i; require __DIR__ . '/quantidade.php'; ?>
                    <button type="button" class="text-xs font-medium text-suave hover:text-coral" data-remover="<?= (int) $i['id'] ?>">Remover</button>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php if ($itens): ?>
<div class="sticky bottom-0 border-t border-linha bg-superficie p-4 pb-seguro space-y-3">
    <?php if ($resumo['poupanca'] > 0): ?>
    <p class="flex justify-between text-sm text-sucesso"><span>Está a poupar</span><span class="font-semibold num"><?= kz($resumo['poupanca']) ?></span></p>
    <?php endif; ?>
    <p class="flex items-baseline justify-between"><span class="font-semibold">Subtotal</span><span class="preco text-2xl"><?= kz($resumo['subtotal']) ?></span></p>
    <p class="text-xs text-suave">IVA incluído. Entrega calculada no checkout.</p>
    <div class="grid grid-cols-2 gap-2">
        <a href="<?= url('/carrinho') ?>" class="btn-secundario">Ver carrinho</a>
        <a href="<?= url('/checkout') ?>" class="btn-primario">Finalizar compra</a>
    </div>
</div>
<?php endif; ?>
