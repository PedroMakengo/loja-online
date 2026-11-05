<?php
/** @var ?array $post @var array $categorias @var array $produtos */
$a = \Core\Flash::antigo();
$v = fn ($k, $pad = '') => e((string) ($a[$k] ?? $post[$k] ?? $pad));
$escolhidos = array_map('intval', (array) ($a['produtos_ids'] ?? explode(',', (string) ($post['produtos_ids'] ?? ''))));
$dataPub = $a['publicado_em'] ?? (!empty($post['publicado_em']) ? date('Y-m-d\TH:i', strtotime($post['publicado_em'])) : '');
?>
<a href="<?= url('/admin/blog') ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Blog</a>
<?php
$tituloAdmin = $post ? 'Editar artigo' : 'Novo artigo';
$accoesAdmin = $post ? '<a href="' . url('/blog/' . $post['slug'] . '?pre=1') . '" target="_blank" class="btn-secundario">Pré-visualizar</a>' : '';
require dirname(__DIR__) . '/_titulo.php';
?>
<form action="<?= url('/admin/blog/guardar') ?>" method="post" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_340px]" novalidate>
    <?= \Core\Csrf::campo() ?>
    <?php if ($post): ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><?php endif; ?>
    <div class="space-y-6 min-w-0">
        <section class="cartao p-5 space-y-4">
            <div>
                <label for="titulo" class="rotulo">Título</label>
                <input id="titulo" name="titulo" required maxlength="200" value="<?= $v('titulo') ?>" class="campo text-lg font-semibold" placeholder="Ex.: Como escolher um telemóvel até 300 000 Kz" <?= ariaErro('titulo') ?>>
                <?= erroCampo('titulo') ?>
            </div>
            <div>
                <label for="resumo" class="rotulo">Resumo <span class="font-normal text-suave">(aparece nos cartões e por baixo do título)</span></label>
                <textarea id="resumo" name="resumo" rows="2" maxlength="300" class="campo" data-contador><?= $v('resumo') ?></textarea>
            </div>
            <div>
                <label for="texto-artigo" class="rotulo">Texto</label>
                <div class="flex flex-wrap gap-1 rounded-t-xl border border-b-0 border-linha bg-elevado p-1.5" role="toolbar" aria-label="Formatação" data-barra-editor>
                    <?php foreach ([['h2', 'Título de secção'], ['h3', 'Subtítulo'], ['strong', 'Negrito'], ['ul', 'Lista'], ['ol', 'Lista numerada'], ['a', 'Ligação'], ['blockquote', 'Destaque']] as [$tag, $rot]): ?>
                    <button type="button" class="h-8 rounded-lg px-2.5 text-xs font-semibold hover:bg-superficie" data-formatar="<?= $tag ?>"><?= $rot ?></button>
                    <?php endforeach; ?>
                    <span class="mx-1 w-px bg-linha" aria-hidden="true"></span>
                    <label for="inserir-produto" class="sr-only">Inserir produto</label>
                    <select id="inserir-produto" class="h-8 rounded-lg border border-linha bg-superficie px-2 text-xs font-semibold" data-inserir-produto>
                        <option value="">+ Inserir cartão de produto…</option>
                        <?php foreach ($produtos as $p): ?><option value="<?= e($p['slug']) ?>"><?= e($p['nome']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <textarea id="texto-artigo" name="conteudo" rows="22" class="campo rounded-t-none font-mono text-[13px] leading-6" <?= ariaErro('conteudo') ?> data-editor><?= $v('conteudo') ?></textarea>
                <?= erroCampo('conteudo') ?>
                <p class="mt-1.5 text-xs text-suave">Seleccione texto e use os botões. Os títulos de secção formam o índice do artigo. Um cartão de produto mostra sempre o preço e o stock actuais.</p>
            </div>
        </section>

        <section class="cartao p-5 space-y-4">
            <h2 class="font-display text-lg font-bold">Google</h2>
            <div>
                <label for="slug" class="rotulo">Endereço</label>
                <div class="flex"><span class="inline-flex items-center rounded-l-xl border border-r-0 border-linha bg-elevado px-2 text-xs text-suave">/blog/</span>
                <input id="slug" name="slug" maxlength="220" value="<?= $v('slug') ?>" placeholder="gerado a partir do título" class="campo rounded-l-none" <?= ariaErro('slug') ?>></div>
                <?= erroCampo('slug') ?>
            </div>
            <div><label for="mt" class="rotulo">Título para o Google <span class="font-normal text-suave">(opcional)</span></label><input id="mt" name="meta_titulo" maxlength="160" value="<?= $v('meta_titulo') ?>" class="campo" data-contador data-ideal="60"></div>
            <div><label for="md" class="rotulo">Descrição para o Google <span class="font-normal text-suave">(opcional; sem ela usa o resumo)</span></label><textarea id="md" name="meta_descricao" rows="2" maxlength="255" class="campo" data-contador data-ideal="155"><?= $v('meta_descricao') ?></textarea></div>
        </section>
    </div>

    <div class="space-y-6 xl:sticky xl:top-24 xl:self-start">
        <section class="cartao p-5 space-y-4">
            <div>
                <label for="estado" class="rotulo">Estado</label>
                <select id="estado" name="estado" class="campo">
                    <option value="rascunho" <?= ($a['estado'] ?? $post['estado'] ?? 'rascunho') === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="publicado" <?= ($a['estado'] ?? $post['estado'] ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                </select>
            </div>
            <div>
                <label for="pub" class="rotulo">Data de publicação</label>
                <input id="pub" name="publicado_em" type="datetime-local" value="<?= e($dataPub) ?>" class="campo">
                <p class="mt-1.5 text-xs text-suave">Vazio = agora. Uma data futura agenda o artigo.</p>
            </div>
            <div>
                <label for="cat" class="rotulo">Categoria</label>
                <select id="cat" name="categoria_id" class="campo" <?= ariaErro('categoria_id') ?>>
                    <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= (string) ($a['categoria_id'] ?? $post['categoria_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="destaque" value="1" <?= ($a['destaque'] ?? $post['destaque'] ?? 0) ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]"> Destaque no topo do blog</label>
            <button class="btn-primario h-11 w-full">Guardar</button>
        </section>

        <section class="cartao p-5 space-y-3">
            <h2 class="font-display text-lg font-bold">Capa</h2>
            <?php if (!empty($post['imagem'])): ?>
            <img src="<?= asset('img/' . $post['imagem']) ?>" alt="" class="aspect-[16/9] w-full rounded-xl object-cover">
            <label class="flex items-center gap-2 text-xs text-suave"><input type="checkbox" name="remover_imagem" value="1" class="h-3.5 w-3.5"> Remover capa</label>
            <?php else: ?><p class="text-xs text-suave">Sem fotografia, o artigo usa uma capa com a cor da categoria.</p><?php endif; ?>
            <label for="imagem" class="sr-only">Imagem de capa</label>
            <input id="imagem" type="file" name="imagem" accept="image/jpeg,image/png,image/webp" class="campo h-auto py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-tinta file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-superficie">
            <p class="text-xs text-suave">Horizontal, idealmente 1600 × 700 píxeis, até 4 MB.</p>
        </section>

        <section class="cartao p-5">
            <label for="pids" class="font-display text-lg font-bold">Produtos neste artigo</label>
            <p class="mb-2 text-xs text-suave">Aparecem no fim do artigo. Vazio = os produtos citados no texto. Ctrl/Cmd para escolher vários.</p>
            <select id="pids" name="produtos_ids[]" multiple size="8" class="campo h-auto py-2 text-sm">
                <?php foreach ($produtos as $p): ?><option value="<?= $p['id'] ?>" <?= in_array((int) $p['id'], $escolhidos, true) ? 'selected' : '' ?>><?= e($p['nome']) ?></option><?php endforeach; ?>
            </select>
        </section>

    </div>
</form>
<?php if ($post): ?>
<form action="<?= url('/admin/blog/' . $post['id'] . '/apagar') ?>" method="post" class="mt-6 xl:ml-auto xl:w-[340px]" data-confirmar="Apagar este artigo definitivamente?">
    <?= \Core\Csrf::campo() ?><button class="btn-fantasma w-full text-suave hover:text-coral">Apagar artigo</button>
</form>
<?php endif; ?>
