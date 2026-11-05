<?php /** @var array $posts @var array $pg @var string $estado */
$tituloAdmin = 'Blog';
$accoesAdmin = '<a href="' . url('/admin/c/blog_categorias') . '" class="btn-secundario">Categorias</a><a href="' . url('/admin/blog/novo') . '" class="btn-primario">' . icone('mais', 'h-4 w-4') . ' Novo artigo</a>';
require dirname(__DIR__) . '/_titulo.php'; ?>
<nav class="faixa mb-4"><?php foreach (['' => 'Todos', 'publicado' => 'Publicados', 'agendado' => 'Agendados', 'rascunho' => 'Rascunhos'] as $k => $t): ?>
    <a href="<?= url('/admin/blog' . ($k ? '?estado=' . $k : '')) ?>" class="chip <?= $estado === $k ? 'chip-activo' : '' ?>"><?= $t ?></a><?php endforeach; ?></nav>
<div class="cartao overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-elevado text-left text-xs text-suave"><tr><th class="px-4 py-3 font-semibold">Artigo</th><th class="px-4 py-3 font-semibold">Estado</th><th class="px-4 py-3 font-semibold text-right hidden sm:table-cell">Leituras</th><th class="px-4 py-3"></th></tr></thead>
    <tbody class="divide-y divide-linha">
        <?php foreach ($posts as $p):
            $agendado = $p['estado'] === 'publicado' && strtotime((string) $p['publicado_em']) > time();
            [$rot, $cls] = $p['estado'] === 'rascunho' ? ['Rascunho', 'bg-elevado text-suave'] : ($agendado ? ['Agendado', 'bg-aviso/15 text-aviso'] : ['Publicado', 'bg-sucesso/15 text-sucesso']); ?>
        <tr class="hover:bg-elevado/50">
            <td class="px-4 py-3"><a href="<?= url('/admin/blog/' . $p['id']) ?>" class="font-semibold hover:text-marca"><?= e($p['titulo']) ?></a><?= $p['destaque'] ? ' <span class="selo-novo ml-1">Destaque</span>' : '' ?>
                <p class="text-xs text-suave"><?= e($p['categoria']) ?><?= $p['autor'] ? ', ' . e($p['autor']) : '' ?></p></td>
            <td class="px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-semibold <?= $cls ?>"><?= $rot ?></span>
                <?php if ($p['publicado_em']): ?><p class="mt-0.5 text-xs text-suave num"><?= date('d/m/Y H:i', strtotime($p['publicado_em'])) ?></p><?php endif; ?></td>
            <td class="px-4 py-3 text-right num hidden sm:table-cell"><?= (int) $p['visualizacoes'] ?></td>
            <td class="px-4 py-3 text-right whitespace-nowrap"><a href="<?= url('/blog/' . $p['slug'] . '?pre=1') ?>" target="_blank" class="link text-xs">Ver</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$posts): ?><tr><td colspan="4" class="px-4 py-10 text-center text-suave">Nenhum artigo nesta lista.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<?php require dirname(__DIR__) . '/_paginacao.php'; ?>
