<?php
/**
 * @var array $pedido @var array $itens @var array $historico @var ?array $pagamento
 * @var array $metodos @var array $banco @var bool $simulado @var array $cliente
 */
use App\Models\Pedido;
use App\Servicos\Pagamentos;

$pd          = $pedido;
$pg          = $pagamento;
$tipo        = Pagamentos::METODOS[$pg['metodo'] ?? '']['tipo'] ?? null;
$pendente    = $pd['estado'] === 'pendente';
$pgPendente  = $pendente && $pg && $pg['estado'] === 'pendente';
$pgFalhou    = $pendente && $pg && in_array($pg['estado'], ['expirado', 'falhado'], true);
$cancelado   = in_array($pd['estado'], ['cancelado', 'reembolsado'], true);

// Datas de cada etapa (primeira vez que o pedido entrou nesse estado)
$datasEtapa = [];
foreach ($historico as $h) { $datasEtapa[$h['estado']] ??= $h['criado_em']; }
$ordemEtapas = array_keys(Pedido::ETAPAS);
$indiceActual = array_search($pd['estado'], $ordemEtapas, true);
$estadoPedido = $pd['estado'];
?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>

        <div class="min-w-0 space-y-6" data-pedido="<?= e($pd['numero']) ?>" <?= $pgPendente && $tipo !== 'manual' ? 'data-aguardar-pagamento="' . e(url('/conta/pedidos/' . $pd['numero'] . '/estado')) . '"' : '' ?>>
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="<?= url('/conta/pedidos') ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Os meus pedidos</a>
                    <h1 class="mt-1 font-display text-2xl sm:text-3xl font-extrabold num"><?= e($pd['numero']) ?></h1>
                    <p class="text-sm text-suave">Feito a <?= e(strftime_pt($pd['criado_em'], true)) ?>, às <?= date('H:i', strtotime($pd['criado_em'])) ?></p>
                </div>
                <?php require __DIR__ . '/_estado.php'; ?>
            </header>

            <!-- ===== Pagamento ===== -->
            <?php if ($pgPendente || $pgFalhou): ?>
            <section id="pagamento" class="cartao overflow-hidden scroll-mt-40" aria-labelledby="t-pag">
                <div class="flex items-center justify-between gap-3 border-b border-linha bg-aviso/10 px-5 py-3">
                    <h2 id="t-pag" class="font-display text-lg font-bold">
                        <?= $pgFalhou ? 'O pagamento não foi concluído' : 'Falta pagar ' . kz($pd['total']) ?>
                    </h2>
                    <?php if ($pgPendente && $pg['expira_em']): ?>
                    <p class="text-sm font-semibold text-aviso" data-contagem="<?= e(date('c', strtotime($pg['expira_em']))) ?>">
                        Expira em <span class="num" data-contagem-texto>--:--</span>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="p-5 sm:p-6">
                <?php if ($pgPendente && $tipo === 'referencia'): ?>
                    <p class="text-sm text-suave">Pague em qualquer ATM Multicaixa, no Multicaixa Express (Pagamentos &gt; Por referência) ou no internet banking do seu banco.</p>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                        <?php foreach ([['Entidade', $pg['entidade']], ['Referência', Pagamentos::referenciaFormatada($pg['referencia'])], ['Montante', strip_tags(kz($pg['valor']))]] as [$r, $v]): ?>
                        <div class="rounded-xl bg-elevado p-4">
                            <dt class="text-xs font-medium text-suave"><?= $r ?></dt>
                            <dd class="mt-1 flex items-center justify-between gap-2">
                                <span class="font-display text-xl sm:text-2xl font-extrabold num"><?= e($v) ?></span>
                                <button type="button" class="btn-icone h-8 w-8 shrink-0" data-copiar="<?= e(preg_replace('/[^\d]/', '', $v)) ?>" aria-label="Copiar <?= strtolower($r) ?>">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg>
                                </button>
                            </dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                    <p class="mt-4 text-sm text-suave">A confirmação é automática, normalmente em poucos minutos. Esta página actualiza-se sozinha.</p>

                <?php elseif ($pgPendente && $tipo === 'push'): ?>
                    <div class="flex items-start gap-4">
                        <span class="relative mt-1 flex h-3 w-3 shrink-0"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-marca opacity-60"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-marca"></span></span>
                        <div>
                            <p class="font-semibold">Enviámos um pedido de pagamento para <span class="num"><?= e(telefoneFormatado($pg['telefone'])) ?></span></p>
                            <p class="mt-1 text-sm text-suave">Abra a app <?= e(Pagamentos::nome($pg['metodo'])) ?>, confirme o valor de <?= kz($pg['valor']) ?> e introduza o PIN. Esta página actualiza-se sozinha assim que o pagamento for aceite.</p>
                        </div>
                    </div>

                <?php elseif ($pgPendente && $tipo === 'manual'): ?>
                    <p class="text-sm text-suave">Transfira o valor exacto para a conta abaixo e envie o comprovativo. Indique <strong class="text-tinta num"><?= e($pd['numero']) ?></strong> no descritivo da transferência.</p>
                    <dl class="mt-4 divide-y divide-linha rounded-xl bg-elevado text-sm">
                        <?php foreach ([['Titular', $banco['titular'], false], ['Banco', $banco['banco'], false], ['IBAN', $banco['iban'], true], ['Montante', strip_tags(kz($pg['valor'])), false], ['Descritivo', $pd['numero'], true]] as [$r, $v, $copiar]): ?>
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="text-suave"><?= $r ?></dt>
                            <dd class="flex items-center gap-1 font-semibold num text-right"><?= e($v) ?>
                                <?php if ($copiar): ?><button type="button" class="btn-icone h-8 w-8" data-copiar="<?= e(str_replace(' ', '', $v)) ?>" aria-label="Copiar <?= strtolower($r) ?>"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg></button><?php endif; ?>
                            </dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                    <?php if ($pg['comprovativo']): ?>
                        <p class="mt-4 flex items-center gap-2 rounded-xl bg-sucesso/10 p-3 text-sm font-medium text-sucesso"><?= icone('check', 'h-5 w-5') ?> Comprovativo recebido. Confirmamos em até 24 horas úteis.</p>
                    <?php else: ?>
                    <form action="<?= url('/conta/pedidos/' . $pd['numero'] . '/comprovativo') ?>" method="post" enctype="multipart/form-data" class="mt-4 flex flex-col sm:flex-row gap-2">
                        <?= \Core\Csrf::campo() ?>
                        <label for="comprovativo" class="sr-only">Comprovativo (PDF, JPG ou PNG, até 5 MB)</label>
                        <input id="comprovativo" type="file" name="comprovativo" required accept="application/pdf,image/jpeg,image/png,image/webp"
                               class="campo h-auto py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-tinta file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-superficie">
                        <button class="btn-primario shrink-0">Enviar comprovativo</button>
                    </form>
                    <p class="mt-2 text-xs text-suave">PDF, JPG ou PNG, até 5 MB. Uma fotografia legível do talão também serve.</p>
                    <?php endif; ?>

                <?php elseif ($pgPendente && $tipo === 'redireccao'): ?>
                    <p class="text-sm text-suave">Vai ser encaminhado para a página segura do processador de cartões. A DIOTEKNO não vê nem guarda os dados do seu cartão.</p>
                    <?php if ($simulado): ?>
                    <form action="<?= url('/conta/pedidos/' . $pd['numero'] . '/simular') ?>" method="post" class="mt-4"><?= \Core\Csrf::campo() ?><button class="btn-primario"><?= icone('cartao') ?> Pagar <?= kz($pg['valor']) ?> com cartão</button></form>
                    <?php else: ?>
                    <a href="<?= e($pg['resposta_gateway'] ?: '#') ?>" class="btn-primario mt-4"><?= icone('cartao') ?> Pagar <?= kz($pg['valor']) ?> com cartão</a>
                    <?php endif; ?>

                <?php elseif ($pgFalhou): ?>
                    <p class="text-sm text-suave">O prazo terminou ou o pagamento foi recusado. Os produtos continuam reservados — escolha como quer pagar e tente de novo.</p>
                <?php endif; ?>

                <?php if ($pgFalhou || ($pgPendente && $tipo === 'push')): ?>
                    <details class="mt-5 rounded-xl border border-linha" <?= $pgFalhou ? 'open' : '' ?>>
                        <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold [&::-webkit-details-marker]:hidden"><?= $pgFalhou ? 'Tentar de novo' : 'Não recebeu o pedido? Enviar de novo ou mudar a forma de pagamento' ?></summary>
                        <form action="<?= url('/conta/pedidos/' . $pd['numero'] . '/pagar') ?>" method="post" class="space-y-3 border-t border-linha p-4">
                            <?= \Core\Csrf::campo() ?>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <?php foreach ($metodos as $m): ?>
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-linha px-3 py-2.5 text-sm has-[:checked]:border-marca">
                                    <input type="radio" name="metodo_pagamento" value="<?= e($m['codigo']) ?>" <?= $m['codigo'] === $pg['metodo'] ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]" data-metodo-pagamento data-pede-telefone="<?= $m['pede_telefone'] ? '1' : '0' ?>">
                                    <?= e($m['nome']) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div data-campo-telefone <?= Pagamentos::METODOS[$pg['metodo']]['pede_telefone'] ? '' : 'hidden' ?>>
                                <label for="tel-repetir" class="rotulo">Número que vai pagar</label>
                                <input id="tel-repetir" name="telefone_pagamento" type="tel" inputmode="tel" value="<?= e(telefoneFormatado($pg['telefone'] ?: $cliente['telefone'])) ?>" class="campo max-w-xs num">
                            </div>
                            <button class="btn-primario">Continuar</button>
                        </form>
                    </details>
                <?php endif; ?>

                <?php if ($simulado && $pgPendente && $tipo !== 'redireccao'): ?>
                    <form action="<?= url('/conta/pedidos/' . $pd['numero'] . '/simular') ?>" method="post" class="mt-5 rounded-xl border border-dashed border-aviso/50 p-3 text-sm">
                        <?= \Core\Csrf::campo() ?>
                        <span class="text-aviso font-semibold">Modo de desenvolvimento:</span>
                        <button class="ml-2 font-semibold underline">Simular pagamento confirmado</button>
                    </form>
                <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- ===== Rastreamento ===== -->
            <section class="cartao p-5 sm:p-6" aria-labelledby="t-rastreio">
                <h2 id="t-rastreio" class="font-display text-lg font-bold">Acompanhamento</h2>
                <?php if ($cancelado): ?>
                    <p class="mt-3 rounded-xl bg-elevado p-4 text-sm"><?= $pd['estado'] === 'reembolsado' ? 'Este pedido foi reembolsado.' : 'Este pedido foi cancelado e os produtos voltaram ao stock.' ?></p>
                <?php else: ?>
                <ol class="mt-5 grid gap-4 sm:grid-cols-5 sm:gap-2">
                    <?php foreach (Pedido::ETAPAS as $chaveEtapa => $nomeEtapa):
                        $i = array_search($chaveEtapa, $ordemEtapas, true);
                        $feito = $indiceActual !== false && $i <= $indiceActual;
                        $actual = $i === $indiceActual;
                    ?>
                    <li class="relative flex sm:flex-col gap-3 sm:gap-2" <?= $actual ? 'aria-current="step"' : '' ?>>
                        <?php if ($i < count($ordemEtapas) - 1): ?>
                        <span class="absolute left-[13px] top-7 bottom-[-1rem] w-0.5 sm:left-7 sm:right-[-0.5rem] sm:top-[13px] sm:bottom-auto sm:h-0.5 sm:w-auto <?= $indiceActual !== false && $i < $indiceActual ? 'bg-marca' : 'bg-linha' ?>" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="relative z-10 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full <?= $feito ? 'bg-marca text-white dark:text-grafite' : 'border-2 border-linha bg-superficie text-suave' ?> <?= $actual ? 'ring-4 ring-marca/20' : '' ?>">
                            <?= $feito ? icone('check', 'h-4 w-4') : '' ?>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold <?= $feito ? '' : 'text-suave' ?>"><?= e($nomeEtapa) ?></span>
                            <?php if (isset($datasEtapa[$chaveEtapa])): ?><span class="block text-xs text-suave num"><?= date('d/m, H:i', strtotime($datasEtapa[$chaveEtapa])) ?></span><?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php if ($pd['codigo_rastreio']): ?>
                <p class="mt-5 text-sm">Código de rastreamento: <strong class="num"><?= e($pd['codigo_rastreio']) ?></strong></p>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($historico): ?>
                <details class="mt-5 border-t border-linha pt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-marca">Ver histórico completo</summary>
                    <ul class="mt-3 space-y-2 text-sm">
                        <?php foreach (array_reverse($historico) as $h): ?>
                        <li class="flex gap-3"><span class="w-24 shrink-0 text-suave num"><?= date('d/m H:i', strtotime($h['criado_em'])) ?></span><span><?= e($h['nota'] ?: (Pedido::ESTADOS[$h['estado']][0] ?? $h['estado'])) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
                <?php endif; ?>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                <!-- Artigos e totais -->
                <section class="cartao" aria-labelledby="t-artigos">
                    <h2 id="t-artigos" class="border-b border-linha px-5 py-4 font-display text-lg font-bold">Artigos</h2>
                    <ul class="divide-y divide-linha">
                        <?php foreach ($itens as $it): ?>
                        <li class="flex gap-3 px-5 py-3">
                            <img src="<?= imagemProduto($it + ['categoria_slug' => $it['categoria_slug'] ?? 'acessorios']) ?>" alt="" class="h-14 w-14 shrink-0 rounded-xl bg-white object-contain p-1.5" width="56" height="56">
                            <div class="min-w-0 flex-1">
                                <?php if ($it['slug']): ?><a href="<?= url('/produto/' . $it['slug']) ?>" class="line-clamp-2 text-sm font-semibold hover:text-marca"><?= e($it['nome']) ?></a>
                                <?php else: ?><p class="line-clamp-2 text-sm font-semibold"><?= e($it['nome']) ?></p><?php endif; ?>
                                <p class="text-xs text-suave num"><?= (int) $it['quantidade'] ?> × <?= kz($it['preco_unitario']) ?></p>
                                <?php if ($pd['estado'] === 'entregue' && $it['slug']): ?><a href="<?= url('/produto/' . $it['slug'] . '#avaliar') ?>" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-marca hover:underline"><?= icone('estrela', 'h-3.5 w-3.5') ?> Avaliar</a><?php endif; ?>
                            </div>
                            <p class="text-sm font-semibold num"><?= kz($it['total']) ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="space-y-1.5 border-t border-linha px-5 py-4 text-sm">
                        <div class="flex justify-between"><dt class="text-suave">Subtotal</dt><dd class="num"><?= kz($pd['subtotal']) ?></dd></div>
                        <?php if ($pd['desconto'] > 0): ?><div class="flex justify-between text-sucesso"><dt>Desconto</dt><dd class="num">−<?= kz($pd['desconto']) ?></dd></div><?php endif; ?>
                        <?php if ($pd['desconto_pontos'] > 0): ?><div class="flex justify-between text-sucesso"><dt><?= plural($pd['pontos_usados'], 'ponto usado', 'pontos usados') ?></dt><dd class="num">−<?= kz($pd['desconto_pontos']) ?></dd></div><?php endif; ?>
                        <div class="flex justify-between"><dt class="text-suave">Entrega</dt><dd class="num"><?= $pd['portes'] > 0 ? kz($pd['portes']) : 'Grátis' ?></dd></div>
                        <div class="flex items-baseline justify-between pt-2"><dt class="font-semibold">Total</dt><dd class="preco text-2xl"><?= kz($pd['total']) ?></dd></div>
                        <p class="text-xs text-suave">Inclui <?= kz($pd['iva']) ?> de IVA.<?= $pd['nif_factura'] ? ' Factura com NIF ' . e($pd['nif_factura']) . '.' : '' ?></p>
                        <?php if ($pd['pontos_ganhos'] > 0 && !$cancelado): ?><p class="text-xs font-semibold text-marca">Ganhou <?= plural($pd['pontos_ganhos'], 'ponto', 'pontos') ?> com este pedido.</p><?php endif; ?>
                    </dl>
                </section>

                <div class="space-y-6">
                    <section class="cartao p-5" aria-labelledby="t-entrega">
                        <h2 id="t-entrega" class="font-display text-lg font-bold"><?= $pd['metodo_entrega'] === 'levantamento' ? 'Levantamento' : 'Entrega' ?></h2>
                        <p class="mt-1 text-sm font-medium text-marca"><?= e($pd['prazo_entrega'] ?? '') ?></p>
                        <div class="mt-3 text-sm leading-6">
                            <p class="font-semibold"><?= e($pd['entrega_nome']) ?> <span class="font-normal text-suave num"><?= e(telefoneFormatado($pd['entrega_telefone'])) ?></span></p>
                            <?php if ($pd['metodo_entrega'] === 'levantamento'): ?>
                                <p><?= e($pd['entrega_rua']) ?></p>
                            <?php else: ?>
                                <?php if ($pd['entrega_rua']): ?><p><?= e($pd['entrega_rua']) ?></p><?php endif; ?>
                                <p><?= e($pd['entrega_bairro']) ?>, <?= e($pd['entrega_municipio']) ?>, <?= e($pd['entrega_provincia']) ?></p>
                                <?php if ($pd['entrega_referencia']): ?><p class="text-suave">Referência: <?= e($pd['entrega_referencia']) ?></p><?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($pd['notas']): ?><p class="mt-3 border-t border-linha pt-3 text-sm text-suave">Observações: <?= e($pd['notas']) ?></p><?php endif; ?>
                    </section>

                    <section class="cartao p-5 space-y-2">
                        <a href="<?= e(linkWhatsapp('Olá! Tenho uma questão sobre o pedido ' . $pd['numero'] . '.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp w-full"><?= icone('whatsapp') ?> Ajuda com este pedido</a>
                        <?php if ($pendente): ?>
                        <form action="<?= url('/conta/pedidos/' . $pd['numero'] . '/cancelar') ?>" method="post" data-confirmar="Cancelar o pedido <?= e($pd['numero']) ?>? Os produtos deixam de estar reservados.">
                            <?= \Core\Csrf::campo() ?>
                            <button class="btn-fantasma w-full text-suave hover:text-coral">Cancelar pedido</button>
                        </form>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
