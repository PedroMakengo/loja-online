<?php /** @var array $itens @var array $resumo @var array $zonas */ ?>
<div class="container pt-6 pb-14">
    <h1 class="font-display text-3xl sm:text-4xl font-extrabold">Carrinho</h1>

    <div id="pagina-carrinho" data-actualizar>
    <?php if (!$itens): ?>
        <div class="mt-6 cartao p-10 text-center">
            <span class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('carrinho', 'h-8 w-8') ?></span>
            <p class="font-display text-xl font-bold">O carrinho está vazio</p>
            <p class="mt-1 text-suave">Os produtos que adicionar ficam guardados durante 30 dias neste dispositivo.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="<?= url('/promocoes') ?>" class="btn-primario">Ver promoções</a>
                <a href="<?= url('/produtos') ?>" class="btn-secundario">Explorar produtos</a>
            </div>
        </div>
    <?php else: ?>
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_380px] lg:items-start">
            <!-- Itens -->
            <ul class="cartao divide-y divide-linha">
                <?php foreach ($itens as $i): ?>
                <li class="flex gap-3 sm:gap-4 p-4" data-item="<?= (int) $i['id'] ?>">
                    <a href="<?= url('/produto/' . $i['slug']) ?>" class="shrink-0">
                        <img src="<?= imagemProduto($i) ?>" alt="" class="h-24 w-24 sm:h-28 sm:w-28 rounded-xl bg-white object-contain p-2" width="112" height="112">
                    </a>
                    <div class="min-w-0 flex-1 sm:flex sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <?php if ($i['marca_nome']): ?><p class="text-xs font-medium text-suave"><?= e($i['marca_nome']) ?></p><?php endif; ?>
                            <a href="<?= url('/produto/' . $i['slug']) ?>" class="line-clamp-2 font-semibold leading-snug hover:text-marca"><?= e($i['nome']) ?></a>
                            <p class="mt-1 text-sm text-suave num"><?= kz($i['preco']) ?> / unidade</p>
                            <?php if ((int) $i['stock'] <= 3): ?><p class="text-xs font-semibold text-aviso">Restam <?= (int) $i['stock'] ?> em stock</p><?php endif; ?>
                        </div>
                        <div class="mt-3 sm:mt-0 flex sm:flex-col items-center sm:items-end justify-between gap-2">
                            <p class="preco text-lg sm:order-2"><?= kz($i['preco'] * $i['quantidade']) ?></p>
                            <div class="flex items-center gap-3 sm:order-1">
                                <?php $produtoQtd = $i; require RAIZ . '/app/Views/partials/quantidade.php'; ?>
                                <button type="button" class="btn-icone h-9 w-9 text-suave hover:text-coral" data-remover="<?= (int) $i['id'] ?>" aria-label="Remover <?= e($i['nome']) ?>"><?= icone('lixo', 'h-5 w-5') ?></button>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Resumo -->
            <aside class="cartao p-5 lg:sticky lg:top-40 space-y-4" aria-label="Resumo do pedido">
                <h2 class="font-display text-lg font-bold">Resumo</h2>

                <?php if ($resumo['falta_portes_gratis'] > 0): ?>
                <div>
                    <p class="text-sm">Faltam <strong class="num"><?= kz($resumo['falta_portes_gratis']) ?></strong> para entrega grátis em Luanda.</p>
                    <div class="mt-2 h-1.5 rounded-full bg-elevado overflow-hidden"><div class="h-full rounded-full bg-marca" style="width: <?= $resumo['progresso_portes'] ?>%"></div></div>
                </div>
                <?php endif; ?>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-suave">Subtotal (<?= $resumo['quantidade'] ?> <?= $resumo['quantidade'] == 1 ? 'artigo' : 'artigos' ?>)</dt><dd class="font-semibold num"><?= kz($resumo['subtotal']) ?></dd></div>
                    <?php if ($resumo['poupanca'] > 0): ?>
                    <div class="flex justify-between text-sucesso"><dt>Poupança em promoções</dt><dd class="font-semibold num">−<?= kz($resumo['poupanca']) ?></dd></div>
                    <?php endif; ?>
                    <?php if ($resumo['desconto'] > 0): ?>
                    <div class="flex justify-between text-sucesso"><dt>Cupão <?= e($resumo['cupao']['codigo']) ?></dt><dd class="font-semibold num">−<?= kz($resumo['desconto']) ?></dd></div>
                    <?php endif; ?>
                    <div class="flex justify-between gap-3">
                        <dt class="text-suave">
                            <label for="estimar-provincia">Entrega para</label>
                            <select id="estimar-provincia" class="mt-1 block campo h-9 w-44 text-sm" data-estimar-entrega
                                    data-zonas='<?= e(json_encode($zonas, JSON_UNESCAPED_UNICODE)) ?>'
                                    data-subtotal="<?= (float) $resumo['total'] ?>" data-gratis="<?= PORTES_GRATIS_LUANDA ?>">
                                <?php foreach (array_unique(array_column($zonas, 'provincia')) as $prov): ?>
                                <option value="<?= e($prov) ?>"><?= e($prov) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </dt>
                        <dd class="text-right self-end"><span class="font-semibold num" data-entrega-preco>—</span><span class="block text-xs text-suave" data-entrega-prazo></span></dd>
                    </div>
                </dl>

                <!-- Cupão -->
                <?php if ($resumo['cupao']): ?>
                <div class="flex items-center justify-between rounded-xl bg-marca-suave px-3 py-2 text-sm">
                    <span><strong class="text-marca"><?= e($resumo['cupao']['codigo']) ?></strong> <span class="text-suave"><?= e($resumo['cupao']['descricao'] ?? '') ?></span></span>
                    <form action="<?= url('/carrinho/cupao/remover') ?>" method="post" data-ajax-form data-recarregar-carrinho>
                        <?= \Core\Csrf::campo() ?><button class="text-xs font-semibold text-suave hover:text-coral">Remover</button>
                    </form>
                </div>
                <?php if (!empty($resumo['cupao']['aviso'])): ?><p class="text-xs text-aviso"><?= e($resumo['cupao']['aviso']) ?></p><?php endif; ?>
                <?php else: ?>
                <form action="<?= url('/carrinho/cupao') ?>" method="post" class="flex gap-2" data-ajax-form data-recarregar-carrinho>
                    <?= \Core\Csrf::campo() ?>
                    <label for="cupao" class="sr-only">Código de desconto</label>
                    <input id="cupao" name="codigo" required placeholder="Código de desconto" class="campo h-10 uppercase placeholder:normal-case" autocomplete="off">
                    <button class="btn-secundario h-10 px-4">Aplicar</button>
                </form>
                <?php endif; ?>

                <div class="border-t border-linha pt-4 flex items-baseline justify-between">
                    <span class="font-semibold">Total</span>
                    <span class="preco text-3xl"><?= kz($resumo['total']) ?></span>
                </div>
                <p class="-mt-2 text-xs text-suave">Inclui <?= kz($resumo['iva_incluido']) ?> de IVA. Entrega somada no checkout.</p>

                <a href="<?= url('/checkout') ?>" class="btn-primario h-12 w-full text-base">Finalizar compra</a>
                <a href="<?= e(linkWhatsapp('Olá! Quero finalizar uma compra de ' . strip_tags(kz($resumo['total'])) . ' no site.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp w-full"><?= icone('whatsapp') ?> Comprar com ajuda no WhatsApp</a>
            </aside>
        </div>
    <?php endif; ?>
    </div>
</div>
