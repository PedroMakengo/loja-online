<?php
/** @var array $cfg @var string $entidade @var array $linhas @var array $pg @var array $opcoesTabela */
$colunas = array_filter($cfg['campos'], fn ($f) => !empty($f['lista']));
$tituloAdmin = $cfg['titulo'];
$accoesAdmin = '<a href="' . url("/admin/c/$entidade/novo") . '" class="btn-primario">' . icone('mais', 'h-4 w-4') . ' Novo ' . e($cfg['singular']) . '</a>';
require dirname(__DIR__) . '/_titulo.php';
$mostrar = function (string $campo, array $f, $valor) use ($opcoesTabela) {
    if ($valor === null || $valor === '') return '<span class="text-suave">—</span>';
    return match ($f['tipo']) {
        'booleano' => $valor ? '<span class="text-sucesso font-semibold">Sim</span>' : '<span class="text-suave">Não</span>',
        'dinheiro' => '<span class="num">' . number_format((float) $valor, (float) $valor == (int) $valor ? 0 : 2, ',', ' ') . '</span>',
        'datahora', 'data' => '<span class="num">' . date($f['tipo'] === 'data' ? 'd/m/Y' : 'd/m/Y H:i', strtotime($valor)) . '</span>',
        'select' => e((string) ($f['opcoes'][$valor] ?? $opcoesTabela[$campo][$valor] ?? $valor)),
        'numero' => '<span class="num">' . e((string) $valor) . '</span>',
        'imagem' => '<img src="' . asset('img/' . $valor) . '" alt="" class="h-10 w-20 rounded-md object-cover bg-elevado" loading="lazy">',
        default => e(mb_strimwidth((string) $valor, 0, 60, '…')),
    };
};
?>
<?php if (!empty($cfg['pesquisa'])): ?>
<form method="get" class="mb-4 flex gap-2"><label for="q" class="sr-only">Procurar</label>
    <input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Procurar" class="campo h-10 max-w-xs"><button class="btn-secundario h-10">Procurar</button></form>
<?php endif; ?>
<div class="cartao overflow-hidden"><div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-elevado text-left text-xs text-suave"><tr>
        <?php foreach ($colunas as $f): ?><th class="px-4 py-3 font-semibold whitespace-nowrap"><?= e($f['rotulo']) ?></th><?php endforeach; ?>
        <?php if (!empty($cfg['contagem'])): ?><th class="px-4 py-3 font-semibold text-right"><?= ucfirst($cfg['contagem'][1]) ?></th><?php endif; ?>
        <th class="px-4 py-3"><span class="sr-only">Acções</span></th>
    </tr></thead>
    <tbody class="divide-y divide-linha">
        <?php foreach ($linhas as $l): ?>
        <tr class="hover:bg-elevado/50">
            <?php $primeira = true; foreach ($colunas as $campo => $f): ?>
            <td class="px-4 py-3 <?= $primeira ? 'font-semibold' : '' ?>">
                <?php if ($primeira): ?><a href="<?= url("/admin/c/$entidade/" . $l['id']) ?>" class="hover:text-marca"><?= $mostrar($campo, $f, $l[$campo]) ?></a><?php else: ?><?= $mostrar($campo, $f, $l[$campo]) ?><?php endif; ?>
            </td>
            <?php $primeira = false; endforeach; ?>
            <?php if (!empty($cfg['contagem'])): ?><td class="px-4 py-3 text-right num"><?= (int) $l['_contagem'] ?></td><?php endif; ?>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <a href="<?= url("/admin/c/$entidade/" . $l['id']) ?>" class="link text-xs">Editar</a>
                <form action="<?= url("/admin/c/$entidade/" . $l['id'] . '/apagar') ?>" method="post" class="inline ml-3" data-confirmar="Apagar este registo? Esta acção não pode ser desfeita.">
                    <?= \Core\Csrf::campo() ?><button class="text-xs font-semibold text-suave hover:text-coral">Apagar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$linhas): ?><tr><td colspan="<?= count($colunas) + 2 ?>" class="px-4 py-10 text-center text-suave">Ainda não há registos.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<?php require dirname(__DIR__) . '/_paginacao.php'; ?>
