<?php /** @var array $cliente */ $antigo = \Core\Flash::antigo(); $val = fn ($c) => e((string) ($antigo[$c] ?? $cliente[$c] ?? '')); ?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>
        <div class="min-w-0 max-w-2xl space-y-6">
            <h1 class="font-display text-3xl font-extrabold">Dados pessoais</h1>

            <form action="<?= url('/conta/dados') ?>" method="post" class="cartao p-5 sm:p-6 space-y-4" novalidate>
                <?= \Core\Csrf::campo() ?>
                <div>
                    <label for="nome" class="rotulo">Nome completo</label>
                    <input id="nome" name="nome" required autocomplete="name" value="<?= $val('nome') ?>" class="campo" <?= ariaErro('nome') ?>>
                    <?= erroCampo('nome') ?>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email" class="rotulo">Email</label>
                        <input id="email" name="email" type="email" required autocomplete="email" value="<?= $val('email') ?>" class="campo" <?= ariaErro('email') ?>>
                        <?= erroCampo('email') ?>
                    </div>
                    <div>
                        <label for="telefone" class="rotulo">Telemóvel</label>
                        <input id="telefone" name="telefone" type="tel" required autocomplete="tel-national" inputmode="tel"
                               value="<?= e(isset($antigo['telefone']) ? $antigo['telefone'] : telefoneFormatado($cliente['telefone'])) ?>" class="campo num" <?= ariaErro('telefone') ?>>
                        <?= erroCampo('telefone') ?>
                    </div>
                </div>
                <div>
                    <label for="nif" class="rotulo">NIF <span class="font-normal text-suave">(opcional)</span></label>
                    <input id="nif" name="nif" autocomplete="off" value="<?= $val('nif') ?>" class="campo uppercase num" <?= ariaErro('nif') ?>>
                    <?php if (!isset(\Core\Flash::erros()['nif'])): ?><p class="mt-1.5 text-xs text-suave">Fica pré-preenchido nas facturas. Pode indicar outro NIF em cada compra.</p><?php endif; ?>
                    <?= erroCampo('nif') ?>
                </div>
                <label class="flex items-start gap-2.5 text-sm cursor-pointer">
                    <input type="checkbox" name="aceita_marketing" value="1" <?= ($antigo['aceita_marketing'] ?? $cliente['aceita_marketing']) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded accent-[rgb(var(--marca))]">
                    Quero receber promoções por email.
                </label>
                <label class="flex items-start gap-2.5 text-sm cursor-pointer">
                    <input type="checkbox" name="recebe_lembretes" value="1" <?= ($antigo['recebe_lembretes'] ?? $cliente['recebe_lembretes']) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded accent-[rgb(var(--marca))]">
                    Avisar-me por email quando deixar produtos no carrinho.
                </label>
                <button class="btn-primario">Guardar dados</button>
            </form>

            <form id="senha" action="<?= url('/conta/senha') ?>" method="post" class="cartao p-5 sm:p-6 space-y-4 scroll-mt-40" novalidate>
                <?= \Core\Csrf::campo() ?>
                <?php $semSenha = (int) ($cliente['senha_definida'] ?? 1) === 0; ?>
                <div>
                    <h2 class="font-display text-lg font-bold"><?= $semSenha ? 'Definir senha' : 'Alterar senha' ?></h2>
                    <p class="text-sm text-suave"><?= $semSenha
                        ? 'A sua conta foi criada com o Google. Defina uma senha se quiser também entrar com email e senha.'
                        : 'Ao alterar, as sessões abertas noutros dispositivos terminam.' ?></p>
                </div>
                <?php if (!empty($cliente['google_id'])): ?>
                    <p class="flex items-center gap-2 rounded-xl border border-linha bg-elevado px-3 py-2 text-sm">
                        <?php require RAIZ . '/app/Views/partials/google-logo.php'; ?>
                        <span>Conta ligada ao Google — pode entrar com o botão <strong>Continuar com o Google</strong>.</span>
                    </p>
                <?php endif; ?>
                <?php if (!$semSenha): ?>
                    <?php $campoSenha = ['senha_actual', 'senha_actual', 'Senha actual', 'current-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                <?php endif; ?>
                <div class="grid gap-4 sm:grid-cols-2">
                    <?php $campoSenha = ['senha', 'senha-nova', 'Nova senha', 'new-password', 'Mínimo 8 caracteres, com letras e números.']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                    <?php $campoSenha = ['senha_confirmacao', 'senha-conf', 'Repetir nova senha', 'new-password']; require RAIZ . '/app/Views/partials/campo-senha.php'; ?>
                </div>
                <button class="btn-secundario"><?= $semSenha ? 'Definir senha' : 'Alterar senha' ?></button>
            </form>
        </div>
    </div>
</div>
