<?php
/**
 * @var array $produto @var array $imagens @var array $especificacoes @var array $relacionados
 * @var array $breadcrumb @var array $entregaLuanda
 */
$p        = $produto;
$desc     = descontoPercent($p['preco_anterior'] ? (float) $p['preco_anterior'] : null, (float) $p['preco']);
$esgotado = (int) $p['stock'] < 1;
$ofertaActiva = $p['oferta_ate'] && strtotime($p['oferta_ate']) > time();
$galeria  = $imagens ?: [['ficheiro' => null, 'alt' => $p['nome']]];
$msgWhats = "Olá DIOTEKNO! Quero encomendar: {$p['nome']} (ref. {$p['sku']}) — " . strip_tags(kz($p['preco']));
?>
<div class="container pt-4 pb-28 lg:pb-14">
    <nav aria-label="Caminho" class="text-sm text-suave">
        <ol class="flex flex-wrap items-center gap-1">
            <?php foreach ($breadcrumb as $i => [$nome, $href]): ?>
            <li class="flex items-center gap-1 <?= $i === count($breadcrumb) - 1 ? 'min-w-0' : '' ?>">
                <?php if ($i < count($breadcrumb) - 1): ?>
                    <a href="<?= url($href) ?>" class="hover:text-tinta"><?= e($nome) ?></a><?= icone('seta-dir', 'h-3.5 w-3.5') ?>
                <?php else: ?>
                    <span class="truncate text-tinta font-medium" aria-current="page"><?= e($nome) ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="mt-4 grid gap-6 lg:grid-cols-[1.1fr_1fr] lg:gap-10">
        <!-- ===== Galeria ===== -->
        <section aria-label="Imagens do produto" data-galeria>
            <div class="relative cartao overflow-hidden">
                <div class="aspect-square bg-white flex items-center justify-center">
                    <img src="<?= $galeria[0]['ficheiro'] ? asset('img/produtos/' . $galeria[0]['ficheiro']) : imagemProduto($p) ?>"
                         alt="<?= e($galeria[0]['alt'] ?: $p['nome']) ?>" class="h-full w-full object-contain p-8 sm:p-14"
                         width="800" height="800" fetchpriority="high" data-galeria-principal>
                </div>
                <div class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
                    <?php if ($desc): ?><span class="etiqueta text-sm num">-<?= $desc ?>%</span><?php endif; ?>
                    <?php if ((int) $p['novidade']): ?><span class="selo-novo">Novo</span><?php endif; ?>
                </div>
            </div>
            <?php if (count($galeria) > 1): ?>
            <div class="mt-3 flex gap-2 overflow-x-auto sem-scrollbar">
                <?php foreach ($galeria as $i => $img): ?>
                <button type="button" class="shrink-0 h-20 w-20 rounded-xl border-2 bg-white p-1.5 <?= $i === 0 ? 'border-marca' : 'border-transparent' ?>"
                        data-galeria-mini="<?= asset('img/produtos/' . $img['ficheiro']) ?>" aria-label="Ver imagem <?= $i + 1 ?>">
                    <img src="<?= asset('img/produtos/' . $img['ficheiro']) ?>" alt="" class="h-full w-full object-contain" loading="lazy">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- ===== Informação e compra ===== -->
        <section class="lg:sticky lg:top-40 lg:self-start" aria-labelledby="nome-produto">
            <?php if ($p['marca_nome']): ?>
            <a href="<?= url('/marca/' . $p['marca_slug']) ?>" class="text-sm font-semibold text-marca hover:underline"><?= e($p['marca_nome']) ?></a>
            <?php endif; ?>
            <?php $favorito = in_array((int) $p['id'], favoritosIds(), true); ?>
            <div class="mt-1 flex items-start justify-between gap-3">
                <h1 id="nome-produto" class="font-display text-2xl sm:text-3xl font-extrabold leading-tight"><?= e($p['nome']) ?></h1>
                <div class="flex shrink-0 gap-2">
                <button type="button" data-comparar="<?= (int) $p['id'] ?>" aria-pressed="<?= $naComparacao ? 'true' : 'false' ?>"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-linha text-suave hover:text-marca aria-pressed:text-marca aria-pressed:border-marca/50 aria-pressed:bg-marca-suave transition-colors"
                        aria-label="<?= $naComparacao ? 'Retirar da comparação' : 'Comparar' ?>" title="Comparar">
                    <?= icone('comparar', 'h-5 w-5') ?>
                </button>
                <button type="button" data-favorito="<?= (int) $p['id'] ?>" aria-pressed="<?= $favorito ? 'true' : 'false' ?>"
                        class="shrink-0 inline-flex h-11 w-11 items-center justify-center rounded-xl border border-linha text-suave hover:text-coral aria-pressed:text-coral aria-pressed:border-coral/40 transition-colors"
                        aria-label="<?= $favorito ? 'Remover dos favoritos' : 'Guardar nos favoritos' ?>">
                    <?= icone('coracao', 'h-6 w-6 ' . ($favorito ? 'fill-current' : '')) ?>
                </button>
                </div>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-suave">
                <?php if ((int) $p['total_avaliacoes'] > 0): ?>
                <a href="#avaliacoes" class="flex items-center gap-1 hover:text-tinta">
                    <span class="flex text-aviso" aria-hidden="true">
                        <?php for ($s = 1; $s <= 5; $s++): ?><?= icone('estrela', 'h-4 w-4 ' . ($s <= round((float) $p['avaliacao_media']) ? 'fill-current' : '')) ?><?php endfor; ?>
                    </span>
                    <span class="font-semibold text-tinta num"><?= number_format((float) $p['avaliacao_media'], 1, ',', '') ?></span>
                    <span class="num">(<?= (int) $p['total_avaliacoes'] ?> avaliações)</span>
                </a>
                <?php endif; ?>
                <span>Ref. <span class="num"><?= e($p['sku']) ?></span></span>
            </div>

            <?php if ($p['resumo']): ?><p class="mt-4 text-[15px] leading-7 text-tinta/85"><?= e($p['resumo']) ?></p><?php endif; ?>

            <!-- Preço -->
            <div class="mt-5 cartao p-4 sm:p-5">
                <?php if ($ofertaActiva && $desc): ?>
                <p class="mb-3 inline-flex items-center gap-2 rounded-lg bg-coral-suave px-2.5 py-1 text-sm font-semibold text-coral" data-contagem="<?= e(date('c', strtotime($p['oferta_ate']))) ?>">
                    <?= icone('relogio', 'h-4 w-4') ?> Promoção termina em <span class="num" data-contagem-texto>--:--:--</span>
                </p>
                <?php endif; ?>

                <div class="flex flex-wrap items-end gap-x-3 gap-y-1">
                    <p class="preco text-4xl sm:text-[2.75rem] leading-none <?= $desc ? 'text-coral' : '' ?>"><?= kz($p['preco']) ?></p>
                    <?php if ($desc): ?>
                    <p class="pb-1"><span class="preco-anterior text-base"><?= kz($p['preco_anterior']) ?></span></p>
                    <?php endif; ?>
                </div>
                <?php if ($desc): ?>
                <p class="mt-1 text-sm font-semibold text-sucesso">Poupa <?= kz($p['preco_anterior'] - $p['preco']) ?></p>
                <?php endif; ?>
                <p class="mt-1 text-xs text-suave">IVA (<?= IVA_TAXA ?>%) incluído. Factura com NIF disponível.</p>
                <?php if ($pontosGanhar > 0): ?><p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-marca-suave px-2 py-1 text-xs font-semibold text-marca"><?= icone('estrela', 'h-3.5 w-3.5') ?> Ganha <?= plural($pontosGanhar, 'ponto', 'pontos') ?> com esta compra</p><?php endif; ?>

                <p class="mt-4 flex items-center gap-2 text-sm font-semibold <?= $esgotado ? 'text-suave' : ((int) $p['stock'] <= 3 ? 'text-aviso' : 'text-sucesso') ?>">
                    <span class="h-2 w-2 rounded-full bg-current" aria-hidden="true"></span>
                    <?php if ($esgotado): ?>Esgotado — pergunte-nos quando volta
                    <?php elseif ((int) $p['stock'] <= 3): ?>Últimas <?= (int) $p['stock'] ?> unidades em stock
                    <?php else: ?>Em stock, pronto a entregar<?php endif; ?>
                </p>

                <?php if (!$esgotado): ?>
                <div class="mt-4 flex gap-2">
                    <div class="inline-flex items-center rounded-xl border border-linha" data-qtd-produto data-max="<?= min((int) $p['stock'], \App\Models\Carrinho::QTD_MAX) ?>">
                        <button type="button" class="h-12 w-11 inline-flex items-center justify-center hover:bg-elevado rounded-l-xl" data-passo="-1" aria-label="Diminuir quantidade"><?= icone('menos', 'h-4 w-4') ?></button>
                        <input type="number" value="1" min="1" max="<?= min((int) $p['stock'], \App\Models\Carrinho::QTD_MAX) ?>" class="w-10 h-12 bg-transparent text-center font-semibold num [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" aria-label="Quantidade" data-qtd-campo>
                        <button type="button" class="h-12 w-11 inline-flex items-center justify-center hover:bg-elevado rounded-r-xl" data-passo="1" aria-label="Aumentar quantidade"><?= icone('mais', 'h-4 w-4') ?></button>
                    </div>
                    <button type="button" class="btn-primario h-12 flex-1 text-base" data-adicionar="<?= (int) $p['id'] ?>" data-com-quantidade>
                        <?= icone('carrinho') ?> Adicionar ao carrinho
                    </button>
                </div>
                <button type="button" class="btn-secundario mt-2 h-12 w-full" data-adicionar="<?= (int) $p['id'] ?>" data-com-quantidade data-comprar-ja>Comprar já</button>
                <?php endif; ?>
                <a href="<?= e(linkWhatsapp($msgWhats)) ?>" target="_blank" rel="noopener" class="btn-whatsapp mt-2 h-12 w-full">
                    <?= icone('whatsapp') ?> <?= $esgotado ? 'Avisar-me quando chegar' : 'Encomendar pelo WhatsApp' ?>
                </a>
            </div>

            <!-- Entrega e garantia -->
            <ul class="mt-4 cartao divide-y divide-linha text-sm">
                <li class="flex gap-3 p-4">
                    <?= icone('camiao', 'h-5 w-5 shrink-0 text-marca') ?>
                    <div>
                        <p class="font-semibold">Entrega em Luanda em 24 horas</p>
                        <p class="text-suave">
                            <?= $entregaLuanda ? kz($entregaLuanda['preco']) . ', grátis acima de ' . kz(PORTES_GRATIS_LUANDA) . '.' : '' ?>
                            Entrega expressa no próprio dia para encomendas até às 13h.
                        </p>
                    </div>
                </li>
                <li class="flex gap-3 p-4">
                    <?= icone('localizacao', 'h-5 w-5 shrink-0 text-marca') ?>
                    <div>
                        <p class="font-semibold">Todas as províncias em 3 a 6 dias</p>
                        <p class="text-suave"><a href="<?= url('/entregas') ?>" class="link font-medium">Ver preços por província</a></p>
                    </div>
                </li>
                <li class="flex gap-3 p-4">
                    <?= icone('escudo', 'h-5 w-5 shrink-0 text-marca') ?>
                    <div>
                        <p class="font-semibold">Garantia oficial de <?= (int) $p['garantia_meses'] ?> meses</p>
                        <p class="text-suave">Produto novo e selado. Troca em 7 dias se vier com defeito.</p>
                    </div>
                </li>
                <li class="flex gap-3 p-4">
                    <?= icone('cartao', 'h-5 w-5 shrink-0 text-marca') ?>
                    <div>
                        <p class="font-semibold">Multicaixa Express, referência ou transferência</p>
                        <p class="text-suave">Também Afrimoney, Unitel Money, Visa e Mastercard.</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>

    <!-- ===== Descrição e especificações ===== -->
    <div class="mt-12 grid gap-10 lg:grid-cols-[1.1fr_1fr]">
        <section aria-labelledby="t-desc">
            <h2 id="t-desc" class="titulo-seccao mb-4">Descrição</h2>
            <div class="prosa"><?= $p['descricao'] /* HTML controlado pelo painel admin */ ?></div>
        </section>

        <?php if ($especificacoes): ?>
        <section aria-labelledby="t-specs">
            <h2 id="t-specs" class="titulo-seccao mb-4">Especificações</h2>
            <div class="cartao overflow-hidden">
                <?php foreach ($especificacoes as $grupo => $linhas): ?>
                <table class="w-full text-sm">
                    <caption class="bg-elevado px-4 py-2 text-left text-xs font-bold text-suave"><?= e($grupo) ?></caption>
                    <tbody class="divide-y divide-linha">
                        <?php foreach ($linhas as $l): ?>
                        <tr>
                            <th scope="row" class="w-2/5 px-4 py-2.5 text-left font-medium text-suave"><?= e($l['nome']) ?></th>
                            <td class="px-4 py-2.5 font-medium"><?= e($l['valor']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <!-- ===== Avaliações ===== -->
    <section id="avaliacoes" class="mt-12 scroll-mt-40" aria-labelledby="t-aval">
        <h2 id="t-aval" class="titulo-seccao mb-4">Avaliações de clientes</h2>
        <?php $totalAval = array_sum($distribuicao); $mediaAval = (float) $p['avaliacao_media']; ?>
        <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
            <!-- Resumo -->
            <div class="space-y-4 lg:sticky lg:top-40 lg:self-start">
                <div class="cartao p-5">
                    <?php if ($totalAval): ?>
                    <div class="flex items-center gap-4">
                        <p class="preco text-5xl num"><?= number_format($mediaAval, 1, ',', '') ?></p>
                        <div>
                            <p class="flex text-aviso" aria-label="<?= number_format($mediaAval, 1, ',', '') ?> de 5 estrelas">
                                <?php for ($s = 1; $s <= 5; $s++): ?><?= icone('estrela', 'h-5 w-5 ' . ($s <= round($mediaAval) ? 'fill-current' : 'text-linha')) ?><?php endfor; ?>
                            </p>
                            <p class="text-sm text-suave num"><?= plural($totalAval, 'avaliação', 'avaliações') ?></p>
                        </div>
                    </div>
                    <ul class="mt-4 space-y-1.5" aria-label="Distribuição das avaliações">
                        <?php foreach ($distribuicao as $estrelas => $n): ?>
                        <li class="flex items-center gap-2 text-xs">
                            <span class="w-14 text-suave num"><?= $estrelas ?> <?= $estrelas === 1 ? 'estrela' : 'estrelas' ?></span>
                            <span class="h-2 flex-1 rounded-full bg-elevado overflow-hidden"><span class="block h-full rounded-full bg-aviso" style="width: <?= $totalAval ? round($n / $totalAval * 100) : 0 ?>%"></span></span>
                            <span class="w-6 text-right text-suave num"><?= $n ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="font-semibold">Ainda sem avaliações</p>
                    <p class="mt-1 text-sm text-suave">Só clientes que compraram e receberam este produto na DIOTEKNO podem avaliá-lo.</p>
                    <?php endif; ?>
                </div>

                <!-- Avaliar -->
                <?php if (!\Core\Auth::autenticado()): ?>
                    <p class="text-sm text-suave">Comprou este produto? <a href="<?= url('/entrar?voltar=' . urlencode('/produto/' . $p['slug'] . '#avaliar')) ?>" class="link">Entre para o avaliar</a>.</p>
                <?php elseif ($elegibilidade['existente']): ?>
                    <p class="rounded-xl bg-elevado p-3 text-sm"><?= $elegibilidade['existente']['estado'] === 'pendente' ? 'A sua avaliação está a ser revista e aparece aqui em breve.' : ($elegibilidade['existente']['estado'] === 'aprovada' ? 'Obrigado por avaliar este produto.' : 'A sua avaliação não foi publicada por não cumprir as regras da loja.') ?></p>
                <?php elseif ($elegibilidade['pedido_id']): ?>
                <form id="avaliar" action="<?= url('/avaliacoes') ?>" method="post" class="cartao p-5 space-y-4 scroll-mt-40" novalidate>
                    <?= \Core\Csrf::campo() ?>
                    <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                    <h3 class="font-display text-lg font-bold">Avaliar este produto</h3>
                    <fieldset>
                        <legend class="rotulo">A sua nota</legend>
                        <div class="estrelas-input" <?= ariaErro('nota') ?>>
                            <?php $notaAntiga = (int) (\Core\Flash::antigo()['nota'] ?? 0); for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" id="nota-<?= $s ?>" name="nota" value="<?= $s ?>" class="sr-only" <?= $notaAntiga === $s ? 'checked' : '' ?> required>
                            <label for="nota-<?= $s ?>" class="cursor-pointer p-0.5" title="<?= $s ?> de 5"><?= icone('estrela', 'h-8 w-8') ?><span class="sr-only"><?= $s ?> <?= $s === 1 ? 'estrela' : 'estrelas' ?></span></label>
                            <?php endfor; ?>
                        </div>
                        <?= erroCampo('nota') ?>
                    </fieldset>
                    <div>
                        <label for="av-titulo" class="rotulo">Resumo <span class="font-normal text-suave">(opcional)</span></label>
                        <input id="av-titulo" name="titulo" maxlength="140" value="<?= antigo('titulo') ?>" placeholder="Ex.: Bateria aguenta o dia todo" class="campo">
                    </div>
                    <div>
                        <label for="av-com" class="rotulo">Comentário <span class="font-normal text-suave">(opcional)</span></label>
                        <textarea id="av-com" name="comentario" rows="4" maxlength="2000" class="campo" placeholder="O que gostou e o que podia ser melhor?" <?= ariaErro('comentario') ?>><?= antigo('comentario') ?></textarea>
                        <?= erroCampo('comentario') ?>
                    </div>
                    <p class="text-xs text-suave">Publicamos com o seu primeiro nome e a inicial do apelido. Ganha <?= \App\Servicos\Fidelidade::cfg()['pontos_avaliacao'] ?> pontos quando for aprovada.</p>
                    <button class="btn-primario w-full">Publicar avaliação</button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Lista -->
            <div class="min-w-0">
                <?php if ($avaliacoes): ?>
                <div class="mb-3 flex items-center justify-end gap-2">
                    <label for="ordem-aval" class="text-sm text-suave">Ordenar</label>
                    <select id="ordem-aval" class="campo h-9 w-auto text-sm" data-ordem-avaliacoes>
                        <option value="recentes">Mais recentes</option><option value="melhores">Melhor nota</option><option value="piores">Pior nota</option>
                    </select>
                </div>
                <div class="space-y-3" data-lista-avaliacoes data-url="<?= url('/produto/' . $p['slug'] . '/avaliacoes') ?>">
                    <?php require __DIR__ . '/_avaliacoes-lista.php'; ?>
                </div>
                <?php if ($maisAvaliacoes): ?><button type="button" class="btn-secundario mt-4 w-full" data-mais-avaliacoes data-pagina="2">Ver mais avaliações</button><?php endif; ?>
                <?php elseif ($totalAval === 0): ?>
                <div class="cartao border-dashed p-8 text-center text-sm text-suave">As avaliações de quem comprou vão aparecer aqui.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== Relacionados ===== -->
    <?php if ($relacionados): ?>
    <section class="mt-12" aria-labelledby="t-rel">
        <h2 id="t-rel" class="titulo-seccao mb-4">Também pode gostar</h2>
        <div class="faixa sm:grid sm:grid-cols-3 lg:grid-cols-4 sm:gap-4">
            <?php foreach (array_slice($relacionados, 0, 8) as $p): ?>
            <div class="w-[46%] sm:w-auto"><?php require RAIZ . '/app/Views/partials/produto-card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- Barra de compra fixa (mobile) -->
<?php $p = $produto; if (!$esgotado): ?>
<div class="lg:hidden fixed inset-x-0 bottom-16 z-40 border-t border-linha bg-superficie/95 backdrop-blur px-4 py-2.5 pb-seguro flex items-center gap-3" data-barra-compra>
    <div class="min-w-0 flex-1">
        <p class="truncate text-xs text-suave"><?= e($p['nome']) ?></p>
        <p class="preco text-lg leading-tight <?= $desc ? 'text-coral' : '' ?>"><?= kz($p['preco']) ?></p>
    </div>
    <button type="button" class="btn-primario h-11" data-adicionar="<?= (int) $p['id'] ?>" data-com-quantidade>Adicionar</button>
</div>
<?php endif; ?>
