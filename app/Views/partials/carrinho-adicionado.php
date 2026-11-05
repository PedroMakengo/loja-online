<?php
/**
 * Conteúdo do painel "Adicionado ao carrinho" (devolvido por AJAX).
 * Espera $item (linha de Carrinho::itens), $resumo e $aviso (?string).
 * Sugere o passo seguinte: aumentar a quantidade, ver o carrinho ou pagar.
 */
$totalLinha = (float) $item['preco'] * (int) $item['quantidade'];
?>
<div class="flex items-center justify-between gap-3 border-b border-linha px-5 py-4">
    <p id="adicionado-titulo" class="flex items-center gap-2 font-display text-lg font-bold text-sucesso">
        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sucesso text-white"><?= icone('check', 'h-4 w-4') ?></span>
        Adicionado ao carrinho
    </p>
    <button type="button" class="btn-icone" data-fechar aria-label="Fechar"><?= icone('fechar') ?></button>
</div>

<div class="px-5 py-4">
    <?php if ($aviso): ?>
    <p class="mb-3 rounded-xl bg-aviso/10 px-3 py-2 text-sm font-medium text-aviso" role="status"><?= e($aviso) ?></p>
    <?php endif; ?>

    <div class="flex gap-4" data-item="<?= (int) $item['id'] ?>">
        <a href="<?= url('/produto/' . $item['slug']) ?>" class="shrink-0">
            <img src="<?= imagemProduto($item) ?>" alt="" class="h-20 w-20 rounded-xl bg-white object-contain p-2 ring-1 ring-linha" width="80" height="80">
        </a>
        <div class="min-w-0 flex-1">
            <?php if (!empty($item['marca_nome'])): ?><p class="text-xs font-medium text-suave"><?= e($item['marca_nome']) ?></p><?php endif; ?>
            <a href="<?= url('/produto/' . $item['slug']) ?>" class="line-clamp-2 text-sm font-semibold leading-snug hover:text-marca"><?= e($item['nome']) ?></a>
            <p class="mt-0.5 text-xs text-suave num"><?= kz($item['preco']) ?> / unidade</p>
            <div class="mt-2 flex items-center justify-between gap-2">
                <div>
                    <p class="mb-1 text-xs font-medium text-suave">Quantidade</p>
                    <?php $produtoQtd = $item; require __DIR__ . '/quantidade.php'; ?>
                </div>
                <p class="preco text-lg"><?= kz($totalLinha) ?></p>
            </div>
        </div>
    </div>

    <?php if ($resumo['falta_portes_gratis'] > 0): ?>
    <p class="mt-4 rounded-xl bg-elevado px-3 py-2 text-sm">Faltam <strong class="num"><?= kz($resumo['falta_portes_gratis']) ?></strong> para entrega grátis em Luanda.</p>
    <?php else: ?>
    <p class="mt-4 rounded-xl bg-sucesso/10 px-3 py-2 text-sm font-semibold text-sucesso">Tem entrega grátis em Luanda.</p>
    <?php endif; ?>
</div>

<div class="border-t border-linha px-5 py-4 pb-seguro space-y-3">
    <p class="flex items-baseline justify-between">
        <span class="text-sm text-suave">Subtotal (<?= plural($resumo['quantidade'], 'artigo', 'artigos') ?>)</span>
        <span class="preco text-xl"><?= kz($resumo['subtotal']) ?></span>
    </p>
    <a href="<?= url('/checkout') ?>" class="btn-primario w-full h-12 text-base">Pagar agora</a>
    <div class="grid grid-cols-2 gap-2">
        <a href="<?= url('/carrinho') ?>" class="btn-secundario">Ver carrinho</a>
        <button type="button" class="btn-secundario" data-fechar>Continuar a comprar</button>
    </div>
</div>
