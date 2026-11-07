<?php
/**
 * Botão "Continuar com o Google" + separador "ou".
 * @var string $voltar
 * @var string $acaoGoogle  'entrar' | 'registar' (só muda o texto de apoio)
 */
if (!\Core\Google::activo()) {
    return;
}
$linkGoogle = url('/entrar/google' . ($voltar !== '/conta' ? '?voltar=' . urlencode($voltar) : ''));
?>
<div class="mt-6">
    <a href="<?= e($linkGoogle) ?>" class="flex h-12 w-full items-center justify-center gap-3 rounded-xl border border-linha bg-white px-4 text-base font-semibold text-[#1f1f1f] shadow-sm transition hover:bg-[#f8f9fa] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--marca))] focus-visible:ring-offset-2">
        <?php require RAIZ . '/app/Views/partials/google-logo.php'; ?>
        <span>Continuar com o Google</span>
    </a>
    <?php if (($acaoGoogle ?? 'entrar') === 'registar'): ?>
        <p class="mt-2 text-center text-xs text-suave">
            Ao continuar com o Google, aceita os <a href="<?= url('/termos') ?>" class="link" target="_blank">termos e condições</a>
            e a <a href="<?= url('/privacidade') ?>" class="link" target="_blank">política de privacidade</a>.
        </p>
    <?php endif; ?>
    <div class="mt-6 flex items-center gap-3 text-xs uppercase tracking-wide text-suave" role="separator">
        <span class="h-px flex-1 bg-linha"></span>
        <span>ou com email</span>
        <span class="h-px flex-1 bg-linha"></span>
    </div>
</div>
