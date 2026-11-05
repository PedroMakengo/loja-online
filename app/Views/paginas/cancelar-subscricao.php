<?php /** @var string $tipo @var ?string $email @var ?string $acao @var bool $feito */ ?>
<div class="container py-10 sm:py-16">
    <section class="mx-auto max-w-md cartao p-6 sm:p-8 text-center">
        <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone($feito ? 'check' : 'email', 'h-7 w-7') ?></span>
        <?php if ($feito): ?>
            <h1 class="font-display text-2xl font-extrabold"><?= $tipo === 'newsletter' ? 'Subscrição cancelada' : 'Lembretes desligados' ?></h1>
            <p class="mt-2 text-suave"><?= $tipo === 'newsletter'
                ? 'Não vai receber mais promoções da DIOTEKNO em ' . e($email) . '. Os emails sobre as suas encomendas continuam a chegar.'
                : 'Não vai receber mais lembretes sobre produtos no carrinho. Pode voltar a ligá-los em "Dados pessoais", na sua conta.' ?></p>
            <a href="<?= url('/') ?>" class="btn-secundario mt-6">Voltar à loja</a>
        <?php else: ?>
            <h1 class="font-display text-2xl font-extrabold"><?= $tipo === 'newsletter' ? 'Deixar de receber promoções?' : 'Desligar lembretes do carrinho?' ?></h1>
            <p class="mt-2 text-suave"><?= $tipo === 'newsletter'
                ? 'Vamos deixar de enviar a newsletter para <strong class="text-tinta">' . e($email) . '</strong>.'
                : 'Deixamos de o avisar quando deixar produtos no carrinho.' ?></p>
            <form action="<?= e($acao) ?>" method="post" class="mt-6">
                <button class="btn-primario w-full"><?= $tipo === 'newsletter' ? 'Sim, cancelar a subscrição' : 'Sim, desligar lembretes' ?></button>
            </form>
            <a href="<?= url('/') ?>" class="mt-3 inline-block text-sm text-suave hover:text-tinta">Afinal quero continuar a receber</a>
        <?php endif; ?>
    </section>
</div>
