<?php /** @var array $clientes @var array $pg */ $tituloAdmin = 'Clientes'; require dirname(__DIR__) . '/_titulo.php'; ?>
<form method="get" class="mb-4 flex flex-wrap gap-2">
    <label for="q" class="sr-only">Procurar</label>
    <input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Nome, email, telefone ou NIF" class="campo h-10 max-w-sm">
    <label for="ordem" class="sr-only">Ordenar</label>
    <select id="ordem" name="ordem" class="campo h-10 w-auto" onchange="this.form.submit()">
        <?php foreach (['' => 'Mais recentes', 'gasto' => 'Maior valor gasto', 'pedidos' => 'Mais pedidos'] as $k => $t): ?><option value="<?= $k ?>" <?= ($_GET['ordem'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
    </select>
    <button class="btn-secundario h-10">Procurar</button>
</form>
<div class="cartao overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-elevado text-left text-xs text-suave"><tr>
        <th class="px-4 py-3 font-semibold">Cliente</th><th class="px-4 py-3 font-semibold hidden md:table-cell">Telefone</th>
        <th class="px-4 py-3 font-semibold text-right">Pedidos</th><th class="px-4 py-3 font-semibold text-right">Valor gasto</th><th class="px-4 py-3 font-semibold hidden lg:table-cell">Registo</th>
    </tr></thead>
    <tbody class="divide-y divide-linha">
        <?php foreach ($clientes as $c): ?>
        <tr class="relative hover:bg-elevado/50">
            <td class="px-4 py-3"><a href="<?= url('/admin/clientes/' . $c['id']) ?>" class="font-semibold hover:text-marca after:absolute after:inset-0"><?= e($c['nome']) ?></a>
                <?php if ($c['estado'] === 'bloqueado'): ?><span class="ml-1 rounded bg-coral-suave px-1 text-xs font-semibold text-coral">Bloqueado</span><?php endif; ?>
                <p class="text-xs text-suave"><?= e($c['email']) ?></p></td>
            <td class="px-4 py-3 text-suave num hidden md:table-cell"><?= e(telefoneFormatado($c['telefone'])) ?></td>
            <td class="px-4 py-3 text-right num"><?= (int) $c['pedidos'] ?></td>
            <td class="px-4 py-3 text-right font-semibold num"><?= kz($c['gasto']) ?></td>
            <td class="px-4 py-3 text-suave num hidden lg:table-cell"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$clientes): ?><tr><td colspan="5" class="px-4 py-10 text-center text-suave">Nenhum cliente encontrado.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<?php require dirname(__DIR__) . '/_paginacao.php'; ?>
