<?php /** @var ?string $enviado */ ?>
<div class="container py-8 sm:py-12">
    <section class="mx-auto max-w-md cartao p-6 sm:p-8" aria-labelledby="t-rec">
        <?php if (!empty($enviado)): ?>
            <span class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-marca-suave text-marca"><?= icone('email', 'h-6 w-6') ?></span>
            <h1 id="t-rec" class="font-display text-2xl font-extrabold">Verifique o seu email</h1>
            <p class="mt-2 text-suave">Se existir uma conta com <strong class="text-tinta"><?= e($enviado) ?></strong>, enviámos um link para definir uma nova senha. O link é válido durante 60 minutos.</p>
            <p class="mt-4 text-sm text-suave">Não chegou? Veja a pasta de spam ou <a href="<?= url('/recuperar-senha') ?>" class="link">tente de novo</a>.</p>
            <a href="<?= url('/entrar') ?>" class="btn-secundario mt-6 w-full">Voltar a entrar</a>
        <?php else: ?>
            <h1 id="t-rec" class="font-display text-2xl font-extrabold">Recuperar a senha</h1>
            <p class="mt-2 text-suave">Indique o email da conta. Enviamos um link para definir uma nova senha.</p>
            <form action="<?= url('/recuperar-senha') ?>" method="post" class="mt-6 space-y-4" novalidate>
                <?= \Core\Csrf::campo() ?>
                <div>
                    <label for="email" class="rotulo">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" value="<?= antigo('email') ?>" class="campo" <?= ariaErro('email') ?> autofocus>
                    <?= erroCampo('email') ?>
                </div>
                <button class="btn-primario h-12 w-full">Enviar link</button>
            </form>
            <p class="mt-6 text-center text-sm"><a href="<?= url('/entrar') ?>" class="link">Voltar a entrar</a></p>
        <?php endif; ?>
    </section>
</div>
