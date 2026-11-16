<?php
/**
 * @var ?array $registo @var ?int $pai @var array $maes @var array $destinos @var int $produtos
 * @var array $filhas @var array $caminho @var bool $podeTerFilhas
 */
use App\Models\Categoria;

$a = \Core\Flash::antigo();
$val = fn (string $campo, $padrao = null) => array_key_exists($campo, $a) ? $a[$campo] : ($registo[$campo] ?? $padrao);
$paiActual = (string) (array_key_exists('parent_id', $a) ? $a['parent_id'] : ($pai ?? ''));
?>
<a href="<?= url('/admin/categorias' . ($registo ? '#cat-' . $registo['id'] : '')) ?>" class="inline-flex items-center gap-1 text-sm text-suave hover:text-tinta"><?= icone('seta-esq', 'h-4 w-4') ?> Categorias</a>
<?php
$tituloAdmin = $registo ? 'Editar "' . $registo['nome'] . '"' : (!$registo && $pai ? 'Nova subcategoria' : 'Nova categoria');
if ($caminho) { $subtituloAdmin = 'Dentro de: ' . e(implode(' › ', $caminho)); }
require dirname(__DIR__) . '/_titulo.php';
?>
<div class="grid gap-6 lg:grid-cols-[minmax(0,42rem)_1fr] items-start">
<form action="<?= url('/admin/categorias/guardar') ?>" method="post" class="cartao p-5 sm:p-6 space-y-4" novalidate>
    <?= \Core\Csrf::campo() ?>
    <input type="hidden" name="id" value="<?= (int) ($registo['id'] ?? 0) ?>">

    <div>
        <label for="c-nome" class="rotulo">Nome</label>
        <input id="c-nome" name="nome" value="<?= e((string) $val('nome', '')) ?>" maxlength="120" class="campo" required <?= ariaErro('nome') ?>>
        <?= erroCampo('nome') ?>
    </div>

    <div>
        <label for="c-pai" class="rotulo">Fica dentro de <span class="font-normal text-suave">(categoria-mãe)</span></label>
        <select id="c-pai" name="parent_id" class="campo" <?= ariaErro('parent_id') ?>>
            <option value="">— Nenhuma: é uma categoria principal (aparece na barra do menu)</option>
            <?php foreach ($maes as $id => $nome): ?>
            <option value="<?= $id ?>" <?= $paiActual === (string) $id ? 'selected' : '' ?>><?= e($nome) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!isset(\Core\Flash::erros()['parent_id'])): ?>
        <p class="mt-1.5 text-xs text-suave">Ex.: "Computadores Portáteis" fica dentro de "Informática"; "Macbook" fica dentro de "Computadores Portáteis". Máximo <?= Categoria::NIVEIS ?> níveis.</p>
        <?php endif; ?>
        <?= erroCampo('parent_id') ?>
    </div>

    <div>
        <label for="c-slug" class="rotulo">Endereço (URL) <span class="font-normal text-suave">(opcional)</span></label>
        <input id="c-slug" name="slug" value="<?= e((string) $val('slug', '')) ?>" maxlength="140" class="campo" placeholder="gerado automaticamente" <?= ariaErro('slug') ?>>
        <?php if (!isset(\Core\Flash::erros()['slug'])): ?><p class="mt-1.5 text-xs text-suave">Fica em /categoria/endereco. Mudar o endereço de uma categoria já publicada quebra links antigos.</p><?php endif; ?>
        <?= erroCampo('slug') ?>
    </div>

    <div>
        <label for="c-desc" class="rotulo">Descrição curta <span class="font-normal text-suave">(opcional)</span></label>
        <textarea id="c-desc" name="descricao" rows="2" maxlength="255" class="campo" <?= ariaErro('descricao') ?>><?= e((string) $val('descricao', '')) ?></textarea>
        <?= erroCampo('descricao') ?>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="c-ordem" class="rotulo">Ordem no menu</label>
            <input id="c-ordem" name="ordem" type="number" inputmode="numeric" value="<?= e((string) $val('ordem', '0')) ?>" class="campo num" <?= ariaErro('ordem') ?>>
            <?php if (!isset(\Core\Flash::erros()['ordem'])): ?><p class="mt-1.5 text-xs text-suave">Menor aparece primeiro, entre as irmãs.</p><?php endif; ?>
            <?= erroCampo('ordem') ?>
        </div>
        <div class="space-y-3 sm:pt-7">
            <label class="flex items-center gap-2.5 text-sm font-medium cursor-pointer"><input type="checkbox" name="activo" value="1" <?= $val('activo', 1) ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]"> Activa (visível na loja)</label>
            <label class="flex items-center gap-2.5 text-sm font-medium cursor-pointer"><input type="checkbox" name="destaque" value="1" <?= $val('destaque', 0) ? 'checked' : '' ?> class="h-4 w-4 accent-[rgb(var(--marca))]"> Mostrar na página inicial</label>
        </div>
    </div>

    <details class="rounded-xl border border-linha p-4" <?= isset(\Core\Flash::erros()['meta_titulo']) || isset(\Core\Flash::erros()['meta_descricao']) ? 'open' : '' ?>>
        <summary class="cursor-pointer text-sm font-semibold">Google (SEO) <span class="font-normal text-suave">— opcional</span></summary>
        <div class="mt-4 space-y-4">
            <div>
                <label for="c-mt" class="rotulo">Título para o Google</label>
                <input id="c-mt" name="meta_titulo" value="<?= e((string) $val('meta_titulo', '')) ?>" maxlength="160" class="campo" <?= ariaErro('meta_titulo') ?>>
                <p class="mt-1.5 text-xs text-suave">Até 60 caracteres aparecem completos.</p>
                <?= erroCampo('meta_titulo') ?>
            </div>
            <div>
                <label for="c-md" class="rotulo">Descrição para o Google</label>
                <textarea id="c-md" name="meta_descricao" rows="2" maxlength="255" class="campo" <?= ariaErro('meta_descricao') ?>><?= e((string) $val('meta_descricao', '')) ?></textarea>
                <?= erroCampo('meta_descricao') ?>
            </div>
        </div>
    </details>

    <div class="flex gap-2 pt-2">
        <button class="btn-primario">Guardar</button>
        <a href="<?= url('/admin/categorias') ?>" class="btn-fantasma">Cancelar</a>
    </div>
</form>

<?php if ($registo): ?>
<aside class="space-y-4">
    <section class="cartao p-5" aria-labelledby="t-filhas">
        <div class="flex items-center justify-between gap-2">
            <h2 id="t-filhas" class="font-display text-lg font-bold">Subcategorias</h2>
            <?php if ($podeTerFilhas): ?><a href="<?= url('/admin/categorias/novo?pai=' . $registo['id']) ?>" class="btn-secundario h-9 text-sm"><?= icone('mais', 'h-4 w-4') ?> Adicionar</a><?php endif; ?>
        </div>
        <?php if ($filhas): ?>
        <ul class="mt-3 divide-y divide-linha text-sm">
            <?php foreach ($filhas as $f): ?>
            <li class="flex items-center justify-between gap-2 py-2">
                <a href="<?= url('/admin/categorias/' . $f['id']) ?>" class="font-medium hover:text-marca"><?= e($f['nome']) ?></a>
                <span class="text-xs text-suave num"><?= (int) $f['total_produtos'] ?> produtos<?= (int) $f['activo'] ? '' : ' · inactiva' ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="mt-2 text-sm text-suave"><?= $podeTerFilhas ? 'Ainda não tem subcategorias.' : 'Último nível: não pode ter subcategorias.' ?></p>
        <?php endif; ?>
    </section>

    <section class="cartao p-5" aria-labelledby="t-mover">
        <h2 id="t-mover" class="font-display text-lg font-bold">Produtos</h2>
        <p class="mt-1 text-sm text-suave"><a href="<?= url('/admin/produtos?categoria=' . $registo['id']) ?>" class="link"><?= $produtos ?> produto(s)</a> directamente nesta categoria.</p>
        <?php if ($produtos > 0): ?>
        <form action="<?= url('/admin/categorias/' . $registo['id'] . '/mover-produtos') ?>" method="post" class="mt-3 space-y-2"
              data-confirmar="Mover os <?= $produtos ?> produto(s) para a categoria escolhida?">
            <?= \Core\Csrf::campo() ?>
            <label for="c-dest" class="rotulo">Mover todos para</label>
            <select id="c-dest" name="destino" class="campo" required>
                <option value="">Escolha a categoria…</option>
                <?php foreach ($destinos as $id => $nome): ?><option value="<?= $id ?>"><?= e($nome) ?></option><?php endforeach; ?>
            </select>
            <button class="btn-secundario w-full">Mover produtos</button>
        </form>
        <?php elseif (!$filhas): ?>
        <form action="<?= url('/admin/categorias/' . $registo['id'] . '/apagar') ?>" method="post" class="mt-3" data-confirmar="Apagar a categoria &quot;<?= e($registo['nome']) ?>&quot;? Esta acção não pode ser desfeita.">
            <?= \Core\Csrf::campo() ?><button class="text-sm font-semibold text-coral hover:underline">Apagar esta categoria</button>
        </form>
        <?php endif; ?>
    </section>
</aside>
<?php endif; ?>
</div>
