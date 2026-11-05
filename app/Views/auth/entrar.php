<?php /** @var string $voltar */ ?>
<div class="container py-8 sm:py-12">
    <div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-2 lg:items-stretch">
        <section class="cartao p-6 sm:p-8" aria-labelledby="t-entrar">
            <h1 id="t-entrar" class="font-display text-3xl font-extrabold">Entrar</h1>
            <p class="mt-1 text-suave">Use o email e a senha da sua conta DIOTEKNO.</p>

            <form action="<?= url('/entrar') ?>" method="post" class="mt-6 space-y-4" novalidate>
                <?= \Core\Csrf::campo() ?>
                <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
                <div>
                    <label for="email" class="rotulo">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" inputmode="email" value="<?= antigo('email') ?>" class="campo" <?= ariaErro('email') ?> autofocus>
                    <?= erroCampo('email') ?>
                </div>
                <?php $campoSenha = ['senha', 'senha', 'Senha', 'current-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                <div class="flex items-center justify-between gap-3 text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="lembrar" value="1" class="h-4 w-4 rounded accent-[rgb(var(--marca))]"> Manter sessão iniciada
                    </label>
                    <a href="<?= url('/recuperar-senha') ?>" class="link">Esqueci-me da senha</a>
                </div>
                <button class="btn-primario h-12 w-full text-base">Entrar</button>
            </form>

            <p class="mt-6 border-t border-linha pt-6 text-center text-sm text-suave">
                Ainda não tem conta?
                <a href="<?= url('/registar' . ($voltar !== '/conta' ? '?voltar=' . urlencode($voltar) : '')) ?>" class="link">Criar conta</a>
            </p>
        </section>
        <?php require __DIR__ . '/_vantagens.php'; ?>
    </div>
</div>
