<?php /** Página provisória para módulos das fases seguintes. @var string $tituloPagina @var string $introPagina */ ?>
<div class="container pt-10 pb-16">
    <div class="mx-auto max-w-lg cartao p-8 text-center">
        <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-marca-suave text-marca"><?= icone('relogio', 'h-7 w-7') ?></span>
        <h1 class="font-display text-2xl font-extrabold"><?= e($tituloPagina) ?></h1>
        <p class="mt-2 text-suave"><?= e($introPagina) ?></p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="<?= url('/carrinho') ?>" class="btn-primario">Ir para o carrinho</a>
            <a href="<?= e(linkWhatsapp('Olá! Quero concluir uma compra.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp"><?= icone('whatsapp') ?> Comprar pelo WhatsApp</a>
        </div>
    </div>
</div>
