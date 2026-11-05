<?php
/** @var ?array $produto @var array $specs @var array $imagens @var array $categorias @var array $marcas */
$p = $produto ?? [];
$a = \Core\Flash::antigo();
$v = fn (string $c, $padrao = '') => e((string) ($a[$c] ?? $p[$c] ?? $padrao));
$num = fn (string $c) => isset($a[$c]) ? e($a[$c]) : (isset($p[$c]) && $p[$c] !== null ? (string) (0 + $p[$c]) : '');
if (isset($a['spec_nome'])) {
    $specs = array_map(fn ($i) => ['grupo' => $a['spec_grupo'][$i] ?? '', 'nome' => $a['spec_nome'][$i], 'valor' => $a['spec_valor'][$i] ?? ''], array_keys($a['spec_nome']));
}
$tituloAdmin = $produto ? $produto['nome'] : 'Novo produto';
$accoesAdmin = $produto && $produto['estado'] === 'activo' ? '<a href="' . url('/produto/' . $produto['slug']) . '" target="_blank" class="btn-secundario">Ver na loja</a>' : '';
?>
<a href="<?= url('/admin/produtos') ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Produtos</a>
<?php require dirname(__DIR__) . '/_titulo.php'; ?>

<form action="<?= url('/admin/produtos/guardar') ?>" method="post" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_340px]" novalidate>
    <?= \Core\Csrf::campo() ?>
    <?php if ($produto): ?><input type="hidden" name="id" value="<?= (int) $produto['id'] ?>"><?php endif; ?>

    <div class="space-y-6 min-w-0">
        <section class="cartao p-5 space-y-4">
            <h2 class="font-display text-lg font-bold">Informação</h2>
            <div>
                <label for="nome" class="rotulo">Nome</label>
                <input id="nome" name="nome" required maxlength="200" value="<?= $v('nome') ?>" class="campo" placeholder="Ex.: Samsung Galaxy A56 5G 128GB" <?= ariaErro('nome') ?>>
                <?= erroCampo('nome') ?>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="sku" class="rotulo">SKU (referência interna)</label>
                    <input id="sku" name="sku" required maxlength="60" value="<?= $v('sku') ?>" class="campo uppercase num" <?= ariaErro('sku') ?>>
                    <?= erroCampo('sku') ?>
                </div>
                <div>
                    <label for="slug" class="rotulo">Endereço na loja</label>
                    <div class="flex"><span class="inline-flex items-center rounded-l-xl border border-r-0 border-linha bg-elevado px-2 text-xs text-suave">/produto/</span>
                    <input id="slug" name="slug" maxlength="220" value="<?= $v('slug') ?>" class="campo rounded-l-none" placeholder="gerado a partir do nome" <?= ariaErro('slug') ?>></div>
                    <?= erroCampo('slug') ?>
                </div>
            </div>
            <div>
                <label for="resumo" class="rotulo">Resumo <span class="font-normal text-suave">(aparece junto ao preço)</span></label>
                <textarea id="resumo" name="resumo" rows="2" maxlength="300" class="campo" data-contador><?= $v('resumo') ?></textarea>
            </div>
            <div>
                <label for="descricao" class="rotulo">Descrição</label>
                <textarea id="descricao" name="descricao" rows="8" class="campo font-mono text-[13px]"><?= $v('descricao') ?></textarea>
                <p class="mt-1.5 text-xs text-suave">Texto simples (linha em branco separa parágrafos) ou HTML básico: &lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;&lt;li&gt;, &lt;h2&gt;, ligações. Outras tags são removidas por segurança.</p>
            </div>
        </section>

        <section class="cartao p-5">
            <h2 class="font-display text-lg font-bold">Imagens</h2>
            <p class="mt-1 text-sm text-suave">A primeira imagem é a principal. JPG, PNG ou WEBP até 4 MB; fundo branco ou transparente, idealmente 1000 × 1000 píxeis.</p>
            <?php if ($imagens): ?>
            <ul class="mt-4 grid grid-cols-3 sm:grid-cols-5 gap-3">
                <?php foreach ($imagens as $i => $img): ?>
                <li class="relative">
                    <img src="<?= asset('img/produtos/' . $img['ficheiro']) ?>" alt="" class="aspect-square w-full rounded-xl bg-white object-contain p-1.5 <?= $i === 0 ? 'ring-2 ring-marca' : '' ?>">
                    <label class="mt-1 flex items-center gap-1.5 text-xs text-suave cursor-pointer"><input type="checkbox" name="remover_imagem[]" value="<?= (int) $img['id'] ?>" class="h-3.5 w-3.5"> Remover</label>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <label class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-linha p-6 text-center hover:border-marca" data-zona-imagens>
                <?= icone('mais', 'h-6 w-6 text-suave') ?>
                <span class="text-sm font-semibold">Adicionar imagens</span>
                <span class="text-xs text-suave" data-nomes-imagens>Pode escolher várias de uma vez</span>
                <input type="file" name="imagens[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            <div class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-3" data-pre-visualizacao></div>
        </section>

        <section class="cartao p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-bold">Especificações</h2>
                <button type="button" class="btn-secundario h-9 px-3 text-xs" data-adicionar-spec><?= icone('mais', 'h-4 w-4') ?> Linha</button>
            </div>
            <p class="mt-1 text-sm text-suave">Agrupadas por secção na página do produto (ex.: Ecrã, Desempenho, Bateria).</p>
            <div class="mt-4 space-y-2" data-specs>
                <?php foreach (($specs ?: [['grupo' => '', 'nome' => '', 'valor' => '']]) as $s): ?>
                <div class="grid grid-cols-[1fr_1fr_1.4fr_auto] gap-2" data-spec>
                    <input name="spec_grupo[]" value="<?= e($s['grupo']) ?>" placeholder="Secção" aria-label="Secção" class="campo h-10" list="grupos-spec">
                    <input name="spec_nome[]" value="<?= e($s['nome']) ?>" placeholder="Característica" aria-label="Característica" class="campo h-10">
                    <input name="spec_valor[]" value="<?= e($s['valor']) ?>" placeholder="Valor" aria-label="Valor" class="campo h-10">
                    <button type="button" class="btn-icone h-10 w-10 text-suave hover:text-coral" data-remover-spec aria-label="Remover linha"><?= icone('lixo', 'h-4 w-4') ?></button>
                </div>
                <?php endforeach; ?>
            </div>
            <datalist id="grupos-spec"><?php foreach (['Geral', 'Ecrã', 'Desempenho', 'Câmara', 'Bateria', 'Ligações', 'Áudio', 'Dimensões', 'Energia', 'Smart', 'Software'] as $g): ?><option value="<?= $g ?>"><?php endforeach; ?></datalist>
        </section>

        <section class="cartao p-5 space-y-4">
            <h2 class="font-display text-lg font-bold">Google</h2>
            <div><label for="mt" class="rotulo">Título <span class="font-normal text-suave">(opcional)</span></label><input id="mt" name="meta_titulo" maxlength="160" value="<?= $v('meta_titulo') ?>" class="campo" data-contador data-ideal="60"></div>
            <div><label for="md" class="rotulo">Descrição <span class="font-normal text-suave">(opcional)</span></label><textarea id="md" name="meta_descricao" rows="2" maxlength="255" class="campo" data-contador data-ideal="155"><?= $v('meta_descricao') ?></textarea></div>
        </section>
    </div>

    <!-- Coluna lateral -->
    <div class="space-y-6 xl:sticky xl:top-24 xl:self-start">
        <section class="cartao p-5 space-y-4">
            <div>
                <label for="estado" class="rotulo">Estado</label>
                <select id="estado" name="estado" class="campo">
                    <?php foreach (['activo' => 'À venda', 'rascunho' => 'Rascunho (não aparece)', 'inactivo' => 'Arquivado'] as $k => $t): ?>
                    <option value="<?= $k ?>" <?= ($a['estado'] ?? $p['estado'] ?? 'rascunho') === $k ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="cat" class="rotulo">Categoria</label>
                <select id="cat" name="categoria_id" class="campo" <?= ariaErro('categoria_id') ?>>
                    <option value="">Escolher…</option>
                    <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= (string) ($a['categoria_id'] ?? $p['categoria_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
                </select>
                <?= erroCampo('categoria_id') ?>
            </div>
            <div>
                <label for="marca" class="rotulo">Marca</label>
                <select id="marca" name="marca_id" class="campo">
                    <option value="">Sem marca</option>
                    <?php foreach ($marcas as $m): ?><option value="<?= $m['id'] ?>" <?= (string) ($a['marca_id'] ?? $p['marca_id'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['nome']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-2 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="destaque" value="1" <?= ($a['destaque'] ?? $p['destaque'] ?? 0) ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]"> Em destaque</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="novidade" value="1" <?= ($a['novidade'] ?? $p['novidade'] ?? 0) ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]"> Novidade</label>
            </div>
        </section>

        <section class="cartao p-5 space-y-4">
            <h2 class="font-display text-lg font-bold">Preço</h2>
            <div>
                <label for="preco" class="rotulo">Preço de venda (Kz, com IVA)</label>
                <input id="preco" name="preco" inputmode="decimal" required value="<?= $num('preco') ?>" class="campo num text-lg font-semibold" <?= ariaErro('preco') ?> data-preco>
                <?= erroCampo('preco') ?>
            </div>
            <div>
                <label for="pa" class="rotulo">Preço anterior <span class="font-normal text-suave">(riscado)</span></label>
                <input id="pa" name="preco_anterior" inputmode="decimal" value="<?= $num('preco_anterior') ?>" class="campo num" <?= ariaErro('preco_anterior') ?> data-preco-anterior>
                <?= erroCampo('preco_anterior') ?>
                <p class="mt-1 text-xs font-semibold text-coral" data-desconto-info></p>
            </div>
            <div>
                <label for="oferta" class="rotulo">Promoção do dia até <span class="font-normal text-suave">(opcional)</span></label>
                <input id="oferta" name="oferta_ate" type="datetime-local" value="<?= isset($a['oferta_ate']) ? e($a['oferta_ate']) : (!empty($p['oferta_ate']) ? date('Y-m-d\TH:i', strtotime($p['oferta_ate'])) : '') ?>" class="campo" <?= ariaErro('oferta_ate') ?>>
                <p class="mt-1 text-xs text-suave">Aparece em "Promoções do dia" com contagem decrescente.</p>
            </div>
            <div>
                <label for="custo" class="rotulo">Custo <span class="font-normal text-suave">(interno, para margens)</span></label>
                <input id="custo" name="custo" inputmode="decimal" value="<?= $num('custo') ?>" class="campo num" data-custo>
                <p class="mt-1 text-xs text-suave" data-margem-info></p>
            </div>
        </section>

        <section class="cartao p-5 grid grid-cols-3 gap-3">
            <?php foreach ([['stock', 'Stock', 0], ['stock_minimo', 'Alerta', 2], ['garantia_meses', 'Garantia (meses)', 12]] as [$c, $r, $pad]): ?>
            <div>
                <label for="<?= $c ?>" class="rotulo text-xs"><?= $r ?></label>
                <input id="<?= $c ?>" name="<?= $c ?>" type="number" min="0" inputmode="numeric" value="<?= $v($c, (string) $pad) ?>" class="campo num" <?= ariaErro($c) ?>>
                <?= erroCampo($c) ?>
            </div>
            <?php endforeach; ?>
        </section>

        <button class="btn-primario h-12 w-full text-base">Guardar produto</button>
    </div>
</form>
