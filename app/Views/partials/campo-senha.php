<?php /** Campo de senha com botão mostrar/ocultar. $campoSenha = [name, id, label, autocomplete, ajuda?] */ [$nomeS, $idS, $rotuloS, $autoS] = $campoSenha; $ajudaS = $campoSenha[4] ?? null; ?>
<div>
    <label for="<?= e($idS) ?>" class="rotulo"><?= e($rotuloS) ?></label>
    <div class="relative">
        <input id="<?= e($idS) ?>" name="<?= e($nomeS) ?>" type="password" required autocomplete="<?= e($autoS) ?>" class="campo pr-12" <?= ariaErro($nomeS) ?>>
        <button type="button" class="absolute right-1 top-1 inline-flex h-9 w-10 items-center justify-center rounded-lg text-suave hover:text-tinta" data-mostrar-senha aria-label="Mostrar senha" aria-pressed="false">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
    </div>
    <?php if ($ajudaS && !isset(\Core\Flash::erros()[$nomeS])): ?><p class="mt-1.5 text-xs text-suave"><?= e($ajudaS) ?></p><?php endif; ?>
    <?= erroCampo($nomeS) ?>
</div>
