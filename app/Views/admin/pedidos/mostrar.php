<?php
/**
 * @var array $pedido @var array $cliente @var array $itens @var array $historico
 * @var array $pagamentos @var array $registos @var array $proximos
 */
use App\Models\Pedido;
use App\Servicos\Pagamentos;
use Core\AdminAuth;

$pd = $pedido; $pg = $pagamentos[0] ?? null; $estadoPedido = $pd['estado'];
$rotulosAccao = ['em_preparacao' => 'Iniciar preparação', 'enviado' => $pd['metodo_entrega'] === 'levantamento' ? 'Marcar como pronto para levantar' : 'Marcar como enviado', 'entregue' => $pd['metodo_entrega'] === 'levantamento' ? 'Marcar como levantado' : 'Marcar como entregue'];
$precisaRastreio = $pd['metodo_entrega'] !== 'levantamento' && $pd['entrega_provincia'] !== 'Luanda';
$lucro = null;
?>
<a href="<?= url('/admin/pedidos') ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Pedidos</a>
<div class="mt-1 mb-6 flex flex-wrap items-center gap-3">
    <h1 class="font-display text-2xl sm:text-3xl font-extrabold num"><?= e($pd['numero']) ?></h1>
    <?php require RAIZ . '/app/Views/conta/_estado.php'; ?>
    <span class="text-sm text-suave"><?= date('d/m/Y H:i', strtotime($pd['criado_em'])) ?></span>
</div>

<div class="grid gap-6 xl:grid-cols-[1fr_380px]">
    <div class="space-y-6 min-w-0">
        <!-- Acção principal -->
        <?php if ($pd['estado'] === 'pendente' && $pg): ?>
        <section class="cartao p-5 border-aviso/40" aria-labelledby="t-pag">
            <h2 id="t-pag" class="font-display text-lg font-bold">Aguarda pagamento por <?= e(Pagamentos::nome($pg['metodo'])) ?></h2>
            <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                <div class="flex justify-between sm:block"><dt class="text-suave">Valor</dt><dd class="font-semibold num"><?= kz($pg['valor']) ?></dd></div>
                <div class="flex justify-between sm:block"><dt class="text-suave">Estado do pagamento</dt><dd class="font-semibold"><?= e(ucfirst($pg['estado'])) ?></dd></div>
                <?php if ($pg['referencia']): ?><div class="flex justify-between sm:block"><dt class="text-suave">Entidade / referência</dt><dd class="font-semibold num"><?= e($pg['entidade']) ?> / <?= e(Pagamentos::referenciaFormatada($pg['referencia'])) ?></dd></div><?php endif; ?>
                <?php if ($pg['telefone']): ?><div class="flex justify-between sm:block"><dt class="text-suave">Telefone</dt><dd class="font-semibold num"><?= e(telefoneFormatado($pg['telefone'])) ?></dd></div><?php endif; ?>
                <div class="flex justify-between sm:block"><dt class="text-suave">Expira</dt><dd class="font-semibold num"><?= $pg['expira_em'] ? date('d/m H:i', strtotime($pg['expira_em'])) : '—' ?></dd></div>
            </dl>

            <?php if ($pg['metodo'] === 'transferencia' && AdminAuth::pode('pagamentos')): ?>
                <?php if ($pg['comprovativo']): ?>
                <div class="mt-4 rounded-xl bg-elevado p-4">
                    <p class="text-sm font-semibold">Comprovativo enviado pelo cliente</p>
                    <a href="<?= url('/admin/comprovativos/' . $pg['comprovativo']) ?>" target="_blank" class="btn-secundario mt-2 h-10"><?= icone('email', 'h-4 w-4') ?> Abrir comprovativo</a>
                    <p class="mt-3 text-xs text-suave">Confirme no extracto do banco que entraram <strong class="num"><?= kz($pg['valor']) ?></strong> com o descritivo <strong class="num"><?= e($pd['numero']) ?></strong>. O comprovativo sozinho não prova o pagamento.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <form action="<?= url('/admin/pedidos/' . $pd['numero'] . '/confirmar-pagamento') ?>" method="post" class="space-y-2">
                            <?= \Core\Csrf::campo() ?>
                            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="verificado" value="1" required class="mt-0.5 h-4 w-4 accent-[rgb(var(--marca))]"> Verifiquei a entrada do valor no banco</label>
                            <button class="btn-primario w-full">Confirmar pagamento</button>
                        </form>
                        <form action="<?= url('/admin/pedidos/' . $pd['numero'] . '/rejeitar-comprovativo') ?>" method="post" class="space-y-2">
                            <?= \Core\Csrf::campo() ?>
                            <label for="motivo-rej" class="sr-only">Motivo</label>
                            <input id="motivo-rej" name="motivo" placeholder="Motivo (ex.: valor não encontrado)" class="campo h-10">
                            <button class="btn-secundario w-full">Pedir novo comprovativo</button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <p class="mt-4 text-sm text-suave">O cliente ainda não enviou o comprovativo.</p>
                <form action="<?= url('/admin/pedidos/' . $pd['numero'] . '/confirmar-pagamento') ?>" method="post" class="mt-3 flex flex-wrap items-center gap-3">
                    <?= \Core\Csrf::campo() ?>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="verificado" value="1" required class="h-4 w-4 accent-[rgb(var(--marca))]"> O valor já entrou no banco</label>
                    <button class="btn-secundario h-10">Confirmar sem comprovativo</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($proximos): ?>
        <section class="cartao p-5 border-marca/40" aria-labelledby="t-proximo">
            <h2 id="t-proximo" class="font-display text-lg font-bold">Próximo passo</h2>
            <?php foreach ($proximos as $prox): ?>
            <form action="<?= url('/admin/pedidos/' . $pd['numero'] . '/estado') ?>" method="post" class="mt-3 space-y-3">
                <?= \Core\Csrf::campo() ?>
                <input type="hidden" name="estado" value="<?= $prox ?>">
                <?php if ($prox === 'enviado' && $pd['metodo_entrega'] !== 'levantamento'): ?>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="rastreio" class="rotulo">Código de rastreamento <?= $precisaRastreio ? '' : '<span class="font-normal text-suave">(opcional em Luanda)</span>' ?></label>
                        <input id="rastreio" name="codigo_rastreio" <?= $precisaRastreio ? 'required' : '' ?> class="campo num" value="<?= e($pd['codigo_rastreio'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="nota-env" class="rotulo">Mensagem para o cliente <span class="font-normal text-suave">(opcional)</span></label>
                        <input id="nota-env" name="nota" maxlength="255" placeholder="Ex.: o estafeta liga antes de chegar" class="campo">
                    </div>
                </div>
                <?php endif; ?>
                <button class="btn-primario"><?= $rotulosAccao[$prox] ?></button>
                <?php if ($prox !== 'em_preparacao'): ?><span class="ml-2 text-xs text-suave">O cliente recebe um email.</span><?php endif; ?>
            </form>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <!-- Artigos -->
        <section class="cartao overflow-hidden" aria-labelledby="t-art">
            <h2 id="t-art" class="border-b border-linha px-5 py-4 font-display text-lg font-bold">Artigos</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-suave"><tr><th class="px-5 py-2 font-semibold">Produto</th><th class="px-5 py-2 font-semibold">SKU</th><th class="px-5 py-2 font-semibold text-right">Qtd.</th><th class="px-5 py-2 font-semibold text-right">Preço</th><th class="px-5 py-2 font-semibold text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-linha">
                        <?php foreach ($itens as $it): ?>
                        <tr>
                            <td class="px-5 py-3 font-medium"><?php if ($it['produto_id']): ?><a href="<?= url('/admin/produtos/' . $it['produto_id']) ?>" class="hover:text-marca"><?= e($it['nome']) ?></a><?php else: ?><?= e($it['nome']) ?><?php endif; ?></td>
                            <td class="px-5 py-3 text-suave num"><?= e($it['sku']) ?></td>
                            <td class="px-5 py-3 text-right num"><?= (int) $it['quantidade'] ?></td>
                            <td class="px-5 py-3 text-right num"><?= kz($it['preco_unitario']) ?></td>
                            <td class="px-5 py-3 text-right font-semibold num"><?= kz($it['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <dl class="border-t border-linha px-5 py-4 text-sm space-y-1 sm:ml-auto sm:max-w-xs">
                <div class="flex justify-between"><dt class="text-suave">Subtotal</dt><dd class="num"><?= kz($pd['subtotal']) ?></dd></div>
                <?php if ($pd['desconto'] > 0): ?><div class="flex justify-between"><dt class="text-suave">Desconto</dt><dd class="num">−<?= kz($pd['desconto']) ?></dd></div><?php endif; ?>
                <?php if ($pd['desconto_pontos'] > 0): ?><div class="flex justify-between"><dt class="text-suave"><?= plural($pd['pontos_usados'], 'ponto', 'pontos') ?></dt><dd class="num">−<?= kz($pd['desconto_pontos']) ?></dd></div><?php endif; ?>
                <div class="flex justify-between"><dt class="text-suave">Entrega (<?= e($pd['metodo_entrega']) ?>)</dt><dd class="num"><?= kz($pd['portes']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-suave">IVA incluído</dt><dd class="num"><?= kz($pd['iva']) ?></dd></div>
                <div class="flex justify-between pt-1 text-base"><dt class="font-semibold">Total</dt><dd class="preco"><?= kz($pd['total']) ?></dd></div>
            </dl>
        </section>

        <!-- Histórico e notas -->
        <section id="notas" class="cartao p-5 scroll-mt-24" aria-labelledby="t-hist">
            <h2 id="t-hist" class="font-display text-lg font-bold">Histórico</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <?php
                $linhas = [];
                foreach ($historico as $h) { $linhas[] = [$h['criado_em'], $h['nota'] ?: Pedido::ESTADOS[$h['estado']][0], 'Cliente vê', null]; }
                foreach ($registos as $r) { $linhas[] = [$r['criado_em'], ($r['accao'] === 'nota' ? 'Nota: ' : str_replace('_', ' ', ucfirst($r['accao'])) . ($r['detalhe'] ? ' — ' : '')) . ($r['detalhe'] ?? ''), 'Interno', $r['admin']]; }
                usort($linhas, fn ($a, $b) => strcmp($b[0], $a[0]));
                foreach ($linhas as [$quando, $texto, $tipo, $quem]): ?>
                <li class="flex gap-3">
                    <span class="w-24 shrink-0 text-suave num"><?= date('d/m H:i', strtotime($quando)) ?></span>
                    <span class="flex-1"><?= e($texto) ?><?= $quem ? ' <span class="text-suave">(' . e($quem) . ')</span>' : '' ?></span>
                    <span class="shrink-0 rounded-md px-1.5 text-[11px] font-semibold <?= $tipo === 'Interno' ? 'bg-elevado text-suave' : 'bg-marca-suave text-marca' ?>"><?= $tipo ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <form action="<?= url('/admin/pedidos/' . $pd['numero'] . '/nota') ?>" method="post" class="mt-4 flex gap-2">
                <?= \Core\Csrf::campo() ?>
                <label for="nota-int" class="sr-only">Nota interna</label>
                <input id="nota-int" name="nota" maxlength="500" placeholder="Nota interna (o cliente não vê)" class="campo h-10">
                <button class="btn-secundario h-10 shrink-0">Adicionar</button>
            </form>
        </section>
    </div>

    <!-- Coluna lateral -->
    <div class="space-y-6">
        <section class="cartao p-5" aria-labelledby="t-cli">
            <h2 id="t-cli" class="font-display text-lg font-bold">Cliente</h2>
            <p class="mt-2 font-semibold"><a href="<?= url('/admin/clientes/' . $cliente['id']) ?>" class="hover:text-marca"><?= e($cliente['nome']) ?></a></p>
            <p class="text-sm text-suave"><?= e($cliente['email']) ?></p>
            <p class="text-sm text-suave num"><?= e(telefoneFormatado($cliente['telefone'])) ?></p>
            <p class="mt-2 text-xs text-suave"><?= plural($cliente['compras'], 'compra paga', 'compras pagas') ?>. Cliente desde <?= date('m/Y', strtotime($cliente['criado_em'])) ?>.</p>
            <div class="mt-3 flex gap-2">
                <a href="<?= e('https://wa.me/' . preg_replace('/\D/', '', $cliente['telefone']) . '?text=' . rawurlencode('Olá ' . explode(' ', $cliente['nome'])[0] . ', é da DIOTEKNO sobre o pedido ' . $pd['numero'] . '.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp h-9 px-3 text-xs"><?= icone('whatsapp', 'h-4 w-4') ?> WhatsApp</a>
                <a href="tel:<?= e($cliente['telefone']) ?>" class="btn-secundario h-9 px-3 text-xs"><?= icone('telefone', 'h-4 w-4') ?> Ligar</a>
            </div>
        </section>

        <section class="cartao p-5" aria-labelledby="t-ent">
            <h2 id="t-ent" class="font-display text-lg font-bold"><?= $pd['metodo_entrega'] === 'levantamento' ? 'Levantamento na loja' : 'Entrega ' . ($pd['metodo_entrega'] === 'expressa' ? 'expressa' : 'ao domicílio') ?></h2>
            <div class="mt-2 text-sm leading-6">
                <p class="font-semibold"><?= e($pd['entrega_nome']) ?> <span class="font-normal text-suave num"><?= e(telefoneFormatado($pd['entrega_telefone'])) ?></span></p>
                <?php if ($pd['metodo_entrega'] !== 'levantamento'): ?>
                <?php if ($pd['entrega_rua']): ?><p><?= e($pd['entrega_rua']) ?></p><?php endif; ?>
                <p><?= e($pd['entrega_bairro']) ?>, <?= e($pd['entrega_municipio']) ?></p>
                <p><?= e($pd['entrega_provincia']) ?></p>
                <?php if ($pd['entrega_referencia']): ?><p class="mt-1 rounded-lg bg-elevado px-2 py-1">Referência: <?= e($pd['entrega_referencia']) ?></p><?php endif; ?>
                <?php endif; ?>
                <?php if ($pd['notas']): ?><p class="mt-2 rounded-lg bg-aviso/10 px-2 py-1 text-aviso">Observações: <?= e($pd['notas']) ?></p><?php endif; ?>
                <?php if ($pd['codigo_rastreio']): ?><p class="mt-2">Rastreio: <strong class="num"><?= e($pd['codigo_rastreio']) ?></strong></p><?php endif; ?>
            </div>
            <button type="button" class="btn-secundario mt-3 h-9 w-full text-xs" onclick="window.print()">Imprimir guia de preparação</button>
        </section>

        <section class="cartao p-5 text-sm" aria-labelledby="t-fact">
            <h2 id="t-fact" class="font-display text-lg font-bold">Factura</h2>
            <p class="mt-2"><?= $pd['nif_factura'] ? 'NIF ' . e($pd['nif_factura']) : 'Consumidor final' ?></p>
            <p class="mt-1 text-xs text-suave">A emissão da factura certificada (AGT) é feita no software de facturação da loja.</p>
        </section>

        <?php if (in_array($pd['estado'], ['pendente', 'pago', 'em_preparacao'], true)): ?>
        <details class="cartao p-5">
            <summary class="cursor-pointer text-sm font-semibold text-coral">Cancelar pedido</summary>
            <form action="<?= url('/admin/pedidos/' . $pd['numero'] . '/cancelar') ?>" method="post" class="mt-3 space-y-2" data-confirmar="Cancelar o pedido <?= e($pd['numero']) ?>? O stock volta a ficar disponível.">
                <?= \Core\Csrf::campo() ?>
                <label for="motivo" class="rotulo">Motivo (o cliente vê)</label>
                <input id="motivo" name="motivo" required maxlength="200" placeholder="Ex.: a pedido do cliente" class="campo">
                <?php if ($pd['estado'] !== 'pendente'): ?><p class="text-xs text-aviso">Este pedido já foi pago: fica como reembolsado e terá de devolver o valor ao cliente.</p><?php endif; ?>
                <button class="btn w-full bg-coral text-white hover:bg-coral/90">Cancelar pedido</button>
            </form>
        </details>
        <?php endif; ?>
    </div>
</div>
