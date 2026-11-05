<?php
/**
 * @var array $itens @var array $resumo @var array $enderecos @var ?array $endereco
 * @var array $opcoesEntrega @var string $metodoEntrega @var array $entrega @var float $total
 * @var array $problemas @var array $metodos @var array $cliente @var string $chave @var array $antigo
 */
$metodoSel = $antigo['metodo_pagamento'] ?? array_key_first($metodos);
$telPagamento = $antigo['telefone_pagamento'] ?? telefoneFormatado($cliente['telefone']);
?>
<div class="container py-6 sm:py-8">
    <h1 class="font-display text-3xl sm:text-4xl font-extrabold">Finalizar compra</h1>

    <form action="<?= url('/checkout/confirmar') ?>" method="post" class="mt-6 grid gap-6 lg:grid-cols-[1fr_400px] lg:items-start" data-checkout novalidate>
        <?= \Core\Csrf::campo() ?>
        <input type="hidden" name="chave" value="<?= e($chave) ?>">

        <div class="space-y-5 min-w-0">
            <!-- 1. Morada -->
            <section class="cartao p-5 sm:p-6" aria-labelledby="t-morada">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 id="t-morada" class="flex items-center gap-3 font-display text-lg font-bold">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-tinta text-sm text-superficie">1</span> Morada de entrega
                    </h2>
                    <a href="<?= url('/conta/enderecos?novo=1&voltar=' . urlencode('/checkout')) ?>" class="link text-sm">Nova morada</a>
                </div>
                <?php if ($enderecos): ?>
                <div class="grid gap-2 sm:grid-cols-2">
                    <?php foreach ($enderecos as $end): ?>
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-linha p-4 has-[:checked]:border-marca has-[:checked]:bg-marca-suave/40 transition-colors">
                        <input type="radio" name="endereco_id" value="<?= (int) $end['id'] ?>" <?= $endereco && (int) $end['id'] === (int) $endereco['id'] ? 'checked' : '' ?>
                               class="mt-1 h-4 w-4 accent-[rgb(var(--marca))]" data-recalcular>
                        <span class="min-w-0 text-sm">
                            <span class="block font-semibold"><?= e($end['nome_destinatario']) ?></span>
                            <span class="block text-suave"><?= e($end['bairro']) ?>, <?= e($end['municipio']) ?></span>
                            <span class="block text-suave"><?= e($end['provincia']) ?></span>
                            <?php if ($end['referencia']): ?><span class="block truncate text-xs text-suave mt-1"><?= e($end['referencia']) ?></span><?php endif; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?= erroCampo('endereco_id') ?>
                <?php else: ?>
                <div class="rounded-xl border border-dashed border-linha p-5 text-center">
                    <p class="text-sm text-suave">Ainda não tem moradas guardadas.</p>
                    <a href="<?= url('/conta/enderecos?novo=1&voltar=' . urlencode('/checkout')) ?>" class="btn-primario mt-3"><?= icone('mais', 'h-4 w-4') ?> Adicionar morada</a>
                </div>
                <?php endif; ?>
            </section>

            <!-- 2. Entrega -->
            <section class="cartao p-5 sm:p-6" aria-labelledby="t-entrega">
                <h2 id="t-entrega" class="mb-4 flex items-center gap-3 font-display text-lg font-bold">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-tinta text-sm text-superficie">2</span> Entrega
                </h2>
                <?php require __DIR__ . '/_entrega.php'; ?>
            </section>

            <!-- 3. Pagamento -->
            <section class="cartao p-5 sm:p-6" aria-labelledby="t-pagamento">
                <h2 id="t-pagamento" class="mb-4 flex items-center gap-3 font-display text-lg font-bold">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-tinta text-sm text-superficie">3</span> Pagamento
                </h2>
                <div class="space-y-2" role="radiogroup" aria-labelledby="t-pagamento">
                    <?php foreach ($metodos as $m): ?>
                    <div class="rounded-xl border border-linha has-[:checked]:border-marca has-[:checked]:bg-marca-suave/40 transition-colors">
                        <label class="flex cursor-pointer items-start gap-3 p-4">
                            <input type="radio" name="metodo_pagamento" value="<?= e($m['codigo']) ?>" <?= $m['codigo'] === $metodoSel ? 'checked' : '' ?>
                                   class="mt-1 h-4 w-4 accent-[rgb(var(--marca))]" data-metodo-pagamento data-pede-telefone="<?= $m['pede_telefone'] ? '1' : '0' ?>">
                            <span>
                                <span class="block font-semibold"><?= e($m['nome']) ?></span>
                                <span class="block text-sm text-suave"><?= e($m['descricao']) ?></span>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?= erroCampo('metodo_pagamento') ?>

                <div class="mt-4" data-campo-telefone <?= ($metodos[$metodoSel]['pede_telefone'] ?? false) ? '' : 'hidden' ?>>
                    <label for="tel-pag" class="rotulo">Número que vai pagar</label>
                    <div class="flex max-w-xs">
                        <span class="inline-flex items-center rounded-l-xl border border-r-0 border-linha bg-elevado px-3 text-sm text-suave num">+244</span>
                        <input id="tel-pag" name="telefone_pagamento" type="tel" inputmode="tel" autocomplete="tel-national" value="<?= e($telPagamento) ?>" class="campo rounded-l-none num" <?= ariaErro('telefone_pagamento') ?>>
                    </div>
                    <?php if (!isset(\Core\Flash::erros()['telefone_pagamento'])): ?><p class="mt-1.5 text-xs text-suave">Recebe o pedido de pagamento neste número depois de confirmar.</p><?php endif; ?>
                    <?= erroCampo('telefone_pagamento') ?>
                </div>
            </section>

            <!-- Factura e notas -->
            <section class="cartao p-5 sm:p-6 space-y-4" aria-labelledby="t-factura">
                <h2 id="t-factura" class="font-display text-lg font-bold">Factura e observações</h2>
                <div>
                    <label for="nif" class="rotulo">NIF na factura <span class="font-normal text-suave">(opcional)</span></label>
                    <input id="nif" name="nif_factura" value="<?= e($antigo['nif_factura'] ?? ($cliente['nif'] ?? '')) ?>" class="campo max-w-xs uppercase num" <?= ariaErro('nif_factura') ?>>
                    <?php if (!isset(\Core\Flash::erros()['nif_factura'])): ?><p class="mt-1.5 text-xs text-suave">Para empresas ou para deduzir despesas. Sem NIF, a factura sai como consumidor final.</p><?php endif; ?>
                    <?= erroCampo('nif_factura') ?>
                </div>
                <div>
                    <label for="notas" class="rotulo">Observações para a entrega <span class="font-normal text-suave">(opcional)</span></label>
                    <textarea id="notas" name="notas" rows="2" maxlength="500" placeholder="Ex.: ligar 30 minutos antes; entregar ao porteiro" class="campo"><?= e($antigo['notas'] ?? '') ?></textarea>
                </div>
            </section>
        </div>

        <!-- Resumo -->
        <aside class="cartao p-5 sm:p-6 lg:sticky lg:top-6" aria-label="Resumo do pedido">
            <h2 class="mb-2 font-display text-lg font-bold">Resumo</h2>
            <?php if ($pontosMax > 0): ?>
            <label class="mb-3 flex cursor-pointer items-start gap-3 rounded-xl border border-linha p-3 has-[:checked]:border-sucesso has-[:checked]:bg-sucesso/5">
                <input type="checkbox" name="usar_pontos" value="1" <?= $pontosUsar ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 accent-[rgb(var(--sucesso))]" data-recalcular>
                <span class="text-sm">
                    <span class="block font-semibold">Usar <?= plural($pontosMax, 'ponto', 'pontos') ?> (−<?= kz(\App\Servicos\Fidelidade::valorEmKz($pontosMax)) ?>)</span>
                    <span class="block text-xs text-suave">Tem <?= number_format($saldoPontos, 0, ',', ' ') ?> pontos. Os pontos pagam até <?= \App\Servicos\Fidelidade::cfg()['maximo_percentagem'] ?>% dos produtos.</span>
                </span>
            </label>
            <?php elseif ($saldoPontos > 0): ?>
            <p class="mb-3 rounded-xl bg-elevado p-3 text-xs text-suave">Tem <?= plural($saldoPontos, 'ponto', 'pontos') ?>. Pode usá-los a partir de <?= \App\Servicos\Fidelidade::cfg()['minimo_resgate'] ?> pontos.</p>
            <?php endif; ?>
            <?php require __DIR__ . '/_resumo.php'; ?>

            <label class="mt-5 flex items-start gap-2.5 text-sm cursor-pointer">
                <input type="checkbox" name="aceita_termos" value="1" <?= !empty($antigo['aceita_termos']) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded accent-[rgb(var(--marca))]" <?= ariaErro('aceita_termos') ?>>
                <span>Aceito os <a href="<?= url('/termos') ?>" target="_blank" class="link">termos e condições</a> da compra.</span>
            </label>
            <?= erroCampo('aceita_termos') ?>

            <button class="btn-primario mt-4 h-12 w-full text-base" <?= $problemas ? 'disabled' : '' ?> data-confirmar-pedido>Confirmar pedido</button>
            <p class="mt-3 flex items-start gap-2 text-xs text-suave"><?= icone('escudo', 'h-4 w-4 shrink-0') ?> O stock fica reservado para si até ao fim do prazo de pagamento.</p>
        </aside>
    </form>
</div>
