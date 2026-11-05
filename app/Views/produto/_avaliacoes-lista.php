<?php /** Lista de avaliações. Espera $avaliacoes */ use App\Models\Avaliacao; ?>
<?php foreach ($avaliacoes as $a): ?>
<article class="cartao p-5" data-avaliacao>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="flex text-aviso" aria-label="<?= (int) $a['nota'] ?> de 5 estrelas">
            <?php for ($s = 1; $s <= 5; $s++): ?><?= icone('estrela', 'h-4 w-4 ' . ($s <= $a['nota'] ? 'fill-current' : 'text-linha')) ?><?php endfor; ?>
        </p>
        <p class="text-xs text-suave num"><?= date('d/m/Y', strtotime($a['criado_em'])) ?></p>
    </div>
    <?php if ($a['titulo']): ?><h3 class="mt-2 font-sans text-base font-semibold"><?= e($a['titulo']) ?></h3><?php endif; ?>
    <?php if ($a['comentario']): ?><p class="mt-1 whitespace-pre-line text-sm leading-6 text-tinta/85"><?= e($a['comentario']) ?></p><?php endif; ?>
    <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-suave">
        <span class="font-semibold text-tinta"><?= e(Avaliacao::nomePublico($a['nome'])) ?></span>
        <?php if ($a['compra_verificada']): ?><span class="inline-flex items-center gap-1 font-semibold text-sucesso"><?= icone('check', 'h-3.5 w-3.5') ?> Compra verificada</span><?php endif; ?>
    </p>
    <?php if ($a['resposta_loja']): ?>
    <div class="mt-3 rounded-xl bg-elevado p-3 text-sm">
        <p class="text-xs font-semibold text-marca">Resposta da DIOTEKNO</p>
        <p class="mt-1 text-tinta/85"><?= e($a['resposta_loja']) ?></p>
    </div>
    <?php endif; ?>
</article>
<?php endforeach; ?>
