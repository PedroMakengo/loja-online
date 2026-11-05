<?php /** @var ?array $categoria @var array $categorias @var ?array $destaque @var array $lista @var array $breadcrumb */ ?>
<div class="container pt-6 pb-14">
    <header class="mb-6">
        <h1 class="font-display text-3xl sm:text-4xl font-extrabold"><?= $categoria ? e($categoria['nome']) : 'Blog' ?></h1>
        <p class="mt-2 max-w-2xl text-suave"><?= $categoria ? e($categoria['descricao'] ?? '') : 'Guias para escolher bem, com preços em Kwanzas e a realidade de Angola em mente: cortes de energia, Wi-Fi, garantia e assistência.' ?></p>
    </header>

    <nav class="faixa mb-8" aria-label="Categorias do blog">
        <a href="<?= url('/blog') ?>" class="chip <?= !$categoria ? 'chip-activo' : '' ?>">Todos</a>
        <?php foreach ($categorias as $c): if (!$c['total'] && ($categoria['id'] ?? 0) != $c['id']) continue; ?>
        <a href="<?= url('/blog/categoria/' . $c['slug']) ?>" class="chip <?= ($categoria['id'] ?? 0) == $c['id'] ? 'chip-activo' : '' ?>"><?= e($c['nome']) ?> <span class="text-xs opacity-70 num"><?= (int) $c['total'] ?></span></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($destaque): $post = $destaque; ?>
    <article class="group relative mb-8 grid overflow-hidden cartao md:grid-cols-2 hover:border-tinta/25 transition-colors">
        <div class="aspect-[16/9] md:aspect-auto md:min-h-[320px]"><?php $grande = true; require __DIR__ . '/_capa.php'; ?></div>
        <div class="flex flex-col justify-center p-6 sm:p-8">
            <p class="text-sm font-semibold text-marca"><?= e($post['categoria_nome']) ?></p>
            <h2 class="mt-2 font-display text-2xl sm:text-3xl font-extrabold leading-tight group-hover:text-marca">
                <a href="<?= url('/blog/' . $post['slug']) ?>" class="after:absolute after:inset-0"><?= e($post['titulo']) ?></a>
            </h2>
            <?php if ($post['resumo']): ?><p class="mt-3 text-suave"><?= e($post['resumo']) ?></p><?php endif; ?>
            <p class="mt-5 text-sm text-suave num"><?= e(strftime_pt($post['publicado_em'], true)) ?>, <?= \App\Models\Blog::minutosLeitura((string) $post['conteudo']) ?> min de leitura</p>
        </div>
    </article>
    <?php endif; ?>

    <?php if ($lista['itens']): ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($lista['itens'] as $post) { require __DIR__ . '/_cartao.php'; } ?>
    </div>
    <?php if ($lista['paginas'] > 1): ?>
    <nav class="mt-10 flex justify-center gap-2" aria-label="Paginação">
        <?php if ($lista['pagina'] > 1): ?><a href="<?= e(urlActual(['pagina' => $lista['pagina'] - 1 > 1 ? $lista['pagina'] - 1 : null])) ?>" class="btn-secundario">Artigos mais recentes</a><?php endif; ?>
        <?php if ($lista['pagina'] < $lista['paginas']): ?><a href="<?= e(urlActual(['pagina' => $lista['pagina'] + 1])) ?>" class="btn-secundario">Artigos anteriores</a><?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php elseif (!$destaque): ?>
    <div class="cartao p-10 text-center text-suave">Ainda não há artigos publicados<?= $categoria ? ' nesta categoria' : '' ?>.</div>
    <?php endif; ?>

    <section class="mt-12 cartao flex flex-col sm:flex-row sm:items-center gap-4 justify-between p-6">
        <div>
            <h2 class="font-display text-xl font-bold">Receba os próximos guias</h2>
            <p class="text-sm text-suave">Uma mensagem por semana, com guias novos e as melhores promoções.</p>
        </div>
        <form action="<?= url('/newsletter') ?>" method="post" class="flex w-full sm:w-auto gap-2" data-ajax-form>
            <?= \Core\Csrf::campo() ?><input type="hidden" name="origem" value="blog">
            <label for="news-blog" class="sr-only">Email</label>
            <input id="news-blog" type="email" name="email" required placeholder="O seu email" class="campo h-11 sm:w-64">
            <button class="btn-primario shrink-0">Subscrever</button>
        </form>
    </section>
</div>
