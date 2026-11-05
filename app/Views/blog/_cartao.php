<?php /** Cartão de artigo. Espera $post */ ?>
<article class="group relative flex flex-col cartao overflow-hidden hover:border-tinta/25 transition-colors">
    <div class="aspect-[16/9] overflow-hidden"><?php $grande = false; require __DIR__ . '/_capa.php'; ?></div>
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-semibold text-marca"><?= e($post['categoria_nome']) ?></p>
        <h3 class="mt-1 font-display text-lg font-bold leading-snug group-hover:text-marca">
            <a href="<?= url('/blog/' . $post['slug']) ?>" class="after:absolute after:inset-0"><?= e($post['titulo']) ?></a>
        </h3>
        <?php if ($post['resumo']): ?><p class="mt-2 text-sm text-suave line-clamp-3"><?= e($post['resumo']) ?></p><?php endif; ?>
        <p class="mt-auto pt-4 text-xs text-suave num"><?= e(strftime_pt($post['publicado_em'], true)) ?>, <?= \App\Models\Blog::minutosLeitura((string) $post['conteudo']) ?> min de leitura</p>
    </div>
</article>
