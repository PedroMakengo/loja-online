<?php
/** @var array $cfg @var string $entidade @var ?array $registo @var array $opcoesTabela */
$a = \Core\Flash::antigo();
$val = function (string $campo, array $f) use ($a, $registo) {
    if (array_key_exists($campo, $a)) return $a[$campo];
    if ($registo) return $registo[$campo];
    return $f['padrao'] ?? null;
};
?>
<a href="<?= url("/admin/c/$entidade") ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> <?= e($cfg['titulo']) ?></a>
<?php $tituloAdmin = ($registo ? 'Editar ' : 'Novo ') . $cfg['singular']; require dirname(__DIR__) . '/_titulo.php'; ?>
<form action="<?= url("/admin/c/$entidade/guardar") ?>" method="post" enctype="multipart/form-data" class="cartao p-5 sm:p-6 max-w-2xl space-y-4" novalidate>
    <?= \Core\Csrf::campo() ?>
    <input type="hidden" name="id" value="<?= (int) ($registo['id'] ?? 0) ?>">
    <?php foreach ($cfg['campos'] as $campo => $f): $v = $val($campo, $f); $id = 'c-' . $campo; ?>
    <?php if (!empty($f['so_leitura'])): if ($registo): ?>
        <p class="text-sm"><span class="text-suave"><?= e($f['rotulo']) ?>:</span> <strong class="num"><?= e((string) $v) ?></strong></p>
    <?php endif; continue; endif; ?>
    <div>
        <?php if ($f['tipo'] === 'booleano'): ?>
            <label class="flex items-center gap-2.5 text-sm font-medium cursor-pointer"><input type="checkbox" name="<?= $campo ?>" value="1" <?= $v ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]"> <?= e($f['rotulo']) ?></label>
        <?php else: ?>
            <label for="<?= $id ?>" class="rotulo"><?= e($f['rotulo']) ?><?= empty($f['obrigatorio']) ? ' <span class="font-normal text-suave">(opcional)</span>' : '' ?></label>
            <?php if ($f['tipo'] === 'textarea'): ?>
                <textarea id="<?= $id ?>" name="<?= $campo ?>" rows="3" <?= isset($f['max']) ? 'maxlength="' . $f['max'] . '"' : '' ?> class="campo" <?= ariaErro($campo) ?>><?= e((string) $v) ?></textarea>
            <?php elseif ($f['tipo'] === 'imagem'): $guardada = $registo[$campo] ?? null; ?>
                <?php if ($guardada): ?>
                <img src="<?= asset('img/' . $guardada) ?>" alt="Imagem actual" class="mb-3 aspect-[16/6] w-full rounded-xl object-cover bg-elevado">
                <label class="mb-2 flex items-center gap-2.5 text-sm cursor-pointer"><input type="checkbox" name="remover_<?= $campo ?>" value="1" class="h-4 w-4 accent-[rgb(var(--marca))]"> Remover a imagem actual</label>
                <?php endif; ?>
                <input id="<?= $id ?>" name="<?= $campo ?>" type="file" accept="image/jpeg,image/png,image/webp" class="campo h-auto py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-elevado file:px-3 file:py-1.5 file:text-sm file:font-semibold" <?= ariaErro($campo) ?>>
                <select id="<?= $id ?>" name="<?= $campo ?>" class="campo" <?= ariaErro($campo) ?>>
                    <?php if (empty($f['obrigatorio'])): ?><option value=""><?= e($f['vazio'] ?? '—') ?></option><?php endif; ?>
                    <?php foreach ($ops as $k => $t): ?><option value="<?= e((string) $k) ?>" <?= (string) $v === (string) $k ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                </select>
            <?php else:
                $tipoInput = ['numero' => 'number', 'datahora' => 'datetime-local', 'data' => 'date'][$f['tipo']] ?? 'text';
                $valInput = $v;
                if ($f['tipo'] === 'datahora' && $v && !isset($a[$campo])) $valInput = date('Y-m-d\TH:i', strtotime($v));
                if ($f['tipo'] === 'dinheiro' && $v !== null && $v !== '' && !isset($a[$campo])) $valInput = 0 + $v; ?>
                <input id="<?= $id ?>" name="<?= $campo ?>" type="<?= $tipoInput ?>" value="<?= e((string) $valInput) ?>"
                       <?= isset($f['max']) ? 'maxlength="' . $f['max'] . '"' : '' ?> <?= in_array($f['tipo'], ['numero', 'dinheiro'], true) ? 'inputmode="decimal"' : '' ?>
                       class="campo <?= in_array($f['tipo'], ['numero', 'dinheiro'], true) ? 'num' : '' ?> <?= !empty($f['maiusculas']) ? 'uppercase' : '' ?>" <?= ariaErro($campo) ?>
                       <?= $f['tipo'] === 'slug' ? 'placeholder="gerado automaticamente"' : '' ?>>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($f['ajuda']) && !isset(\Core\Flash::erros()[$campo])): ?><p class="mt-1.5 text-xs text-suave"><?= e($f['ajuda']) ?></p><?php endif; ?>
        <?= erroCampo($campo) ?>
    </div>
    <?php endforeach; ?>
    <div class="flex gap-2 pt-2">
        <button class="btn-primario">Guardar</button>
        <a href="<?= url("/admin/c/$entidade") ?>" class="btn-fantasma">Cancelar</a>
    </div>
</form>
