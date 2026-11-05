<?php
/** @var ?array $c @var array $produtos @var int $destinatarios */
$a = \Core\Flash::antigo();
$v = fn ($k, $pad = '') => e((string) ($a[$k] ?? $c[$k] ?? $pad));
$editavel = !$c || $c['estado'] === 'rascunho';
$escolhidos = array_map('intval', (array) ($a['produtos_ids'] ?? explode(',', (string) ($c['produtos_ids'] ?? ''))));
$fonte = $a['fonte'] ?? $c['fonte'] ?? 'promocoes';
?>
<a href="<?= url('/admin/newsletter') ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Newsletter</a>
<?php
$tituloAdmin = $c ? $c['assunto'] : 'Nova campanha';
$accoesAdmin = $c ? '<form action="' . url('/admin/newsletter/' . $c['id'] . '/duplicar') . '" method="post">' . \Core\Csrf::campo() . '<button class="btn-secundario">Duplicar</button></form>' : '';
require dirname(__DIR__) . '/_titulo.php';
?>
<div class="grid gap-6 xl:grid-cols-[1fr_520px]">
    <div class="space-y-6 min-w-0">
        <?php if ($c && $c['estado'] !== 'rascunho'): ?>
        <section class="cartao p-5">
            <h2 class="font-display text-lg font-bold"><?= $c['estado'] === 'enviada' ? 'Enviada' : 'Em envio' ?></h2>
            <?php $pct = $c['total'] ? round(($c['enviados'] + $c['falhados']) / $c['total'] * 100) : 0; ?>
            <div class="mt-3 h-2 rounded-full bg-elevado"><div class="h-full rounded-full bg-sucesso" style="width: <?= $pct ?>%"></div></div>
            <p class="mt-2 text-sm text-suave num"><?= (int) $c['enviados'] ?> enviados de <?= (int) $c['total'] ?><?= $c['falhados'] ? ', ' . (int) $c['falhados'] . ' não enviados' : '' ?><?= $c['enviada_em'] ? '. Concluída a ' . date('d/m/Y H:i', strtotime($c['enviada_em'])) : '' ?>.</p>
        </section>
        <?php endif; ?>

        <form action="<?= url('/admin/newsletter/guardar') ?>" method="post" class="cartao p-5 space-y-4" novalidate>
            <?= \Core\Csrf::campo() ?>
            <?php if ($c): ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><?php endif; ?>
            <fieldset class="space-y-4" <?= $editavel ? '' : 'disabled' ?>>
                <div>
                    <label for="assunto" class="rotulo">Assunto</label>
                    <input id="assunto" name="assunto" maxlength="150" required value="<?= $v('assunto') ?>" placeholder="Ex.: Semana Gaming: PS5 com 50 000 Kz de desconto" class="campo" <?= ariaErro('assunto') ?> data-contador data-ideal="50">
                    <?= erroCampo('assunto') ?>
                </div>
                <div>
                    <label for="pre" class="rotulo">Texto de pré-visualização <span class="font-normal text-suave">(aparece a seguir ao assunto na caixa de entrada)</span></label>
                    <input id="pre" name="pre_cabecalho" maxlength="150" value="<?= $v('pre_cabecalho') ?>" class="campo" data-contador data-ideal="90">
                </div>
                <div>
                    <label for="titulo" class="rotulo">Título dentro do email</label>
                    <input id="titulo" name="titulo" maxlength="150" required value="<?= $v('titulo') ?>" class="campo" <?= ariaErro('titulo') ?>>
                    <?= erroCampo('titulo') ?>
                </div>
                <div>
                    <label for="texto" class="rotulo">Texto <span class="font-normal text-suave">(linha em branco separa parágrafos)</span></label>
                    <textarea id="texto" name="texto" rows="5" maxlength="5000" class="campo"><?= $v('texto') ?></textarea>
                </div>
                <div>
                    <label for="fonte" class="rotulo">Produtos no email</label>
                    <select id="fonte" name="fonte" class="campo" data-fonte-produtos>
                        <?php foreach (['promocoes' => 'Promoções do momento (preços actualizados no envio)', 'novidades' => 'Novidades', 'mais_vendidos' => 'Mais vendidos', 'escolhidos' => 'Escolher produtos', 'nenhum' => 'Sem produtos'] as $k => $t): ?>
                        <option value="<?= $k ?>" <?= $fonte === $k ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div data-produtos-escolhidos <?= $fonte === 'escolhidos' ? '' : 'hidden' ?>>
                    <label for="pids" class="rotulo">Produtos (até 6; Ctrl/Cmd para escolher vários)</label>
                    <select id="pids" name="produtos_ids[]" multiple size="8" class="campo h-auto py-2" <?= ariaErro('produtos_ids') ?>>
                        <?php foreach ($produtos as $p): ?><option value="<?= $p['id'] ?>" <?= in_array((int) $p['id'], $escolhidos, true) ? 'selected' : '' ?>><?= e($p['nome']) ?> (<?= e($p['sku']) ?>)</option><?php endforeach; ?>
                    </select>
                    <?= erroCampo('produtos_ids') ?>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="bt" class="rotulo">Texto do botão <span class="font-normal text-suave">(opcional)</span></label><input id="bt" name="botao_texto" maxlength="60" value="<?= $v('botao_texto', 'Ver todas as promoções') ?>" class="campo"></div>
                    <div><label for="bl" class="rotulo">Ligação do botão</label><input id="bl" name="botao_link" maxlength="255" value="<?= $v('botao_link', '/promocoes') ?>" class="campo" <?= ariaErro('botao_link') ?>><?= erroCampo('botao_link') ?></div>
                </div>
                <?php if ($editavel): ?><button class="btn-primario">Guardar e pré-visualizar</button><?php endif; ?>
            </fieldset>
        </form>

        <?php if ($c && $editavel): ?>
        <section class="cartao p-5 space-y-4">
            <h2 class="font-display text-lg font-bold">Enviar</h2>
            <form action="<?= url('/admin/newsletter/' . $c['id'] . '/teste') ?>" method="post"><?= \Core\Csrf::campo() ?>
                <button class="btn-secundario">Enviar teste para <?= e(\Core\AdminAuth::admin()['email']) ?></button>
            </form>
            <form action="<?= url('/admin/newsletter/' . $c['id'] . '/enviar') ?>" method="post" class="space-y-3 border-t border-linha pt-4" data-confirmar="Enviar a campanha para <?= $destinatarios ?> pessoas? Depois de começar não é possível parar.">
                <?= \Core\Csrf::campo() ?>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmo" value="1" required class="mt-0.5 h-4 w-4 accent-[rgb(var(--marca))]"> Revi a pré-visualização e o email de teste.</label>
                <button class="btn-primario" <?= $destinatarios ? '' : 'disabled' ?>>Enviar para <?= plural($destinatarios, 'pessoa', 'pessoas') ?></button>
            </form>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($c): ?>
    <section class="xl:sticky xl:top-24 xl:self-start">
        <h2 class="mb-2 text-sm font-semibold text-suave">Pré-visualização</h2>
        <iframe src="<?= url('/admin/newsletter/' . $c['id'] . '/pre-visualizar') ?>" title="Pré-visualização do email" class="h-[720px] w-full rounded-2xl border border-linha bg-white" loading="lazy"></iframe>
    </section>
    <?php endif; ?>
</div>
