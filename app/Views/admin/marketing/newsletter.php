<?php
/** @var array $campanhas @var int $destinatarios @var array $subscritores @var int $clientesAceitam @var int $pendentes @var array $cfg */
$tituloAdmin = 'Newsletter';
$accoesAdmin = '<a href="' . url('/admin/newsletter/exportar') . '" class="btn-secundario">Exportar contactos</a><a href="' . url('/admin/newsletter/nova') . '" class="btn-primario">' . icone('mais', 'h-4 w-4') . ' Nova campanha</a>';
require dirname(__DIR__) . '/_titulo.php';
$estados = ['rascunho' => ['Rascunho', 'bg-elevado text-suave'], 'a_enviar' => ['A enviar', 'bg-aviso/15 text-aviso'], 'enviada' => ['Enviada', 'bg-sucesso/15 text-sucesso']];
?>
<dl class="grid grid-cols-2 xl:grid-cols-4 gap-3">
    <div class="cartao p-4"><dt class="text-sm text-suave">Vão receber</dt><dd class="font-display text-2xl font-extrabold num"><?= $destinatarios ?></dd><dd class="text-xs text-suave">Emails únicos</dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">Subscritores da newsletter</dt><dd class="font-display text-2xl font-extrabold num"><?= (int) ($subscritores['activo'] ?? 0) ?></dd><dd class="text-xs text-suave num"><?= (int) ($subscritores['cancelado'] ?? 0) ?> cancelaram</dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">Clientes que aceitam promoções</dt><dd class="font-display text-2xl font-extrabold num"><?= $clientesAceitam ?></dd></div>
    <div class="cartao p-4"><dt class="text-sm text-suave">Na fila de envio</dt><dd class="font-display text-2xl font-extrabold num"><?= $pendentes ?></dd>
        <dd class="text-xs text-suave">Até <?= $cfg['limite_por_hora'] ?> por hora<?php if ($pendentes): ?>, <form action="<?= url('/admin/newsletter/processar') ?>" method="post" class="inline"><?= \Core\Csrf::campo() ?><button class="link">enviar lote agora</button></form><?php endif; ?></dd></div>
</dl>

<div class="mt-6 cartao overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-elevado text-left text-xs text-suave"><tr><th class="px-4 py-3 font-semibold">Campanha</th><th class="px-4 py-3 font-semibold">Estado</th><th class="px-4 py-3 font-semibold text-right">Enviados</th><th class="px-4 py-3 font-semibold">Data</th></tr></thead>
    <tbody class="divide-y divide-linha">
        <?php foreach ($campanhas as $c): ?>
        <tr class="relative hover:bg-elevado/50">
            <td class="px-4 py-3"><a href="<?= url('/admin/newsletter/' . $c['id']) ?>" class="font-semibold hover:text-marca after:absolute after:inset-0"><?= e($c['assunto']) ?></a></td>
            <td class="px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-semibold <?= $estados[$c['estado']][1] ?>"><?= $estados[$c['estado']][0] ?></span></td>
            <td class="px-4 py-3 text-right num"><?= $c['estado'] === 'rascunho' ? '—' : (int) $c['enviados'] . ' / ' . (int) $c['total'] ?></td>
            <td class="px-4 py-3 text-suave num"><?= date('d/m/Y', strtotime($c['enviada_em'] ?? $c['criado_em'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$campanhas): ?><tr><td colspan="4" class="px-4 py-10 text-center text-suave">Ainda não criou campanhas. Comece por uma com as promoções da semana.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<p class="mt-3 text-xs text-suave">Cada email leva um link para cancelar a subscrição com um clique, como exigem o Gmail e a Lei de Protecção de Dados Pessoais.</p>
