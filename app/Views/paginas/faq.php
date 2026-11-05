<?php
$perguntas = [
    'Compras e pagamentos' => [
        ['Como pago com Multicaixa Express?', 'No checkout escolha Multicaixa Express e indique o número de telemóvel associado. Recebe um pedido de pagamento na app; ao aceitar, a encomenda fica confirmada de imediato.'],
        ['Posso pagar por referência Multicaixa?', 'Sim. Geramos uma entidade e referência válidas por 48 horas. Pode pagar em qualquer ATM ou no internet banking.'],
        ['Emitem factura com NIF?', 'Sim. Todas as vendas têm factura. Indique o NIF da pessoa ou empresa no checkout.'],
        ['Os preços incluem IVA?', 'Sim, todos os preços apresentados já incluem IVA à taxa em vigor.'],
    ],
    'Entregas' => [
        ['Quanto tempo demora a entrega em Luanda?', 'Até 24 horas úteis. Com entrega expressa, encomendas confirmadas até às 13h chegam no próprio dia.'],
        ['Entregam nas províncias?', 'Sim, em todas as 21 províncias através de transportadoras parceiras, em 3 a 6 dias úteis.'],
        ['Posso levantar na loja?', 'Sim. Escolha "Levantar na loja" no checkout e avisamos quando estiver pronto.'],
    ],
    'Garantia e devoluções' => [
        ['Os produtos são originais?', 'Sim. Vendemos apenas produtos novos, selados e com garantia oficial das marcas em Angola.'],
        ['O que faço se o produto vier com defeito?', 'Contacte-nos nos primeiros 7 dias e trocamos por um novo. Depois desse prazo, tratamos da reparação ao abrigo da garantia.'],
    ],
];
?>
<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Perguntas frequentes'; $introPagina = 'Respostas rápidas sobre pagamentos, entregas e garantia.'; require __DIR__ . '/_cabecalho.php'; ?>
    <div class="max-w-3xl space-y-8">
        <?php foreach ($perguntas as $grupo => $lista): ?>
        <section>
            <h2 class="mb-3 font-display text-xl font-bold"><?= e($grupo) ?></h2>
            <div class="cartao divide-y divide-linha">
                <?php foreach ($lista as [$p, $r]): ?>
                <details class="group p-4 sm:p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold [&::-webkit-details-marker]:hidden">
                        <?= e($p) ?>
                        <span class="transition-transform group-open:rotate-180 text-suave"><?= icone('seta-baixo') ?></span>
                    </summary>
                    <p class="mt-3 text-suave leading-7"><?= e($r) ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
        <div class="cartao p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <p class="font-semibold">Não encontrou a resposta?</p>
            <a href="<?= e(linkWhatsapp('Olá! Tenho uma dúvida.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp"><?= icone('whatsapp') ?> Perguntar no WhatsApp</a>
        </div>
    </div>
</div>
