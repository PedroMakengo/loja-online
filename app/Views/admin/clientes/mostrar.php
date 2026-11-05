<?php /** @var array $cliente @var array $pedidos @var array $enderecos @var array $favoritos @var array $totais */ use App\Servicos\Pagamentos; $c = $cliente; ?>
<a href="<?= url('/admin/clientes') ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Clientes</a>
<?php $tituloAdmin = $c['nome']; $subtituloAdmin = e($c['email']) . ', cliente desde ' . e(strftime_pt($c['criado_em'])); require dirname(__DIR__) . '/_titulo.php'; ?>
<div class="grid gap-6 xl:grid-cols-[1fr_340px]">
    <div class="space-y-6 min-w-0">
        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="cartao kpi p-4"><dt class="text-sm text-suave">Pedidos</dt><dd class="font-display kpi-valor font-extrabold num"><?= (int) $totais['pedidos'] ?></dd></div>
            <div class="cartao kpi p-4"><dt class="text-sm text-suave">Valor gasto</dt><dd class="preco kpi-valor"><?= kz($totais['gasto']) ?></dd></div>
            <div class="cartao kpi p-4"><dt class="text-sm text-suave">Último acesso</dt><dd class="font-semibold num"><?= $c['ultimo_login_em'] ? date('d/m/Y H:i', strtotime($c['ultimo_login_em'])) : '—' ?></dd></div>
        </dl>
        <section class="cartao overflow-hidden">
            <h2 class="border-b border-linha px-5 py-4 font-display text-lg font-bold">Pedidos</h2>
            <ul class="divide-y divide-linha">
                <?php foreach ($pedidos as $pd): $estadoPedido = $pd['estado']; ?>
                <li class="relative flex items-center justify-between gap-3 px-5 py-3 hover:bg-elevado/50 text-sm">
                    <div><a href="<?= url('/admin/pedidos/' . $pd['numero']) ?>" class="font-semibold num hover:text-marca after:absolute after:inset-0"><?= e($pd['numero']) ?></a>
                        <p class="text-xs text-suave"><?= date('d/m/Y', strtotime($pd['criado_em'])) ?>, <?= e(Pagamentos::nome($pd['metodo_pagamento'])) ?></p></div>
                    <div class="text-right"><p class="font-semibold num"><?= kz($pd['total']) ?></p><?php require RAIZ . '/app/Views/conta/_estado.php'; ?></div>
                </li>
                <?php endforeach; ?>
                <?php if (!$pedidos): ?><li class="px-5 py-6 text-center text-sm text-suave">Ainda sem pedidos.</li><?php endif; ?>
            </ul>
        </section>
        <section class="cartao p-5">
            <h2 class="font-display text-lg font-bold">Pontos <span class="text-base font-normal text-suave num">(saldo <?= number_format((int) $c['pontos'], 0, ',', ' ') ?>)</span></h2>
            <?php if ($pontos): ?>
            <ul class="mt-3 space-y-1.5 text-sm">
                <?php foreach ($pontos as $m): ?>
                <li class="flex justify-between gap-3"><span><span class="text-suave num"><?= date('d/m/Y', strtotime($m['criado_em'])) ?></span> <?= e($m['descricao']) ?></span><span class="font-semibold num <?= $m['pontos'] > 0 ? 'text-sucesso' : 'text-suave' ?>"><?= $m['pontos'] > 0 ? '+' : '' ?><?= (int) $m['pontos'] ?></span></li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?><p class="mt-2 text-sm text-suave">Sem movimentos.</p><?php endif; ?>
            <?php if (\Core\AdminAuth::pode('pagamentos')): ?>
            <form action="<?= url('/admin/clientes/' . $c['id'] . '/pontos') ?>" method="post" class="mt-4 flex flex-wrap gap-2 border-t border-linha pt-4">
                <?= \Core\Csrf::campo() ?>
                <label for="aj-p" class="sr-only">Pontos</label>
                <input id="aj-p" name="pontos" type="number" placeholder="+100 ou -50" class="campo h-10 w-32 num" required>
                <label for="aj-m" class="sr-only">Motivo</label>
                <input id="aj-m" name="motivo" maxlength="150" placeholder="Motivo (o cliente vê)" class="campo h-10 flex-1 min-w-[12rem]" required>
                <button class="btn-secundario h-10">Ajustar</button>
            </form>
            <?php endif; ?>
        </section>

        <?php if ($favoritos): ?>
        <section class="cartao p-5"><h2 class="font-display text-lg font-bold">Favoritos</h2>
            <ul class="mt-3 space-y-1 text-sm"><?php foreach ($favoritos as $f): ?><li class="flex justify-between gap-3"><span><?= e($f['nome']) ?></span><span class="num text-suave"><?= kz($f['preco']) ?></span></li><?php endforeach; ?></ul>
        </section>
        <?php endif; ?>
    </div>
    <div class="space-y-6">
        <section class="cartao p-5 text-sm space-y-1">
            <h2 class="font-display text-lg font-bold mb-2">Contacto</h2>
            <p class="num"><?= e(telefoneFormatado($c['telefone'])) ?></p>
            <p><?= e($c['email']) ?></p>
            <?php if ($c['nif']): ?><p>NIF <span class="num"><?= e($c['nif']) ?></span></p><?php endif; ?>
            <p class="text-suave"><?= $c['aceita_marketing'] ? 'Aceita receber promoções' : 'Não aceita promoções por email' ?></p>

            <a href="<?= e('https://wa.me/' . preg_replace('/\D/', '', (string) $c['telefone'])) ?>" target="_blank" rel="noopener" class="btn-whatsapp mt-3 h-9 px-3 text-xs"><?= icone('whatsapp', 'h-4 w-4') ?> WhatsApp</a>
        </section>
        <?php foreach ($enderecos as $end): ?>
        <section class="cartao p-5"><?php if ($end['principal']): ?><span class="selo-novo mb-2">Principal</span><?php endif; ?><?php require RAIZ . '/app/Views/conta/_endereco.php'; ?></section>
        <?php endforeach; ?>
        <?php if (\Core\AdminAuth::pode('pagamentos')): ?>
        <form action="<?= url('/admin/clientes/' . $c['id'] . '/estado') ?>" method="post" data-confirmar="<?= $c['estado'] === 'activo' ? 'Bloquear esta conta? O cliente deixa de conseguir entrar.' : 'Reactivar esta conta?' ?>">
            <?= \Core\Csrf::campo() ?>
            <button class="btn-fantasma w-full <?= $c['estado'] === 'activo' ? 'text-coral' : 'text-sucesso' ?>"><?= $c['estado'] === 'activo' ? 'Bloquear conta' : 'Reactivar conta' ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
