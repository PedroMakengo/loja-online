<?php /** @var string $voltar */ ?>
<div class="min-h-screen flex items-center justify-center p-4 bg-grafite">
    <section class="w-full max-w-sm cartao p-6 sm:p-8" aria-labelledby="t-admin">
        <div class="mb-6 flex items-center justify-between">
            <?php $tamanho = 'md'; require RAIZ . '/app/Views/partials/logo.php'; ?>
            <span class="rounded-md bg-elevado px-2 py-0.5 text-xs font-semibold text-suave">Painel</span>
        </div>
        <h1 id="t-admin" class="font-display text-2xl font-extrabold">Entrar no painel</h1>
        <form action="<?= url('/admin/entrar') ?>" method="post" class="mt-6 space-y-4" novalidate>
            <?= \Core\Csrf::campo() ?>
            <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
            <div>
                <label for="email" class="rotulo">Email</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="<?= antigo('email') ?>" class="campo" <?= ariaErro('email') ?> autofocus>
                <?= erroCampo('email') ?>
            </div>
            <?php $campoSenha = ['senha', 'senha', 'Senha', 'current-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
            <button class="btn-primario h-12 w-full">Entrar</button>
        </form>
    </section>
</div>
