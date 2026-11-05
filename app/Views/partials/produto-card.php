<?php
/** Cartão de produto. Espera $p (linha de Produto::BASE). Opcional: $comContagem (bool) */
$desc     = descontoPercent($p['preco_anterior'] ? (float) $p['preco_anterior'] : null, (float) $p['preco']);
$esgotado = (int) $p['stock'] < 1;
$ultimas  = !$esgotado && (int) $p['stock'] <= 3;
$link     = url('/produto/' . $p['slug']);
$favorito = in_array((int) $p['id'], favoritosIds(), true);
$comparar = in_array((int) $p['id'], \App\Servicos\Comparador::ids(), true);
?>
<article class="group relative flex flex-col cartao p-2.5 sm:p-3 hover:border-tinta/25 transition-colors cursor-pointer" data-card="<?= $link ?>">
    <div class="relative aspect-square overflow-hidden rounded-xl bg-white">
        <a href="<?= $link ?>" tabindex="-1" aria-hidden="true" class="block h-full w-full">
            <img src="<?= imagemProduto($p) ?>" alt="<?= e($p['nome']) ?>" loading="lazy" decoding="async" width="400" height="400"
                 class="h-full w-full object-contain p-4 transition-transform duration-300 group-hover:scale-[1.04] <?= $esgotado ? 'opacity-50' : '' ?>">
        </a>
        <div class="absolute left-2 top-2 flex flex-col items-start gap-1">
            <?php if ($desc): ?><span class="etiqueta num">-<?= $desc ?>%</span><?php endif; ?>
            <?php if ((int) $p['novidade']): ?><span class="selo-novo">Novo</span><?php endif; ?>
        </div>
        <button type="button" data-favorito="<?= (int) $p['id'] ?>" aria-pressed="<?= $favorito ? 'true' : 'false' ?>"
                class="absolute right-2 top-2 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full bg-superficie/90 text-suave hover:text-coral aria-pressed:text-coral transition-colors"
                aria-label="<?= $favorito ? 'Remover dos favoritos' : 'Guardar nos favoritos' ?>">
            <?= icone('coracao', 'h-5 w-5 ' . ($favorito ? 'fill-current' : '')) ?>
        </button>
        <button type="button" data-comparar="<?= (int) $p['id'] ?>" aria-pressed="<?= $comparar ? 'true' : 'false' ?>"
                class="absolute right-2 top-12 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full bg-superficie/90 text-suave hover:text-marca aria-pressed:bg-marca aria-pressed:text-white dark:aria-pressed:text-grafite transition-colors"
                aria-label="<?= $comparar ? 'Retirar da comparação' : 'Comparar' ?>" title="Comparar">
            <?= icone('comparar', 'h-[18px] w-[18px]') ?>
        </button>
    </div>

    <div class="flex flex-1 flex-col pt-3 px-0.5">
        <?php if (!empty($p['marca_nome'])): ?>
        <p class="text-xs font-medium text-suave"><?= e($p['marca_nome']) ?></p>
        <?php endif; ?>
        <h3 class="mt-0.5 font-sans text-sm font-semibold leading-snug text-tinta line-clamp-2 min-h-[2.5rem]">
            <a href="<?= $link ?>" class="after:absolute after:inset-0 after:rounded-2xl focus-visible:outline-none"><?= e($p['nome']) ?></a>
        </h3>

        <?php if ((int) $p['total_avaliacoes'] > 0): ?>
        <p class="mt-1 flex items-center gap-1 text-xs text-suave">
            <span class="text-aviso"><?= icone('estrela', 'h-3.5 w-3.5 fill-current') ?></span>
            <span class="font-semibold text-tinta num"><?= number_format((float) $p['avaliacao_media'], 1, ',', '') ?></span>
            <span class="num">(<?= (int) $p['total_avaliacoes'] ?>)</span>
        </p>
        <?php endif; ?>

        <div class="mt-auto pt-2 flex items-end justify-between gap-2">
            <div class="min-w-0">
                <?php if ($desc): ?><p class="preco-anterior"><?= kz($p['preco_anterior']) ?></p><?php endif; ?>
                <p class="preco text-lg sm:text-xl leading-tight <?= $desc ? 'text-coral' : '' ?>"><?= kz($p['preco']) ?></p>
                <?php if ($esgotado): ?>
                    <p class="text-xs font-semibold text-suave">Esgotado</p>
                <?php elseif ($ultimas): ?>
                    <p class="text-xs font-semibold text-aviso">Últimas <?= (int) $p['stock'] ?> unidades</p>
                <?php endif; ?>
            </div>
            <?php if (!$esgotado): ?>
            <button type="button" data-adicionar="<?= (int) $p['id'] ?>"
                    class="relative z-10 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tinta text-superficie hover:bg-marca transition-colors"
                    aria-label="Adicionar <?= e($p['nome']) ?> ao carrinho">
                <?= icone('carrinho', 'h-5 w-5') ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</article>
