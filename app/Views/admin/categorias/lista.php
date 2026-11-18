<?php
/** @var array $categorias  árvore achatada (Categoria::arvoreAdmin) @var string $q */
use App\Models\Categoria;

$tituloAdmin = 'Categorias';
$subtituloAdmin = 'Categoria › subcategoria › sub-subcategoria (até ' . Categoria::NIVEIS . ' níveis). A ordem define a posição no menu da loja.';
$accoesAdmin = '<a href="' . url('/admin/categorias/novo') . '" class="btn-primario">' . icone('mais', 'h-4 w-4') . ' Nova categoria</a>';
require dirname(__DIR__) . '/_titulo.php';
$cores = [1 => 'bg-marca-suave text-marca', 2 => 'bg-elevado text-tinta', 3 => 'bg-elevado text-suave'];
?>
<form method="get" class="mb-4 flex gap-2"><label for="q" class="sr-only">Procurar</label>
    <input id="q" name="q" value="<?= e($q) ?>" placeholder="Procurar categoria" class="campo h-10 max-w-xs"><button class="btn-secundario h-10">Procurar</button>
    <?php if ($q !== ''): ?><a href="<?= url('/admin/categorias') ?>" class="btn-fantasma h-10">Ver árvore completa</a><?php endif; ?></form>

<div class="cartao overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-elevado text-left text-xs text-suave"><tr>
        <th class="px-4 py-3 font-semibold">Nome</th>
        <th class="px-4 py-3 font-semibold hidden md:table-cell">Nível</th>
        <th class="px-4 py-3 font-semibold text-right">Ordem</th>
        <th class="px-4 py-3 font-semibold text-right" title="Produtos nesta categoria / incluindo subcategorias">Produtos</th>
        <th class="px-4 py-3 font-semibold">Estado</th>
        <th class="px-4 py-3"><span class="sr-only">Acções</span></th>
    </tr></thead>
    <tbody class="divide-y divide-linha">
        <?php foreach ($categorias as $c): $n = (int) $c['nivel']; ?>
        <tr id="cat-<?= (int) $c['id'] ?>" class="hover:bg-elevado/50 target:bg-marca-suave/40 <?= $n === 1 && $q === '' ? 'bg-elevado/30' : '' ?>">
            <td class="px-4 py-3">
                <div class="flex items-center gap-2" style="padding-left: <?= $q === '' ? ($n - 1) * 1.75 : 0 ?>rem">
                    <?php if ($n > 1 && $q === ''): ?><span class="text-linha select-none" aria-hidden="true">└</span><?php endif; ?>
                    <a href="<?= url('/admin/categorias/' . $c['id']) ?>" class="hover:text-marca <?= $n === 1 ? 'font-bold' : 'font-medium' ?>"><?= e($c['nome']) ?></a>
                    <span class="text-xs text-suave hidden lg:inline">/<?= e($c['slug']) ?></span>
                    <?php if ((int) $c['total_produtos'] === 0 && (int) $c['activo']): ?><span class="rounded-full bg-elevado px-2 py-0.5 text-[11px] font-semibold text-suave" title="Sem produtos: não aparece nos menus da loja até ter pelo menos um">Vazia · fora do menu</span><?php endif; ?>
                    <?php if ((int) $c['destaque']): ?><span class="rounded-full bg-aviso/15 px-2 py-0.5 text-[11px] font-semibold text-aviso" title="Aparece na página inicial">Início</span><?php endif; ?>
                </div>
            </td>
            <td class="px-4 py-3 hidden md:table-cell"><span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $cores[$n] ?? $cores[3] ?>"><?= e(Categoria::NOMES_NIVEL[$n] ?? 'Nível ' . $n) ?></span></td>
            <td class="px-4 py-3 text-right num"><?= (int) $c['ordem'] ?></td>
            <td class="px-4 py-3 text-right num whitespace-nowrap">
                <a href="<?= url('/admin/produtos?categoria=' . $c['id']) ?>" class="hover:text-marca"><?= (int) $c['produtos_directos'] ?></a><?php if ($c['tem_filhos']): ?><span class="text-suave"> / <?= (int) $c['total_produtos'] ?></span><?php endif; ?>
            </td>
            <td class="px-4 py-3"><?= (int) $c['activo'] ? '<span class="text-sucesso font-semibold">Activa</span>' : '<span class="text-suave">Inactiva</span>' ?></td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if ($n < Categoria::NIVEIS): ?>
                <a href="<?= url('/admin/categorias/novo?pai=' . $c['id']) ?>" class="link text-xs">+ <?= e(mb_strtolower(Categoria::NOMES_NIVEL[$n + 1])) ?></a>
                <?php endif; ?>
                <a href="<?= url('/admin/categorias/' . $c['id']) ?>" class="link text-xs ml-3">Editar</a>
                <?php if (!$c['tem_filhos'] && !(int) $c['produtos_directos']): ?>
                <form action="<?= url('/admin/categorias/' . $c['id'] . '/apagar') ?>" method="post" class="inline ml-3" data-confirmar="Apagar a categoria &quot;<?= e($c['nome']) ?>&quot;? Esta acção não pode ser desfeita.">
                    <?= \Core\Csrf::campo() ?><button class="text-xs font-semibold text-suave hover:text-coral">Apagar</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$categorias): ?><tr><td colspan="6" class="px-4 py-10 text-center text-suave"><?= $q !== '' ? 'Nenhuma categoria com esse nome.' : 'Ainda não há categorias.' ?></td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<p class="mt-3 text-xs text-suave">Produtos: na própria categoria / incluindo subcategorias. Só pode apagar categorias sem subcategorias e sem produtos; uma categoria inactiva esconde também todas as suas subcategorias da loja. Categorias sem produtos (nem nas subcategorias) não aparecem nos menus até terem pelo menos um.</p>
