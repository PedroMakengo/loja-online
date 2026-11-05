<?php /** @var string $voltar */ ?>
<div class="container py-8 sm:py-12">
    <div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-2 lg:items-stretch">
        <section class="cartao p-6 sm:p-8" aria-labelledby="t-registar">
            <h1 id="t-registar" class="font-display text-3xl font-extrabold">Criar conta</h1>
            <p class="mt-1 text-suave">Demora menos de um minuto.</p>

            <form action="<?= url('/registar') ?>" method="post" class="mt-6 space-y-4" novalidate>
                <?= \Core\Csrf::campo() ?>
                <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
                <div>
                    <label for="nome" class="rotulo">Nome completo</label>
                    <input id="nome" name="nome" required autocomplete="name" value="<?= antigo('nome') ?>" class="campo" <?= ariaErro('nome') ?>>
                    <?= erroCampo('nome') ?>
                </div>
                <div>
                    <label for="email" class="rotulo">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" inputmode="email" value="<?= antigo('email') ?>" class="campo" <?= ariaErro('email') ?>>
                    <?= erroCampo('email') ?>
                </div>
                <div>
                    <label for="telefone" class="rotulo">Telemóvel</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-xl border border-r-0 border-linha bg-elevado px-3 text-sm text-suave num">+244</span>
                        <input id="telefone" name="telefone" type="tel" required autocomplete="tel-national" inputmode="tel" placeholder="923 456 789" value="<?= antigo('telefone') ?>" class="campo rounded-l-none num" <?= ariaErro('telefone') ?>>
                    </div>
                    <?php if (!isset(\Core\Flash::erros()['telefone'])): ?><p class="mt-1.5 text-xs text-suave">Usamos para combinar a entrega e para o Multicaixa Express.</p><?php endif; ?>
                    <?= erroCampo('telefone') ?>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <?php $campoSenha = ['senha', 'senha', 'Senha', 'new-password', 'Mínimo 8 caracteres, com letras e números.']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                    <?php $campoSenha = ['senha_confirmacao', 'senha_confirmacao', 'Repetir senha', 'new-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                </div>
                <div class="space-y-2.5 text-sm">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="aceita_termos" value="1" <?= antigo('aceita_termos') ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded accent-[rgb(var(--marca))]" <?= ariaErro('aceita_termos') ?>>
                        <span>Li e aceito os <a href="<?= url('/termos') ?>" class="link" target="_blank">termos e condições</a> e a <a href="<?= url('/privacidade') ?>" class="link" target="_blank">política de privacidade</a>.</span>
                    </label>
                    <?= erroCampo('aceita_termos') ?>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="aceita_marketing" value="1" <?= antigo('aceita_marketing') ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded accent-[rgb(var(--marca))]">
                        <span class="text-suave">Quero receber promoções por email (opcional).</span>
                    </label>
                </div>
                <button class="btn-primario h-12 w-full text-base">Criar conta</button>
            </form>

            <p class="mt-6 border-t border-linha pt-6 text-center text-sm text-suave">
                Já tem conta? <a href="<?= url('/entrar' . ($voltar !== '/conta' ? '?voltar=' . urlencode($voltar) : '')) ?>" class="link">Entrar</a>
            </p>
        </section>
        <?php require __DIR__ . '/_vantagens.php'; ?>
    </div>
</div>
