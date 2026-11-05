<?php
/** @var array $produtos @var array $pg @var array $categorias @var array $marcas @var string $estado */
$tituloAdmin = 'Produtos';
$accoesAdmin = '<a href="' . url('/admin/produtos/novo') . '" class="btn-primario">' . icone('mais', 'h-4 w-4') . ' Novo produto</a>';
require dirname(__DIR__) . '/_titulo.php';
?>
<form method="get" class="mb-4 flex flex-wrap gap-2" data-filtros-admin>
    <label for="q" class="sr-only">Procurar</label>
    <input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Nome ou SKU" class="campo h-10 max-w-xs">
    <?php foreach ([
        ['categoria', 'Categoria', array_column($categorias, 'nome', 'id')],
        ['marca', 'Marca', array_column($marcas, 'nome', 'id')],
        ['estado', 'Estado', ['activo' => 'À venda', 'rascunho' => 'Rascunho', 'inactivo' => 'Arquivado', 'todos' => 'Todos']],
        ['stock', 'Stock', ['baixo' => 'Stock baixo', 'esgotado' => 'Esgotado']],
        ['ordem', 'Ordenar', ['' => 'Últimos alterados', 'stock' => 'Menos stock', 'vendas' => 'Mais vendidos', 'preco' => 'Preço', 'nome' => 'Nome']],
    ] as [$nome, $rotulo, $opcoes]): ?>
    <label for="f-<?= $nome ?>" class="sr-only"><?= $rotulo ?></label>
    <select id="f-<?= $nome ?>" name="<?= $nome ?>" class="campo h-10 w-auto" onchange="this.form.submit()">
        <?php if (!in_array($nome, ['estado', 'ordem'], true)): ?><option value=""><?= $rotulo ?>: todas</option><?php endif; ?>
        <?php foreach ($opcoes as $v => $t): $sel = ($nome === 'estado' ? $estado : ($_GET[$nome] ?? '')) == $v; ?>
        <option value="<?= e((string) $v) ?>" <?= $sel ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endforeach; ?>
    <button class="btn-secundario h-10">Filtrar</button>
</form>

<div class="cartao overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-elevado text-left text-xs text-suave">
                <tr>
                    <th class="px-4 py-3 font-semibold">Produto</th>
                    <th class="px-4 py-3 font-semibold hidden md:table-cell">Categoria</th>
                    <th class="px-4 py-3 font-semibold text-right">Preço</th>
                    <th class="px-4 py-3 font-semibold w-32">Stock</th>
                    <th class="px-4 py-3 font-semibold text-right hidden sm:table-cell">Vendas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-linha">
                <?php foreach ($produtos as $p): $baixo = (int) $p['stock'] <= (int) $p['stock_minimo']; ?>
                <tr class="hover:bg-elevado/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="<?= imagemProduto($p) ?>" alt="" class="h-11 w-11 shrink-0 rounded-lg bg-white object-contain p-1" width="44" height="44" loading="lazy">
                            <div class="min-w-0">
                                <a href="<?= url('/admin/produtos/' . $p['id']) ?>" class="font-semibold hover:text-marca line-clamp-1"><?= e($p['nome']) ?></a>
                                <p class="text-xs text-suave num"><?= e($p['sku']) ?><?= $p['marca'] ? ', ' . e($p['marca']) : '' ?>
                                    <?php if ($p['estado'] !== 'activo'): ?><span class="ml-1 rounded bg-elevado px-1 font-semibold"><?= $p['estado'] === 'rascunho' ? 'Rascunho' : 'Arquivado' ?></span><?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-suave hidden md:table-cell"><?= e($p['categoria']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <p class="font-semibold num <?= $p['preco_anterior'] > $p['preco'] ? 'text-coral' : '' ?>"><?= kz($p['preco']) ?></p>
                        <?php if ($p['preco_anterior'] > $p['preco']): ?><p class="preco-anterior text-xs"><?= kz($p['preco_anterior']) ?></p><?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <form action="<?= url('/admin/produtos/' . $p['id'] . '/stock') ?>" method="post" class="flex items-center gap-1" data-stock-rapido>
                            <label for="stock-<?= $p['id'] ?>" class="sr-only">Stock de <?= e($p['nome']) ?></label>
                            <input id="stock-<?= $p['id'] ?>" name="stock" type="number" min="0" inputmode="numeric" value="<?= (int) $p['stock'] ?>"
                                   class="campo h-9 w-20 text-center num <?= $baixo ? '!border-aviso text-aviso font-semibold' : '' ?>">
                            <button class="btn-icone h-9 w-9 hidden" aria-label="Guardar stock"><?= icone('check', 'h-4 w-4') ?></button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right text-suave num hidden sm:table-cell"><?= (int) $p['vendas'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$produtos): ?><tr><td colspan="5" class="px-4 py-10 text-center text-suave">Nenhum produto com estes filtros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/_paginacao.php'; ?>
