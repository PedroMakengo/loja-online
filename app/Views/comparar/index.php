<?php
/** @var array $produtos @var array $linhas */
$n = count($produtos);
// Uma linha "difere" quando nem todos os produtos têm o mesmo valor
$difere = fn (array $valores) => count(array_unique(array_map(fn ($p) => mb_strtolower(trim($valores[$p['id']] ?? '—')), $produtos))) > 1;
?>
<div class="container pt-6 pb-14">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-3xl sm:text-4xl font-extrabold">Comparar produtos</h1>
            <p class="mt-1 text-suave"><?= $n ? plural($n, 'produto', 'produtos') . ' lado a lado.' : '' ?> Pode comparar até <?= \App\Servicos\Comparador::MAXIMO ?>.</p>
        </div>
        <?php if ($n): ?>
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer"><input type="checkbox" class="h-4 w-4 accent-[rgb(var(--marca))]" data-so-diferencas> Mostrar só as diferenças</label>
            <form action="<?= url('/comparar/limpar') ?>" method="post"><?= \Core\Csrf::campo() ?><button class="btn-fantasma h-9 px-3 text-sm">Limpar tudo</button></form>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($n < 2): ?>
    <div class="cartao p-8 text-center">
        <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('comparar', 'h-7 w-7') ?></span>
        <p class="font-display text-lg font-bold"><?= $n ? 'Escolha mais um produto para comparar' : 'Ainda não escolheu produtos' ?></p>
        <p class="mt-1 text-sm text-suave">Toque no ícone de comparar <?= icone('comparar', 'inline h-4 w-4 align-text-bottom') ?> nos produtos da mesma categoria.</p>
        <a href="<?= url($n ? '/categoria/' . $produtos[0]['categoria_slug'] : '/produtos') ?>" class="btn-primario mt-5"><?= $n ? 'Ver ' . e($produtos[0]['categoria_nome']) : 'Explorar produtos' ?></a>
    </div>
    <?php endif; ?>

    <?php if ($n): ?>
    <div class="cartao overflow-x-auto <?= $n < 2 ? 'mt-6' : '' ?>">
        <table class="w-full text-sm table-fixed" style="min-width: <?= 160 + $n * 200 ?>px" data-tabela-comparar>
            <colgroup><col class="w-40"><?php foreach ($produtos as $_): ?><col><?php endforeach; ?></colgroup>
            <thead>
                <tr class="align-top">
                    <th scope="col" class="sticky left-0 z-10 bg-superficie p-4 text-left"><span class="sr-only">Característica</span></th>
                    <?php foreach ($produtos as $p): ?>
                    <th scope="col" class="p-4 text-left font-normal">
                        <div class="relative">
                            <button type="button" class="absolute -right-1 -top-1 btn-icone h-8 w-8 text-suave" data-comparar="<?= (int) $p['id'] ?>" data-recarregar aria-pressed="true" aria-label="Retirar <?= e($p['nome']) ?>"><?= icone('fechar', 'h-4 w-4') ?></button>
                            <a href="<?= url('/produto/' . $p['slug']) ?>" class="block">
                                <img src="<?= imagemProduto($p) ?>" alt="" class="aspect-square w-full max-w-[160px] rounded-xl bg-white object-contain p-3" width="160" height="160" loading="lazy">
                                <p class="mt-3 text-xs text-suave"><?= e($p['marca_nome'] ?? '') ?></p>
                                <p class="font-semibold leading-snug hover:text-marca line-clamp-2"><?= e($p['nome']) ?></p>
                            </a>
                            <?php if ($p['preco_anterior'] > $p['preco']): ?><p class="mt-2 preco-anterior"><?= kz($p['preco_anterior']) ?></p><?php endif; ?>
                            <p class="preco text-xl <?= $p['preco_anterior'] > $p['preco'] ? 'text-coral' : '' ?>"><?= kz($p['preco']) ?></p>
                            <?php if ((int) $p['stock'] > 0): ?>
                            <button type="button" class="btn-primario mt-3 h-10 w-full" data-adicionar="<?= (int) $p['id'] ?>"><?= icone('carrinho', 'h-4 w-4') ?> Adicionar</button>
                            <?php else: ?><p class="mt-3 text-sm font-semibold text-suave">Esgotado</p><?php endif; ?>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $base = [
                    'Avaliação' => array_combine(array_column($produtos, 'id'), array_map(fn ($p) => (int) $p['total_avaliacoes'] ? number_format((float) $p['avaliacao_media'], 1, ',', '') . ' de 5 (' . plural($p['total_avaliacoes'], 'avaliação', 'avaliações') . ')' : 'Sem avaliações', $produtos)),
                    'Disponibilidade' => array_combine(array_column($produtos, 'id'), array_map(fn ($p) => (int) $p['stock'] > 0 ? 'Em stock' : 'Esgotado', $produtos)),
                ];
                $grupos = ['Resumo' => $base] + $linhas;
                foreach ($grupos as $grupo => $carac): ?>
                <tr><th colspan="<?= $n + 1 ?>" scope="colgroup" class="sticky left-0 bg-elevado px-4 py-2 text-left text-xs font-bold text-suave"><?= e($grupo) ?></th></tr>
                <?php foreach ($carac as $nome => $valores): $dif = $n > 1 && $difere($valores); ?>
                <tr class="border-t border-linha <?= $dif ? '' : 'igual' ?>" <?= $dif ? 'data-difere' : '' ?>>
                    <th scope="row" class="sticky left-0 z-10 bg-superficie px-4 py-3 text-left font-medium text-suave"><?= e($nome) ?></th>
                    <?php foreach ($produtos as $p): ?>
                    <td class="px-4 py-3 <?= $dif ? 'font-semibold' : '' ?>"><?= isset($valores[$p['id']]) ? e($valores[$p['id']]) : '<span class="text-suave">—</span>' ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-suave">As características a negrito são diferentes entre os produtos. Em telemóvel, deslize a tabela para o lado.</p>
    <?php endif; ?>
</div>
