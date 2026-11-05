<?php
/** @var array $enderecos @var ?array $editar @var bool $novo @var array $provincias @var int $maximo @var array $cliente */
$antigo = \Core\Flash::antigo();
$f = fn ($c, $padrao = '') => e((string) ($antigo[$c] ?? $editar[$c] ?? $padrao));
?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>
        <div class="min-w-0 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-display text-3xl font-extrabold">Moradas</h1>
                <?php if (!$novo && count($enderecos) < $maximo): ?>
                <a href="<?= url('/conta/enderecos?novo=1') ?>" class="btn-primario"><?= icone('mais', 'h-4 w-4') ?> Nova morada</a>
                <?php endif; ?>
            </div>

            <?php if ($novo): ?>
            <form action="<?= url('/conta/enderecos/guardar') ?>" method="post" class="cartao p-5 sm:p-6 space-y-4 max-w-2xl" novalidate>
                <?= \Core\Csrf::campo() ?>
                <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $editar['id'] ?>"><?php endif; ?>
                <?php if (!empty($_GET['voltar'])): ?><input type="hidden" name="voltar" value="<?= e(caminhoSeguro($_GET['voltar'], '/conta/enderecos')) ?>"><?php endif; ?>
                <h2 class="font-display text-lg font-bold"><?= $editar ? 'Editar morada' : 'Nova morada' ?></h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="e-nome" class="rotulo">Quem recebe</label>
                        <input id="e-nome" name="nome_destinatario" required autocomplete="name" value="<?= $f('nome_destinatario', $cliente['nome']) ?>" class="campo" <?= ariaErro('nome_destinatario') ?>>
                        <?= erroCampo('nome_destinatario') ?>
                    </div>
                    <div>
                        <label for="e-tel" class="rotulo">Telemóvel de contacto</label>
                        <input id="e-tel" name="telefone" type="tel" required inputmode="tel" autocomplete="tel-national"
                               value="<?= e((string) ($antigo['telefone'] ?? telefoneFormatado($editar['telefone'] ?? $cliente['telefone']))) ?>" class="campo num" <?= ariaErro('telefone') ?>>
                        <?= erroCampo('telefone') ?>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="e-prov" class="rotulo">Província</label>
                        <select id="e-prov" name="provincia" required class="campo" <?= ariaErro('provincia') ?>>
                            <?php $provSel = $antigo['provincia'] ?? $editar['provincia'] ?? 'Luanda'; foreach ($provincias as $pv): ?>
                            <option <?= $pv === $provSel ? 'selected' : '' ?>><?= e($pv) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= erroCampo('provincia') ?>
                    </div>
                    <div>
                        <label for="e-mun" class="rotulo">Município</label>
                        <input id="e-mun" name="municipio" required list="municipios-luanda" placeholder="Ex.: Talatona" value="<?= $f('municipio') ?>" class="campo" <?= ariaErro('municipio') ?>>
                        <datalist id="municipios-luanda">
                            <?php foreach (['Belas', 'Cacuaco', 'Cazenga', 'Ingombota', 'Kilamba', 'Kilamba Kiaxi', 'Maianga', 'Rangel', 'Samba', 'Sambizanga', 'Talatona', 'Viana', 'Camama', 'Mussulo', 'Benfica', 'Mulenvos', 'Hoji-ya-Henda', 'Kikolo', 'Ngola Kiluanje', 'Neves Bendinha', 'Estalagem', 'Zango', 'Kalawenda', 'Cauelele'] as $mn): ?>
                            <option value="<?= $mn ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <?= erroCampo('municipio') ?>
                    </div>
                </div>
                <div>
                    <label for="e-bairro" class="rotulo">Bairro</label>
                    <input id="e-bairro" name="bairro" required placeholder="Ex.: Morro Bento" value="<?= $f('bairro') ?>" class="campo" <?= ariaErro('bairro') ?>>
                    <?= erroCampo('bairro') ?>
                </div>
                <div>
                    <label for="e-rua" class="rotulo">Rua, edifício, casa <span class="font-normal text-suave">(opcional)</span></label>
                    <input id="e-rua" name="rua" autocomplete="street-address" placeholder="Ex.: Rua 21, Edifício Girassol, 3.º andar" value="<?= $f('rua') ?>" class="campo" <?= ariaErro('rua') ?>>
                    <?= erroCampo('rua') ?>
                </div>
                <div>
                    <label for="e-ref" class="rotulo">Ponto de referência <span class="font-normal text-suave">(recomendado)</span></label>
                    <textarea id="e-ref" name="referencia" rows="2" maxlength="255" placeholder="Ex.: Em frente à farmácia Central, portão azul" class="campo"><?= $f('referencia') ?></textarea>
                    <p class="mt-1.5 text-xs text-suave">Ajuda o estafeta a encontrar a morada à primeira.</p>
                </div>
                <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                    <input type="checkbox" name="principal" value="1" <?= ($antigo['principal'] ?? $editar['principal'] ?? !$enderecos) ? 'checked' : '' ?> class="h-4 w-4 rounded accent-[rgb(var(--marca))]">
                    Usar como morada principal
                </label>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button class="btn-primario"><?= $editar ? 'Guardar alterações' : 'Guardar morada' ?></button>
                    <a href="<?= url('/conta/enderecos') ?>" class="btn-fantasma">Cancelar</a>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($enderecos): ?>
            <ul class="grid gap-4 md:grid-cols-2">
                <?php foreach ($enderecos as $end): ?>
                <li class="cartao p-5 flex flex-col <?= $end['principal'] ? 'border-marca' : '' ?>">
                    <?php if ($end['principal']): ?><span class="selo-novo mb-3 self-start">Morada principal</span><?php endif; ?>
                    <div class="flex-1"><?php require __DIR__ . '/_endereco.php'; ?></div>
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-linha pt-3 text-sm">
                        <a href="<?= url('/conta/enderecos?editar=' . (int) $end['id']) ?>" class="link">Editar</a>
                        <?php if (!$end['principal']): ?>
                        <form action="<?= url('/conta/enderecos/' . (int) $end['id'] . '/principal') ?>" method="post">
                            <?= \Core\Csrf::campo() ?><button class="font-semibold text-tinta hover:text-marca">Tornar principal</button>
                        </form>
                        <?php endif; ?>
                        <form action="<?= url('/conta/enderecos/' . (int) $end['id'] . '/remover') ?>" method="post" class="ml-auto" data-confirmar="Remover esta morada?">
                            <?= \Core\Csrf::campo() ?><button class="font-semibold text-suave hover:text-coral">Remover</button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php elseif (!$novo): ?>
            <div class="cartao p-8 text-center">
                <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-elevado text-suave"><?= icone('localizacao', 'h-7 w-7') ?></span>
                <p class="font-display text-lg font-bold">Ainda não tem moradas guardadas</p>
                <p class="mt-1 text-sm text-suave">Guarde a morada de casa ou do trabalho para não a escrever em cada compra.</p>
                <a href="<?= url('/conta/enderecos?novo=1') ?>" class="btn-primario mt-5">Adicionar morada</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
