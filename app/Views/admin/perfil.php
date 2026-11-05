<?php /** @var array $admin */ $tituloAdmin = 'O meu perfil'; require __DIR__ . '/_titulo.php'; ?>
<div class="grid gap-6 lg:grid-cols-2 max-w-4xl">
    <section class="cartao p-5">
        <dl class="space-y-3 text-sm">
            <div><dt class="text-suave">Nome</dt><dd class="font-semibold"><?= e($admin['nome']) ?></dd></div>
            <div><dt class="text-suave">Email</dt><dd class="font-semibold"><?= e($admin['email']) ?></dd></div>
            <div><dt class="text-suave">Papel</dt><dd class="font-semibold"><?= e(\Core\AdminAuth::PAPEIS[$admin['papel']]) ?></dd></div>
        </dl>
    </section>
    <form action="<?= url('/admin/perfil/senha') ?>" method="post" class="cartao p-5 space-y-4" novalidate>
        <?= \Core\Csrf::campo() ?>
        <h2 class="font-display text-lg font-bold">Alterar senha</h2>
        <?php if ($admin['deve_mudar_senha']): ?><p class="rounded-xl bg-aviso/10 p-3 text-sm text-aviso">Está a usar uma senha temporária. Defina uma senha pessoal para continuar.</p><?php endif; ?>
        <?php $campoSenha = ['senha_actual', 'senha_actual', 'Senha actual', 'current-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
        <?php $campoSenha = ['senha', 'senha-nova', 'Nova senha', 'new-password', 'Mínimo 10 caracteres, com letras e números.']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
        <?php $campoSenha = ['senha_confirmacao', 'senha-conf', 'Repetir nova senha', 'new-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
        <button class="btn-primario">Guardar senha</button>
    </form>
</div>
