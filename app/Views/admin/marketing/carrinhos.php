<?php
/** @var array $carrinhos @var array $pg @var array $stats @var float $valorEmAberto @var array $cfg */
$tituloAdmin = 'Carrinhos abandonados';
$subtituloAdmin = 'Clientes com conta recebem um lembrete ' . plural($cfg['lembrete1_horas'], 'hora', 'horas') . ' depois de deixarem o carrinho e outro ' . plural($cfg['lembrete2_horas'], 'hora', 'horas') . ' depois' . ($cfg['cupao_lembrete2'] ? ', com ' . $cfg['cupao_lembrete2'] . '% de desconto' : '') . '.';
$accoesAdmin = '<form action="' . url('/admin/marketing/carrinhos/processar') . '" method="post">' . \Core\Csrf::campo() . '<button class="btn-secundario">Enviar lembretes devidos agora</button></form>';
require dirname(__DIR__) . '/_titulo.php';
$taxa = $stats['lembrados'] ? round($stats['convertidos'] / $stats['lembrados'] * 100) : 0;
?>
<dl class="grid grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="cartao kpi p-4"><dt class="text-sm text-suave">Valor em carrinhos parados</dt><dd class="preco kpi-valor"><?= kz($valorEmAberto) ?></dd><dd class="text-xs text-suave">Últimos 30 dias</dd></div>
    <div class="cartao kpi p-4"><dt class="text-sm text-suave">Carrinhos lembrados</dt><dd class="font-display kpi-valor font-extrabold num"><?= (int) $stats['lembrados'] ?></dd></div>
    <div class="cartao kpi p-4"><dt class="text-sm text-suave">Voltaram pelo email</dt><dd class="font-display kpi-valor font-extrabold num"><?= (int) $stats['clicados'] ?></dd></div>
    <div class="cartao kpi p-4"><dt class="text-sm text-suave">Compraram</dt><dd class="font-display kpi-valor font-extrabold num"><?= (int) $stats['convertidos'] ?> <span class="text-base text-suave"><?= $taxa ?>%</span></dd></div>
    <div class="cartao kpi p-4"><dt class="text-sm text-suave">Receita recuperada</dt><dd class="preco kpi-valor text-sucesso"><?= kz($stats['receita']) ?></dd><dd class="text-xs text-suave num"><?= plural($stats['n'], 'pedido pago', 'pedidos pagos') ?></dd></div>
</dl>

<div class="mt-6 cartao overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-elevado text-left text-xs text-suave"><tr>
        <th class="px-4 py-3 font-semibold">Cliente</th><th class="px-4 py-3 font-semibold">Produtos</th>
        <th class="px-4 py-3 font-semibold text-right">Valor</th><th class="px-4 py-3 font-semibold">Parado desde</th><th class="px-4 py-3 font-semibold">Lembretes</th><th class="px-4 py-3"></th>
    </tr></thead>
    <tbody class="divide-y divide-linha">
        <?php foreach ($carrinhos as $c): ?>
        <tr class="align-top">
            <td class="px-4 py-3">
                <?php if ($c['cliente_id']): ?>
                <a href="<?= url('/admin/clientes/' . $c['cliente_id']) ?>" class="font-semibold hover:text-marca"><?= e($c['nome']) ?></a>
                <?php if (!$c['recebe_lembretes']): ?><p class="text-xs text-suave">Desligou os lembretes</p><?php endif; ?>
                <?php else: ?><span class="text-suave">Visitante sem conta</span><?php endif; ?>
            </td>
            <td class="px-4 py-3 max-w-xs"><p class="line-clamp-2"><?= e($c['itens']) ?></p></td>
            <td class="px-4 py-3 text-right font-semibold num"><?= kz($c['valor']) ?></td>
            <td class="px-4 py-3 text-suave num whitespace-nowrap"><?= date('d/m H:i', strtotime($c['actualizado_em'])) ?></td>
            <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($c['lembretes_enviados'] == 2 && !$c['recuperacao_enviada_em']): ?><span class="text-xs text-suave">Ignorado</span>
                <?php else: ?><span class="num"><?= min(2, (int) $c['lembretes_enviados']) ?>/2</span><?php endif; ?>
                <?php if ($c['recuperado_em']): ?><p class="text-xs font-semibold text-sucesso">Abriu o link</p><?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right">
                <?php if ($c['cliente_id'] && $c['telefone']): ?>
                <a href="<?= e('https://wa.me/' . preg_replace('/\D/', '', $c['telefone']) . '?text=' . rawurlencode('Olá ' . explode(' ', $c['nome'])[0] . ', é da DIOTEKNO. Vimos que deixou produtos no carrinho. Podemos ajudar a concluir a compra?')) ?>" target="_blank" rel="noopener" class="btn-whatsapp h-8 px-3 text-xs"><?= icone('whatsapp', 'h-4 w-4') ?> Contactar</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$carrinhos): ?><tr><td colspan="6" class="px-4 py-10 text-center text-suave">Não há carrinhos abandonados nos últimos 30 dias.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<?php require dirname(__DIR__) . '/_paginacao.php'; ?>
<p class="mt-3 text-xs text-suave">Carrinhos de visitantes sem conta não recebem email porque a loja não tem o contacto. Carrinhos abaixo de <?= kz($cfg['carrinho_minimo']) ?> ou só com produtos esgotados são ignorados.</p>
