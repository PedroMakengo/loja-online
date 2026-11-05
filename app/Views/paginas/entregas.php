<?php /** @var array $zonas */ ?>
<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Entregas e prazos'; $introPagina = 'Entregamos em todas as províncias. Em Luanda, com a nossa própria frota.'; require __DIR__ . '/_cabecalho.php'; ?>
    <div class="max-w-3xl">
        <p class="mb-4 rounded-xl bg-marca-suave p-4 text-sm"><strong>Entrega grátis em Luanda</strong> em compras a partir de <?= kz(PORTES_GRATIS_LUANDA) ?>.</p>
        <div class="cartao overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-elevado text-left text-suave">
                    <tr><th class="px-4 py-3 font-semibold">Destino</th><th class="px-4 py-3 font-semibold">Prazo</th><th class="px-4 py-3 font-semibold text-right">Preço</th></tr>
                </thead>
                <tbody class="divide-y divide-linha">
                    <?php foreach ($zonas as $z): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium"><?= e($z['provincia']) ?><?= $z['expressa'] ? ' <span class="selo-novo ml-1">Expressa</span>' : '' ?></td>
                        <td class="px-4 py-3 text-suave">
                            <?php if ($z['expressa']): ?>No próprio dia (até às 13h)
                            <?php elseif ($z['prazo_min'] == $z['prazo_max']): ?><?= (int) $z['prazo_min'] ?> dia útil
                            <?php else: ?><?= (int) $z['prazo_min'] ?> a <?= (int) $z['prazo_max'] ?> dias úteis<?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold num"><?= kz($z['preco']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="prosa mt-8">
            <h2>Como funciona</h2>
            <ul>
                <li>Depois de confirmado o pagamento, preparamos a encomenda e enviamos o código de rastreamento por SMS e email.</li>
                <li>Em Luanda, o estafeta liga antes de chegar. Indique um ponto de referência na morada para facilitar.</li>
                <li>Nas províncias, a transportadora entrega na morada ou num ponto de levantamento na capital provincial.</li>
            </ul>
        </div>
    </div>
</div>
