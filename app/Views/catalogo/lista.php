<?php
/**
 * Listagem do catálogo.
 * @var string $titulo @var ?string $descricao @var array $breadcrumb
 * @var array $resultado (itens,total,pagina,paginas) @var array $filtros
 * @var array $facetaMarcas @var array $facetaPreco @var bool $mostrarMarcas
 */
use App\Models\Produto;

$filtrosActivos = [];
foreach ($filtros['marcas'] ?? [] as $slugMarca) {
    $nomeMarca = $slugMarca;
    foreach ($facetaMarcas as $fm) { if ($fm['slug'] === $slugMarca) { $nomeMarca = $fm['nome']; } }
    $filtrosActivos[] = [$nomeMarca, urlActual(['marca' => array_values(array_diff($filtros['marcas'], [$slugMarca])), 'pagina' => null])];
}
if (!empty($filtros['min']) || !empty($filtros['max'])) {
    $filtrosActivos[] = [
        trim((!empty($filtros['min']) ? 'Desde ' . kz($filtros['min']) : '') . ' ' . (!empty($filtros['max']) ? 'até ' . kz($filtros['max']) : '')),
        urlActual(['min' => null, 'max' => null, 'pagina' => null]),
    ];
}
if (!empty($filtros['promo']) && empty($filtros['_fixo_promo']))       { $filtrosActivos[] = ['Em promoção', urlActual(['promo' => null, 'pagina' => null])]; }
if (!empty($filtros['novidade']) && empty($filtros['_fixo_novidade'])) { $filtrosActivos[] = ['Novidades', urlActual(['novidade' => null, 'pagina' => null])]; }
if (!empty($filtros['stock']))    { $filtrosActivos[] = ['Com stock', urlActual(['stock' => null, 'pagina' => null])]; }
?>
<div class="container pt-4 pb-14">
    <!-- Breadcrumb -->
    <nav aria-label="Caminho" class="text-sm text-suave">
        <ol class="flex flex-wrap items-center gap-1">
            <?php foreach ($breadcrumb as $i => [$nome, $href]): ?>
            <li class="flex items-center gap-1">
                <?php if ($i < count($breadcrumb) - 1): ?>
                    <a href="<?= url($href) ?>" class="hover:text-tinta"><?= e($nome) ?></a><?= icone('seta-dir', 'h-3.5 w-3.5') ?>
                <?php else: ?>
                    <span class="text-tinta font-medium" aria-current="page"><?= e($nome) ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <header class="mt-3 mb-6">
        <h1 class="font-display text-3xl sm:text-4xl font-extrabold"><?= e($titulo) ?></h1>
        <?php if (!empty($descricao)): ?><p class="mt-2 text-suave max-w-2xl"><?= e($descricao) ?></p><?php endif; ?>
    </header>

    <form method="get" action="<?= e(strtok($_SERVER['REQUEST_URI'], '?')) ?>" class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8" data-filtros>
        <?php if (!empty($filtros['q'])): ?><input type="hidden" name="q" value="<?= e($filtros['q']) ?>"><?php endif; ?>

        <!-- ===== Painel de filtros (gaveta no mobile, coluna no desktop) ===== -->
        <div id="painel-filtros" class="fixed inset-0 z-[60] hidden lg:static lg:z-auto lg:block" data-gaveta>
            <div class="absolute inset-0 bg-grafite/50 lg:hidden" data-fechar></div>
            <aside class="absolute inset-y-0 left-0 w-[86%] max-w-sm overflow-y-auto bg-superficie lg:bg-transparent lg:static lg:w-auto lg:max-w-none lg:overflow-visible flex flex-col" aria-label="Filtros">
                <div class="flex h-16 items-center justify-between px-4 border-b border-linha lg:hidden">
                    <p class="font-display text-lg font-bold">Filtros</p>
                    <button type="button" class="btn-icone" data-fechar aria-label="Fechar filtros"><?= icone('fechar') ?></button>
                </div>

                <div class="flex-1 p-4 lg:p-0 space-y-6 lg:sticky lg:top-40">
                    <fieldset>
                        <legend class="mb-3 text-sm font-bold">Disponibilidade</legend>
                        <div class="space-y-2.5 text-sm">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="stock" value="1" <?= !empty($filtros['stock']) ? 'checked' : '' ?> class="h-4 w-4 rounded accent-[rgb(var(--marca))]"> Só com stock
                            </label>
                            <?php if (empty($filtros['_fixo_promo'])): ?>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="promo" value="1" <?= !empty($filtros['promo']) ? 'checked' : '' ?> class="h-4 w-4 rounded accent-[rgb(var(--marca))]"> Em promoção
                            </label>
                            <?php endif; ?>
                            <?php if (empty($filtros['_fixo_novidade'])): ?>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="novidade" value="1" <?= !empty($filtros['novidade']) ? 'checked' : '' ?> class="h-4 w-4 rounded accent-[rgb(var(--marca))]"> Novidades
                            </label>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <?php if ($mostrarMarcas && $facetaMarcas): ?>
                    <fieldset id="faceta-marcas" data-actualizar>
                        <legend class="mb-3 text-sm font-bold">Marca</legend>
                        <div class="space-y-2.5 text-sm">
                            <?php foreach ($facetaMarcas as $fm): ?>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="marca[]" value="<?= e($fm['slug']) ?>" <?= in_array($fm['slug'], $filtros['marcas'] ?? [], true) ? 'checked' : '' ?> class="h-4 w-4 rounded accent-[rgb(var(--marca))]">
                                <span class="flex-1"><?= e($fm['nome']) ?></span>
                                <span class="text-xs text-suave num"><?= (int) $fm['total'] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <?php endif; ?>

                    <fieldset>
                        <legend class="mb-3 text-sm font-bold">Preço (Kz)</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="f-min" class="text-xs text-suave">Mínimo</label>
                                <input id="f-min" type="number" inputmode="numeric" name="min" min="0" step="1000" value="<?= e((string) ($filtros['min'] ?? '')) ?>"
                                       placeholder="<?= number_format($facetaPreco['min'], 0, '', ' ') ?>" class="campo h-10 num" data-sem-auto>
                            </div>
                            <div>
                                <label for="f-max" class="text-xs text-suave">Máximo</label>
                                <input id="f-max" type="number" inputmode="numeric" name="max" min="0" step="1000" value="<?= e((string) ($filtros['max'] ?? '')) ?>"
                                       placeholder="<?= number_format($facetaPreco['max'], 0, '', ' ') ?>" class="campo h-10 num" data-sem-auto>
                            </div>
                        </div>
                        <button type="submit" class="btn-secundario mt-2 h-10 w-full">Aplicar preço</button>
                    </fieldset>
                </div>

                <div class="sticky bottom-0 border-t border-linha bg-superficie p-4 pb-seguro lg:hidden">
                    <button type="button" class="btn-primario w-full" data-fechar>Ver <span data-total-botao class="num"><?= (int) $resultado['total'] ?></span> produtos</button>
                </div>
            </aside>
        </div>

        <!-- ===== Resultados ===== -->
        <div class="min-w-0" id="resultados" data-actualizar>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-suave"><strong class="text-tinta num"><?= (int) $resultado['total'] ?></strong> <?= $resultado['total'] == 1 ? 'produto' : 'produtos' ?></p>
                <div class="flex items-center gap-2">
                    <button type="button" class="chip lg:hidden" data-abrir="painel-filtros">
                        <?= icone('filtro', 'h-4 w-4') ?> Filtros<?= $filtrosActivos ? ' (' . count($filtrosActivos) . ')' : '' ?>
                    </button>
                    <label for="f-ordem" class="sr-only">Ordenar por</label>
                    <select id="f-ordem" name="ordem" class="campo h-9 w-auto pr-8 text-sm">
                        <?php foreach (Produto::ORDENS as $valor => $rotulo): ?>
                        <option value="<?= $valor ?>" <?= ($filtros['ordem'] ?? 'relevancia') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($filtrosActivos): ?>
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <?php foreach ($filtrosActivos as [$rotulo, $href]): ?>
                <a href="<?= e($href) ?>" class="chip h-8 bg-elevado border-transparent" data-ligacao-filtro>
                    <?= e($rotulo) ?> <?= icone('fechar', 'h-3.5 w-3.5') ?>
                </a>
                <?php endforeach; ?>
                <a href="<?= e(strtok($_SERVER['REQUEST_URI'], '?') . (!empty($filtros['q']) ? '?q=' . urlencode($filtros['q']) : '')) ?>" class="text-sm font-semibold text-marca hover:underline" data-ligacao-filtro>Limpar filtros</a>
            </div>
            <?php endif; ?>

            <?php if ($resultado['itens']): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-4">
                <?php foreach ($resultado['itens'] as $p): ?>
                    <?php require RAIZ . '/app/Views/partials/produto-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($resultado['paginas'] > 1): ?>
            <nav class="mt-10 flex items-center justify-center gap-1" aria-label="Paginação">
                <?php $pg = $resultado['pagina']; $tot = $resultado['paginas']; ?>
                <?php if ($pg > 1): ?><a href="<?= e(urlActual(['pagina' => $pg - 1])) ?>" class="btn-icone" data-ligacao-filtro aria-label="Página anterior"><?= icone('seta-esq') ?></a><?php endif; ?>
                <?php for ($n = max(1, $pg - 2); $n <= min($tot, $pg + 2); $n++): ?>
                    <a href="<?= e(urlActual(['pagina' => $n > 1 ? $n : null])) ?>" data-ligacao-filtro
                       class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold num <?= $n === $pg ? 'bg-tinta text-superficie' : 'hover:bg-elevado' ?>"
                       <?= $n === $pg ? 'aria-current="page"' : '' ?>><?= $n ?></a>
                <?php endfor; ?>
                <?php if ($pg < $tot): ?><a href="<?= e(urlActual(['pagina' => $pg + 1])) ?>" class="btn-icone" data-ligacao-filtro aria-label="Página seguinte"><?= icone('seta-dir') ?></a><?php endif; ?>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <div class="cartao p-10 text-center">
                <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('pesquisa', 'h-7 w-7') ?></span>
                <p class="font-display text-lg font-bold">Nenhum produto com estes filtros</p>
                <p class="mt-1 text-sm text-suave">Retire um filtro ou pergunte-nos no WhatsApp — muitos produtos chegam por encomenda.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <?php if ($filtrosActivos): ?><a href="<?= e(strtok($_SERVER['REQUEST_URI'], '?')) ?>" class="btn-secundario" data-ligacao-filtro>Limpar filtros</a><?php endif; ?>
                    <a href="<?= e(linkWhatsapp('Olá! Procuro: ' . ($filtros['q'] ?? $titulo))) ?>" target="_blank" rel="noopener" class="btn-whatsapp"><?= icone('whatsapp') ?> Perguntar no WhatsApp</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>
