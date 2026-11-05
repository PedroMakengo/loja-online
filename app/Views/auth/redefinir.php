<?php /** @var string $token @var bool $valido */ ?>
<div class="container py-8 sm:py-12">
    <section class="mx-auto max-w-md cartao p-6 sm:p-8" aria-labelledby="t-red">
        <?php if (!$valido): ?>
            <h1 id="t-red" class="font-display text-2xl font-extrabold">Este link já não é válido</h1>
            <p class="mt-2 text-suave">Os links de recuperação expiram ao fim de 60 minutos e só podem ser usados uma vez.</p>
            <a href="<?= url('/recuperar-senha') ?>" class="btn-primario mt-6 w-full">Pedir novo link</a>
        <?php else: ?>
            <h1 id="t-red" class="font-display text-2xl font-extrabold">Definir nova senha</h1>
            <p class="mt-2 text-suave">Depois de guardar, fica com a sessão iniciada.</p>
            <form action="<?= url('/redefinir-senha/' . $token) ?>" method="post" class="mt-6 space-y-4" novalidate>
                <?= \Core\Csrf::campo() ?>
                <?php $campoSenha = ['senha', 'senha', 'Nova senha', 'new-password', 'Mínimo 8 caracteres, com letras e números.']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                <?php $campoSenha = ['senha_confirmacao', 'senha_confirmacao', 'Repetir nova senha', 'new-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                <button class="btn-primario h-12 w-full">Guardar nova senha</button>
            </form>
        <?php endif; ?>
    </section>
</div>
