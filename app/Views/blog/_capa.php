<?php
/** Capa do artigo: fotografia, ou capa tipográfica na cor da categoria. Espera $post, $grande (bool) */
$grande = $grande ?? false;
$cores = ['guias-de-compra' => 'bg-[#2346F5]', 'dicas' => 'bg-[#0F5E47]', 'noticias' => 'bg-[#C9352B]', 'tecnologia' => 'bg-grafite'];
$icones = ['guias-de-compra' => 'loja', 'dicas' => 'estrela', 'noticias' => 'email', 'tecnologia' => 'grelha'];
?>
<?php if (!empty($post['imagem'])): ?>
    <img src="<?= asset('img/' . $post['imagem']) ?>" alt="" class="h-full w-full object-cover" loading="<?= $grande ? 'eager' : 'lazy' ?>" <?= $grande ? 'fetchpriority="high"' : '' ?>>
<?php else: ?>
    <div class="relative flex h-full w-full flex-col justify-between overflow-hidden <?= $cores[$post['categoria_slug']] ?? 'bg-grafite' ?> p-5 <?= $grande ? 'sm:p-8' : '' ?> text-white" aria-hidden="true">
        <div class="pointer-events-none absolute inset-0 opacity-[0.08] [background-image:linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] [background-size:40px_40px]"></div>
        <span class="relative text-xs font-semibold uppercase tracking-wider text-white/70"><?= e($post['categoria_nome']) ?></span>
        <span class="relative self-end text-white/25"><?= icone($icones[$post['categoria_slug']] ?? 'grelha', $grande ? 'h-28 w-28' : 'h-16 w-16') ?></span>
    </div>
<?php endif; ?>
