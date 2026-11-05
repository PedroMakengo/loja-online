<?php
/** @var array $pedidos @var array $pg @var string $filtro @var array $contagens */
use App\Controllers\Admin\PedidosController;
use App\Servicos\Pagamentos;
$tituloAdmin = 'Pedidos'; require dirname(__DIR__) . '/_titulo.php';
?>
<nav class="faixa mb-4" aria-label="Filtrar por estado">
    <?php foreach (PedidosController::FILTROS as $chave => $rotulo):
        $n = $chave === '' ? array_sum(array_diff_key($contagens, ['transferencias' => 0])) : ($contagens[$chave] ?? 0);
        if ($chave === 'transferencias' && !$n) continue; ?>
    <a href="<?= e(urlActual(['estado' => $chave ?: null, 'pagina' => null])) ?>" class="chip <?= $filtro === $chave ? 'chip-activo' : '' ?> <?= $chave === 'transferencias' && $filtro !== $chave ? '!border-coral/40 !text-coral' : '' ?>">
        <?= $rotulo ?> <span class="text-xs opacity-70 num"><?= (int) $n ?></span>
    </a>
    <?php endforeach; ?>
</nav>

<form method="get" class="mb-4 flex flex-wrap gap-2">
    <?php if ($filtro): ?><input type="hidden" name="estado" value="<?= e($filtro) ?>"><?php endif; ?>
    <label for="q" class="sr-only">Procurar</label>
    <input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Nº do pedido, cliente, email ou telefone" class="campo h-10 max-w-sm">
    <label for="metodo" class="sr-only">Forma de pagamento</label>
    <select id="metodo" name="metodo" class="campo h-10 w-auto" onchange="this.form.submit()">
        <option value="">Todas as formas de pagamento</option>
        <?php foreach (Pagamentos::METODOS as $c => $m): ?><option value="<?= $c ?>" <?= ($_GET['metodo'] ?? '') === $c ? 'selected' : '' ?>><?= e($m['nome']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn-secundario h-10">Procurar</button>
</form>

<div class="cartao overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-elevado text-left text-xs text-suave">
                <tr>
                    <th class="px-4 py-3 font-semibold">Pedido</th>
                    <th class="px-4 py-3 font-semibold">Cliente</th>
                    <th class="px-4 py-3 font-semibold hidden lg:table-cell">Entrega</th>
                    <th class="px-4 py-3 font-semibold hidden md:table-cell">Pagamento</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3 font-semibold text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-linha">
                <?php foreach ($pedidos as $p): $estadoPedido = $p['estado'];
                    $horas = (time() - strtotime($p['criado_em'])) / 3600;
                    $atrasado = in_array($p['estado'], ['pago', 'em_preparacao'], true) && $horas > 24; ?>
                <tr class="relative hover:bg-elevado/50">
                    <td class="px-4 py-3">
                        <a href="<?= url('/admin/pedidos/' . $p['numero']) ?>" class="font-semibold num hover:text-marca after:absolute after:inset-0"><?= e($p['numero']) ?></a>
                        <p class="text-xs num <?= $atrasado ? 'font-semibold text-coral' : 'text-suave' ?>"><?= date('d/m H:i', strtotime($p['criado_em'])) ?><?= $atrasado ? ' (há ' . floor($horas) . ' h)' : '' ?></p>
                    </td>
                    <td class="px-4 py-3"><p class="font-medium"><?= e($p['cliente']) ?></p><p class="text-xs text-suave num"><?= e(telefoneFormatado($p['cliente_telefone'])) ?></p></td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <p><?= $p['metodo_entrega'] === 'levantamento' ? 'Levantamento' : e($p['entrega_municipio'] . ', ' . $p['entrega_provincia']) ?></p>
                        <?php if ($p['metodo_entrega'] === 'expressa'): ?><span class="selo-novo">Expressa</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <?= e(Pagamentos::nome($p['metodo_pagamento'])) ?>
                        <?php if ($p['comprovativo'] && $p['estado'] === 'pendente'): ?><p class="text-xs font-semibold text-coral">Comprovativo enviado</p><?php endif; ?>
                    </td>
                    <td class="px-4 py-3"><?php require RAIZ . '/app/Views/conta/_estado.php'; ?></td>
                    <td class="px-4 py-3 text-right"><p class="font-semibold num"><?= kz($p['total']) ?></p><p class="text-xs text-suave num"><?= (int) $p['artigos'] ?> art.</p></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$pedidos): ?><tr><td colspan="6" class="px-4 py-10 text-center text-suave">Nenhum pedido encontrado com estes filtros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/_paginacao.php'; ?>
