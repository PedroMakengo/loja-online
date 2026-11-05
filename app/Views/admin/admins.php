<?php /** @var array $admins @var array $registos */ use Core\AdminAuth; $tituloAdmin = 'Administradores'; require __DIR__ . '/_titulo.php'; ?>
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="space-y-6 min-w-0">
        <section class="cartao overflow-hidden"><div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-elevado text-left text-xs text-suave"><tr><th class="px-4 py-3 font-semibold">Nome</th><th class="px-4 py-3 font-semibold">Papel</th><th class="px-4 py-3 font-semibold hidden md:table-cell">Último acesso</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-linha">
                    <?php foreach ($admins as $ad): ?>
                    <tr class="<?= $ad['activo'] ? '' : 'opacity-50' ?>">
                        <td class="px-4 py-3"><p class="font-semibold"><?= e($ad['nome']) ?></p><p class="text-xs text-suave"><?= e($ad['email']) ?></p></td>
                        <td class="px-4 py-3"><?= e(AdminAuth::PAPEIS[$ad['papel']]) ?></td>
                        <td class="px-4 py-3 text-suave num hidden md:table-cell"><?= $ad['ultimo_login_em'] ? date('d/m/Y H:i', strtotime($ad['ultimo_login_em'])) : 'Nunca' ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ((int) $ad['id'] !== AdminAuth::id()): ?>
                            <form action="<?= url('/admin/administradores/' . $ad['id'] . '/alternar') ?>" method="post" data-confirmar="<?= $ad['activo'] ? 'Retirar o acesso a ' . e($ad['nome']) . '?' : 'Repor o acesso?' ?>">
                                <?= \Core\Csrf::campo() ?><button class="text-xs font-semibold <?= $ad['activo'] ? 'text-coral' : 'text-sucesso' ?>"><?= $ad['activo'] ? 'Retirar acesso' : 'Repor acesso' ?></button>
                            </form>
                            <?php else: ?><span class="text-xs text-suave">É você</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></section>

        <section class="cartao p-5">
            <h2 class="font-display text-lg font-bold">Actividade recente</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <?php foreach ($registos as $r): ?>
                <li class="flex gap-3"><span class="w-24 shrink-0 text-suave num"><?= date('d/m H:i', strtotime($r['criado_em'])) ?></span>
                    <span><strong><?= e($r['nome'] ?? 'Sistema') ?></strong> <?= e(str_replace('_', ' ', $r['accao'])) ?> <?= e($r['alvo'] ?? '') ?> <span class="num"><?= e($r['alvo_id'] ?? '') ?></span><?= $r['detalhe'] ? ' <span class="text-suave">— ' . e($r['detalhe']) . '</span>' : '' ?></span></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <form action="<?= url('/admin/administradores') ?>" method="post" class="cartao p-5 space-y-4 h-fit" novalidate>
        <?= \Core\Csrf::campo() ?>
        <h2 class="font-display text-lg font-bold">Novo administrador</h2>
        <div><label for="an" class="rotulo">Nome completo</label><input id="an" name="nome" required value="<?= antigo('nome') ?>" class="campo" <?= ariaErro('nome') ?>><?= erroCampo('nome') ?></div>
        <div><label for="ae" class="rotulo">Email</label><input id="ae" name="email" type="email" required value="<?= antigo('email') ?>" class="campo" <?= ariaErro('email') ?>><?= erroCampo('email') ?></div>
        <div><label for="ap" class="rotulo">Papel</label>
            <select id="ap" name="papel" class="campo"><?php foreach (AdminAuth::PAPEIS as $k => $t): ?><option value="<?= $k ?>" <?= antigo('papel', 'atendimento') === $k ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
            <p class="mt-1.5 text-xs text-suave">Atendimento: pedidos, clientes e mensagens. Stock: produtos e catálogo. Gestor: tudo menos administradores.</p>
        </div>
        <button class="btn-primario w-full">Criar e gerar senha temporária</button>
    </form>
</div>
