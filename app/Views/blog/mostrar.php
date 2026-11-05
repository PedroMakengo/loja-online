<?php
/** @var array $post @var string $html @var array $indice @var array $produtos @var array $relacionados @var int $minutos @var array $breadcrumb @var bool $preVisualizar */
$urlArtigo = URL_BASE . '/blog/' . $post['slug'];
$publico = $post['estado'] === 'publicado' && strtotime((string) $post['publicado_em']) <= time();
?>
<?php if ($preVisualizar && !$publico): ?>
<div class="bg-aviso/15 text-aviso"><div class="container py-2 text-sm font-semibold">Pré-visualização: este artigo <?= $post['estado'] === 'rascunho' ? 'é um rascunho' : 'está agendado para ' . date('d/m/Y H:i', strtotime((string) $post['publicado_em'])) ?> e não está visível para os clientes. <a href="<?= url('/admin/blog/' . $post['id']) ?>" class="underline">Voltar ao editor</a></div></div>
<?php endif; ?>
<article class="container pt-6 pb-14" itemscope itemtype="https://schema.org/BlogPosting">
    <nav aria-label="Caminho" class="text-sm text-suave">
        <ol class="flex flex-wrap items-center gap-1">
            <?php foreach (array_slice($breadcrumb, 0, -1) as [$nome, $href]): ?>
            <li class="flex items-center gap-1"><a href="<?= url($href) ?>" class="hover:text-tinta"><?= e($nome) ?></a><?= icone('seta-dir', 'h-3.5 w-3.5') ?></li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <header class="mx-auto mt-4 max-w-3xl">
        <a href="<?= url('/blog/categoria/' . $post['categoria_slug']) ?>" class="text-sm font-semibold text-marca hover:underline"><?= e($post['categoria_nome']) ?></a>
        <h1 class="mt-2 font-display text-3xl sm:text-[2.6rem] font-extrabold leading-[1.1]" itemprop="headline"><?= e($post['titulo']) ?></h1>
        <?php if ($post['resumo']): ?><p class="mt-4 text-lg text-suave" itemprop="description"><?= e($post['resumo']) ?></p><?php endif; ?>
        <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-suave">
            <span>Equipa <?= e(NOME_SITE) ?></span>
            <time datetime="<?= date('c', strtotime((string) $post['publicado_em'])) ?>" class="num"><?= e(strftime_pt((string) $post['publicado_em'], true)) ?></time>
            <?php if (strtotime((string) $post['actualizado_em']) - strtotime((string) $post['publicado_em']) > 86400 * 2): ?>
            <span class="num">Actualizado a <?= e(strftime_pt((string) $post['actualizado_em'], true)) ?></span>
            <?php endif; ?>
            <span><?= $minutos ?> min de leitura</span>
        </p>
    </header>

    <div class="mx-auto mt-8 max-w-5xl aspect-[21/9] overflow-hidden rounded-2xl"><?php $grande = true; require __DIR__ . '/_capa.php'; ?></div>

    <div class="mx-auto mt-10 grid max-w-5xl gap-10 lg:grid-cols-[1fr_260px]">
        <div class="min-w-0">
            <?php if (count($indice) >= 3): ?>
            <nav class="mb-8 rounded-2xl bg-elevado p-5 lg:hidden" aria-labelledby="t-indice-m">
                <p id="t-indice-m" class="mb-2 text-sm font-bold">Neste artigo</p>
                <ol class="list-decimal space-y-1 pl-5 text-sm"><?php foreach ($indice as $i): ?><li><a href="#<?= e($i['id']) ?>" class="hover:text-marca"><?= e($i['texto']) ?></a></li><?php endforeach; ?></ol>
            </nav>
            <?php endif; ?>

            <div class="artigo" itemprop="articleBody"><?= $html /* HTML limpo pelo HtmlSeguro ao guardar */ ?></div>

            <!-- Partilhar -->
            <div class="mt-10 flex flex-wrap items-center gap-2 border-t border-linha pt-6">
                <span class="text-sm font-semibold mr-1">Partilhar</span>
                <a href="https://wa.me/?text=<?= rawurlencode($post['titulo'] . ' ' . $urlArtigo) ?>" target="_blank" rel="noopener" class="btn-whatsapp h-9 px-3 text-sm"><?= icone('whatsapp', 'h-4 w-4') ?> WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($urlArtigo) ?>" target="_blank" rel="noopener" class="btn-secundario h-9 px-3 text-sm">Facebook</a>
                <button type="button" class="btn-secundario h-9 px-3 text-sm" data-copiar="<?= e($urlArtigo) ?>">Copiar ligação</button>
            </div>
        </div>

        <aside class="hidden lg:block">
            <div class="sticky top-40 space-y-6">
                <?php if (count($indice) >= 3): ?>
                <nav aria-labelledby="t-indice" data-indice>
                    <p id="t-indice" class="mb-2 text-sm font-bold">Neste artigo</p>
                    <ol class="space-y-1 border-l border-linha text-sm">
                        <?php foreach ($indice as $i): ?>
                        <li><a href="#<?= e($i['id']) ?>" class="-ml-px block border-l-2 border-transparent py-1 pl-3 text-suave hover:text-tinta aria-[current=true]:border-marca aria-[current=true]:font-semibold aria-[current=true]:text-tinta" data-indice-link="<?= e($i['id']) ?>"><?= e($i['texto']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <?php endif; ?>
                <div class="cartao p-4">
                    <p class="text-sm font-semibold">Dúvidas sobre qual escolher?</p>
                    <p class="mt-1 text-xs text-suave">Diga-nos o orçamento e para que precisa. Um técnico responde no WhatsApp.</p>
                    <a href="<?= e(linkWhatsapp('Olá! Li o artigo "' . $post['titulo'] . '" e preciso de ajuda para escolher.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp mt-3 h-9 w-full text-sm"><?= icone('whatsapp', 'h-4 w-4') ?> Pedir ajuda</a>
                </div>
            </div>
        </aside>
    </div>

    <?php if ($produtos): ?>
    <section class="mx-auto mt-14 max-w-5xl" aria-labelledby="t-prod">
        <h2 id="t-prod" class="titulo-seccao mb-4">Produtos neste artigo</h2>
        <div class="faixa sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:gap-4">
            <?php foreach ($produtos as $p): ?><div class="w-[46%] sm:w-auto"><?php require RAIZ . '/app/Views/partials/produto-card.php'; ?></div><?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($relacionados): ?>
    <section class="mx-auto mt-14 max-w-5xl" aria-labelledby="t-rel">
        <h2 id="t-rel" class="titulo-seccao mb-4">Continue a ler</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($relacionados as $post) { require __DIR__ . '/_cartao.php'; } ?>
        </div>
    </section>
    <?php endif; ?>
</article>
