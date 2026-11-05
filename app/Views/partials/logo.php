<?php
/* Logótipo. $tamanho opcional ('sm'|'md'); $claro = true em fundos sempre escuros (rodapé).
   Sem $claro, troca sozinho para a versão clara no modo escuro. */
$tamanho = $tamanho ?? 'md';
$claro   = $claro ?? false;
$classe  = ($tamanho === 'sm' ? 'h-8' : 'h-9 sm:h-10') . ' w-auto';
$img = function (string $ficheiro, string $extra = '') use ($classe) { ?>
    <picture class="<?= $extra ?>">
        <source srcset="<?= asset("img/$ficheiro.webp") ?>" type="image/webp">
        <img src="<?= asset("img/$ficheiro.png") ?>" alt="<?= e(NOME_SITE) ?>" class="<?= $classe ?>" width="540" height="120" decoding="async">
    </picture>
<?php };
?>
<span class="inline-flex items-center">
    <?php if ($claro): $img('logo-claro');
    else: $img('logo', 'dark:hidden'); $img('logo-claro', 'hidden dark:block'); endif; ?>
</span>
<?php $claro = false; unset($img); ?>
