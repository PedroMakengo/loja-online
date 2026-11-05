<?php /** Bloco de morada. Espera $end */ ?>
<p class="font-semibold"><?= e($end['nome_destinatario']) ?></p>
<p class="text-sm text-suave num"><?= e(telefoneFormatado($end['telefone'])) ?></p>
<p class="mt-2 text-sm leading-6">
    <?= $end['rua'] ? e($end['rua']) . '<br>' : '' ?>
    <?= e($end['bairro']) ?>, <?= e($end['municipio']) ?><br>
    <?= e($end['provincia']) ?>
</p>
<?php if ($end['referencia']): ?><p class="mt-1 text-sm text-suave">Referência: <?= e($end['referencia']) ?></p><?php endif; ?>
