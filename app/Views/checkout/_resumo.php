<?php /** Resumo lateral. Espera $itens, $resumo, $entrega, $total, $ivaIncluido, $problemas */ ?>
<div id="checkout-resumo">
    <ul class="divide-y divide-linha">
        <?php foreach ($itens as $i): ?>
        <li class="flex gap-3 py-3">
            <span class="relative shrink-0">
                <img src="<?= imagemProduto($i) ?>" alt="" class="h-16 w-16 rounded-xl bg-white object-contain p-1.5" width="64" height="64">
                <span class="absolute -right-1.5 -top-1.5 min-w-[1.25rem] h-5 rounded-full bg-tinta px-1 text-center text-[11px] font-bold leading-5 text-superficie num"><?= (int) $i['quantidade'] ?></span>
            </span>
            <span class="min-w-0 flex-1 text-sm font-medium leading-snug line-clamp-2"><?= e($i['nome']) ?></span>
            <span class="text-sm font-semibold num shrink-0"><?= kz($i['preco'] * $i['quantidade']) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($problemas): ?>
    <div class="my-3 rounded-xl bg-coral-suave p-3 text-sm text-coral" role="alert">
        <?php foreach ($problemas as $pr): ?><p><?= e($pr) ?></p><?php endforeach; ?>
        <a href="<?= url('/carrinho') ?>" class="mt-1 inline-block font-semibold underline">Corrigir no carrinho</a>
    </div>
    <?php endif; ?>

    <dl class="space-y-2 border-t border-linha pt-4 text-sm">
        <div class="flex justify-between"><dt class="text-suave">Subtotal</dt><dd class="font-semibold num"><?= kz($resumo['subtotal']) ?></dd></div>
        <?php if ($resumo['desconto'] > 0): ?>
        <div class="flex justify-between text-sucesso"><dt>Cupão <?= e($resumo['cupao']['codigo']) ?></dt><dd class="font-semibold num">−<?= kz($resumo['desconto']) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($descontoPontos)): ?>
        <div class="flex justify-between text-sucesso"><dt><?= number_format($pontosUsar, 0, ',', ' ') ?> pontos</dt><dd class="font-semibold num">−<?= kz($descontoPontos) ?></dd></div>
        <?php endif; ?>
        <div class="flex justify-between"><dt class="text-suave"><?= e($entrega['nome']) ?></dt><dd class="font-semibold num <?= $entrega['preco'] == 0 ? 'text-sucesso' : '' ?>"><?= $entrega['preco'] == 0 ? 'Grátis' : kz($entrega['preco']) ?></dd></div>
    </dl>
    <div class="mt-4 flex items-baseline justify-between border-t border-linha pt-4">
        <span class="font-semibold">Total</span>
        <span class="preco text-3xl" data-total-checkout><?= kz($total) ?></span>
    </div>
    <p class="mt-1 text-xs text-suave">Inclui <?= kz($ivaIncluido) ?> de IVA (<?= IVA_TAXA ?>%).</p>
    <?php $poupaTotal = $resumo['poupanca'] + $resumo['desconto'] + ($descontoPontos ?? 0); if ($poupaTotal > 0): ?><p class="mt-2 text-sm font-semibold text-sucesso">Está a poupar <?= kz($poupaTotal) ?> nesta compra.</p><?php endif; ?>
    <?php if (!empty($pontosGanhar)): ?><p class="mt-1 text-xs text-suave">Ganha <strong class="text-tinta num"><?= plural($pontosGanhar, 'ponto', 'pontos') ?></strong> quando o pagamento for confirmado.</p><?php endif; ?>
</div>
