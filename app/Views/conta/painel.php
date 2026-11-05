<?php
/** @var array $resumo @var array $pedidos @var ?array $endereco @var array $favoritos */
use Core\Auth;
$hora = (int) date('G');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 19 ? 'Boa tarde' : 'Boa noite');
?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>

        <div class="min-w-0 space-y-6">
            <header>
                <h1 class="font-display text-3xl sm:text-4xl font-extrabold"><?= $saudacao ?>, <?= e(Auth::primeiroNome()) ?></h1>
                <p class="mt-1 text-suave">Cliente desde <?= e(strftime_pt(Auth::cliente()['criado_em'])) ?>.</p>
            </header>

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php foreach ([
                    ['Pedidos', $resumo['pedidos'], '/conta/pedidos'],
                    ['Em curso', $resumo['em_curso'], '/conta/pedidos'],
                    ['Favoritos', $resumo['favoritos'], '/favoritos'],
                    ['Pontos', (int) Auth::cliente()['pontos'], '/conta/pontos'],
                ] as [$rotulo, $valor, $href]): ?>
                <div class="cartao p-4">
                    <dt class="text-sm text-suave"><?= $rotulo ?></dt>
                    <dd class="mt-1 font-display text-3xl font-extrabold num">
                        <?= $href ? '<a href="' . url($href) . '" class="hover:text-marca">' . $valor . '</a>' : $valor ?>
                    </dd>
                </div>
                <?php endforeach; ?>
            </dl>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                <section class="cartao" aria-labelledby="t-ultimos">
                    <div class="flex items-center justify-between border-b border-linha p-4 sm:px-5">
                        <h2 id="t-ultimos" class="font-display text-lg font-bold">Últimos pedidos</h2>
                        <?php if ($pedidos): ?><a href="<?= url('/conta/pedidos') ?>" class="link text-sm">Ver todos</a><?php endif; ?>
                    </div>
                    <?php if ($pedidos): ?>
                    <ul class="divide-y divide-linha">
                        <?php foreach ($pedidos as $pd): $estadoPedido = $pd['estado']; ?>
                        <li class="relative flex items-center justify-between gap-3 p-4 sm:px-5 hover:bg-elevado/50 cursor-pointer" data-card="<?= url('/conta/pedidos/' . $pd['numero']) ?>">
                            <div class="min-w-0">
                                <a href="<?= url('/conta/pedidos/' . $pd['numero']) ?>" class="font-semibold num hover:text-marca after:absolute after:inset-0"><?= e($pd['numero']) ?></a>
                                <p class="truncate text-sm text-suave"><?= e($pd['primeiro_artigo'] ?? '') ?><?= $pd['artigos'] > 1 ? ' e mais ' . ($pd['artigos'] - 1) : '' ?></p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="preco text-base"><?= kz($pd['total']) ?></p>
                                <?php require __DIR__ . '/_estado.php'; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="p-6 text-center">
                        <p class="text-suave">Ainda não fez nenhuma encomenda.</p>
                        <a href="<?= url('/promocoes') ?>" class="btn-primario mt-4">Ver promoções</a>
                    </div>
                    <?php endif; ?>
                </section>

                <section class="cartao" aria-labelledby="t-morada">
                    <div class="flex items-center justify-between border-b border-linha p-4 sm:px-5">
                        <h2 id="t-morada" class="font-display text-lg font-bold">Morada principal</h2>
                        <a href="<?= url('/conta/enderecos') ?>" class="link text-sm"><?= $endereco ? 'Gerir' : '' ?></a>
                    </div>
                    <div class="p-4 sm:px-5">
                        <?php if ($endereco): $end = $endereco; require __DIR__ . '/_endereco.php'; ?>
                        <?php else: ?>
                        <p class="text-sm text-suave">Guarde uma morada para o checkout ficar mais rápido.</p>
                        <a href="<?= url('/conta/enderecos?novo=1') ?>" class="btn-secundario mt-4 w-full"><?= icone('mais', 'h-4 w-4') ?> Adicionar morada</a>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <?php if ($porAvaliar): ?>
            <section class="cartao p-5" aria-labelledby="t-avaliar">
                <h2 id="t-avaliar" class="font-display text-lg font-bold">Já recebeu, conte-nos como correu</h2>
                <p class="text-sm text-suave">Cada avaliação aprovada vale <?= \App\Servicos\Fidelidade::cfg()['pontos_avaliacao'] ?> pontos.</p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <?php foreach ($porAvaliar as $pa): ?>
                    <li class="flex items-center gap-3 rounded-xl border border-linha p-3">
                        <img src="<?= imagemProduto($pa) ?>" alt="" class="h-12 w-12 shrink-0 rounded-lg bg-white object-contain p-1" width="48" height="48">
                        <span class="min-w-0 flex-1 text-sm font-medium line-clamp-2"><?= e($pa['nome']) ?></span>
                        <a href="<?= url('/produto/' . $pa['slug'] . '#avaliar') ?>" class="btn-secundario h-9 px-3 text-xs shrink-0">Avaliar</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <?php if ($favoritos): ?>
            <section aria-labelledby="t-fav">
                <div class="mb-3 flex items-center justify-between">
                    <h2 id="t-fav" class="font-display text-lg font-bold">Os seus favoritos</h2>
                    <a href="<?= url('/favoritos') ?>" class="link text-sm">Ver todos</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4">
                    <?php foreach ($favoritos as $p) { require RAIZ . '/app/Views/partials/produto-card.php'; } ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>
