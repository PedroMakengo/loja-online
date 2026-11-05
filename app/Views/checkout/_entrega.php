<?php /** Opções de entrega. Espera $opcoesEntrega, $metodoEntrega, $endereco */ ?>
<div id="checkout-entrega" class="space-y-2">
    <?php if (!$endereco): ?>
    <p class="rounded-xl bg-aviso/10 p-3 text-sm text-aviso">Sem morada guardada, só é possível levantar na loja. Adicione uma morada para receber em casa.</p>
    <?php endif; ?>
    <?php foreach ($opcoesEntrega as $op): ?>
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-linha p-4 has-[:checked]:border-marca has-[:checked]:bg-marca-suave/40 transition-colors">
        <input type="radio" name="metodo_entrega" value="<?= e($op['codigo']) ?>" <?= $op['codigo'] === $metodoEntrega ? 'checked' : '' ?>
               class="mt-1 h-4 w-4 accent-[rgb(var(--marca))]" data-recalcular>
        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                <span class="font-semibold"><?= e($op['nome']) ?></span>
                <span class="font-semibold num <?= $op['preco'] == 0 ? 'text-sucesso' : '' ?>"><?= $op['preco'] == 0 ? 'Grátis' : kz($op['preco']) ?></span>
            </span>
            <span class="block text-sm text-tinta/80"><?= e($op['prazo']) ?></span>
            <?php if ($op['descricao']): ?><span class="block text-xs text-suave mt-0.5"><?= e($op['descricao']) ?></span><?php endif; ?>
        </span>
    </label>
    <?php endforeach; ?>
</div>
