<?php /** @var array $mensagens @var array $pg @var bool $so */ $tituloAdmin = 'Mensagens do formulário de contacto'; require __DIR__ . '/_titulo.php'; ?>
<nav class="mb-4 flex gap-2"><a href="<?= url('/admin/mensagens') ?>" class="chip <?= !$so ? 'chip-activo' : '' ?>">Todas</a><a href="<?= url('/admin/mensagens?estado=nao-lidas') ?>" class="chip <?= $so ? 'chip-activo' : '' ?>">Por ler</a></nav>
<div class="space-y-3">
    <?php foreach ($mensagens as $m): ?>
    <article class="cartao p-5 <?= $m['lido'] ? 'opacity-70' : 'border-marca/40' ?>">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-semibold"><?= e($m['assunto']) ?> <?php if (!$m['lido']): ?><span class="selo-novo ml-1">Nova</span><?php endif; ?></p>
                <p class="text-sm text-suave"><?= e($m['nome']) ?>, <a href="mailto:<?= e($m['email']) ?>" class="link font-normal"><?= e($m['email']) ?></a><?= $m['telefone'] ? ', <span class="num">' . e($m['telefone']) . '</span>' : '' ?></p>
            </div>
            <p class="text-xs text-suave num"><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></p>
        </div>
        <p class="mt-3 whitespace-pre-line text-sm leading-6"><?= e($m['mensagem']) ?></p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['assunto']) ?>" class="btn-secundario h-9 px-3 text-xs"><?= icone('email', 'h-4 w-4') ?> Responder por email</a>
            <?php if ($m['telefone'] && telefoneAngola($m['telefone'])): ?><a href="https://wa.me/<?= preg_replace('/\D/', '', telefoneAngola($m['telefone'])) ?>" target="_blank" rel="noopener" class="btn-whatsapp h-9 px-3 text-xs"><?= icone('whatsapp', 'h-4 w-4') ?> WhatsApp</a><?php endif; ?>
            <form action="<?= url('/admin/mensagens/' . $m['id'] . '/lida') ?>" method="post"><?= \Core\Csrf::campo() ?><button class="btn-fantasma h-9 px-3 text-xs"><?= $m['lido'] ? 'Marcar como por ler' : 'Marcar como lida' ?></button></form>
        </div>
    </article>
    <?php endforeach; ?>
    <?php if (!$mensagens): ?><p class="cartao p-8 text-center text-suave">Não há mensagens<?= $so ? ' por ler' : '' ?>.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/_paginacao.php'; ?>
