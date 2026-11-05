<?php /** @var array $pedidos */ ?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>
        <div class="min-w-0 space-y-6">
            <h1 class="font-display text-3xl font-extrabold">Os meus pedidos</h1>
            <?php if ($pedidos): ?>
            <ul class="cartao divide-y divide-linha">
                <?php foreach ($pedidos as $pd): $estadoPedido = $pd['estado']; ?>
                <li class="relative grid grid-cols-[1fr_auto] hover:bg-elevado/50 gap-x-4 gap-y-1 p-4 sm:px-5">
                    <a href="<?= url('/conta/pedidos/' . $pd['numero']) ?>" class="font-semibold num hover:text-marca after:absolute after:inset-0">
                        <?= e($pd['numero']) ?></a>
                    <p class="preco text-base text-right"><?= kz($pd['total']) ?></p>
                    <p class="truncate text-sm text-suave"><?= date('d/m/Y', strtotime($pd['criado_em'])) ?>, <?= (int) $pd['artigos'] ?> <?= $pd['artigos'] == 1 ? 'artigo' : 'artigos' ?></p>
                    <div class="text-right"><?php require __DIR__ . '/_estado.php'; ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="cartao p-8 text-center">
                <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('camiao', 'h-7 w-7') ?></span>
                <p class="font-display text-lg font-bold">Ainda não tem pedidos</p>
                <p class="mt-1 text-sm text-suave">Quando fizer uma compra, pode seguir aqui o pagamento, a preparação e a entrega.</p>
                <a href="<?= url('/promocoes') ?>" class="btn-primario mt-5">Ver promoções</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
