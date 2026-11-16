<?php /** @var array $menuCategorias  @var int $qtdCarrinho */ ?>
<header class="sticky top-0 z-50 bg-fundo/95 backdrop-blur supports-[backdrop-filter]:bg-fundo/80 border-b border-linha">
    <!-- Faixa de informação -->
    <div class="bg-grafite text-white/90 text-xs">
        <div class="container flex h-8 items-center justify-between gap-4">
            <p class="truncate"><span class="text-white font-semibold">Entrega em 24 horas em Luanda.</span> Grátis acima de <?= kz(PORTES_GRATIS_LUANDA) ?>.</p>
            <nav class="hidden md:flex items-center gap-5 shrink-0" aria-label="Ligações úteis">
                <a href="<?= url('/entregas') ?>" class="hover:text-white">Entregas e prazos</a>
                <a href="<?= url('/faq') ?>" class="hover:text-white">Ajuda</a>
                <a href="tel:<?= e(str_replace(' ', '', TELEFONE_LOJA)) ?>" class="hover:text-white num"><?= e(TELEFONE_LOJA) ?></a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="flex h-16 items-center gap-2 lg:gap-6">
            <button type="button" class="btn-icone lg:hidden -ml-2" data-abrir="menu-mobile" aria-label="Abrir menu">
                <?= icone('menu', 'h-6 w-6') ?>
            </button>

            <a href="<?= url('/') ?>" class="shrink-0" aria-label="<?= e(NOME_SITE) ?> — página inicial">
                <?php $tamanho = 'md'; require __DIR__ . '/logo.php'; ?>
            </a>

            <!-- Pesquisa (desktop) -->
            <div class="hidden lg:block flex-1 max-w-2xl">
                <?php $idPesquisa = 'pesquisa-desktop'; require __DIR__ . '/pesquisa.php'; ?>
            </div>

            <div class="ml-auto flex items-center gap-0.5">
                <button type="button" class="btn-icone" data-tema aria-label="Alternar modo claro/escuro">
                    <span class="dark:hidden"><?= icone('lua') ?></span>
                    <span class="hidden dark:inline"><?= icone('sol') ?></span>
                </button>
                <?php if ($clienteSessao): ?>
                <a href="<?= url('/conta') ?>" class="btn-icone hidden sm:inline-flex lg:w-auto lg:px-3 lg:gap-2" aria-label="A minha conta">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-marca text-xs font-bold text-white dark:text-grafite"><?= e(mb_strtoupper(mb_substr(\Core\Auth::primeiroNome(), 0, 1))) ?></span>
                    <span class="hidden lg:inline text-sm font-medium max-w-[8rem] truncate"><?= e(\Core\Auth::primeiroNome()) ?></span>
                </a>
                <?php else: ?>
                <a href="<?= url('/entrar') ?>" class="btn-icone hidden sm:inline-flex lg:w-auto lg:px-3 lg:gap-2" aria-label="Entrar na conta">
                    <?= icone('conta') ?><span class="hidden lg:inline text-sm font-medium">Entrar</span>
                </a>
                <?php endif; ?>
                <a href="<?= url('/favoritos') ?>" class="btn-icone hidden sm:inline-flex" aria-label="Favoritos">
                    <?= icone('coracao') ?>
                </a>
                <a href="<?= url('/carrinho') ?>" class="btn-icone relative lg:w-auto lg:px-3 lg:gap-2" data-abrir="carrinho" aria-label="Carrinho">
                    <?= icone('carrinho', 'h-6 w-6') ?>
                    <span class="hidden lg:inline text-sm font-medium">Carrinho</span>
                    <span data-carrinho-qtd
                          class="absolute -top-0.5 left-6 min-w-[1.25rem] h-5 rounded-full bg-coral px-1 text-[11px] font-bold leading-5 text-white text-center num <?= $qtdCarrinho ? '' : 'hidden' ?>"><?= $qtdCarrinho ?></span>
                </a>
            </div>
        </div>

        <!-- Pesquisa (mobile) -->
        <div class="pb-3 lg:hidden">
            <?php $idPesquisa = 'pesquisa-mobile'; require __DIR__ . '/pesquisa.php'; ?>
        </div>
    </div>

    <!-- Navegação por categorias (desktop) -->
    <nav class="relative hidden lg:block border-t border-linha" aria-label="Categorias">
        <div class="container flex h-12 items-center gap-1 text-sm">
            <div class="relative" data-dropdown>
                <button type="button" class="btn-fantasma h-9 px-3 font-semibold" data-dropdown-botao aria-expanded="false">
                    <?= icone('grelha', 'h-4 w-4') ?> Todas as categorias <?= icone('seta-baixo', 'h-4 w-4') ?>
                </button>
                <div class="absolute left-0 top-11 z-40 w-[640px] max-h-[70vh] overflow-y-auto cartao shadow-flutuante p-3 grid grid-cols-2 gap-1 animate-entrar" data-dropdown-painel hidden>
                    <?php foreach ($menuCategorias as $c): ?>
                    <div class="flex gap-3 rounded-xl p-2 hover:bg-elevado">
                        <img src="<?= imagemCategoria($c['slug']) ?>" alt="" class="h-10 w-10 shrink-0 rounded-lg bg-white p-1" loading="lazy" width="40" height="40">
                        <span class="min-w-0">
                            <a href="<?= url('/categoria/' . $c['slug']) ?>" class="block font-semibold text-tinta hover:text-marca"><?= e($c['nome']) ?></a>
                            <?php if ($c['filhos']): ?>
                            <span class="mt-0.5 block text-xs leading-relaxed text-suave">
                                <?php foreach (array_slice($c['filhos'], 0, 4) as $j => $f): ?><?= $j ? ' · ' : '' ?><a href="<?= url('/categoria/' . $f['slug']) ?>" class="hover:text-tinta hover:underline"><?= e($f['nome']) ?></a><?php endforeach; ?><?= count($c['filhos']) > 4 ? ' …' : '' ?>
                            </span>
                            <?php else: ?>
                            <span class="block text-xs text-suave num"><?= (int) $c['total_produtos'] ?> produtos</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <span class="mx-1 h-5 w-px bg-linha" aria-hidden="true"></span>
            <?php foreach (array_slice($menuCategorias, 0, 7) as $c): ?>
            <?php // Com subcategorias: mega-menu ao passar o rato ou ao focar com o teclado (sem JS) ?>
            <div class="self-stretch flex items-center <?= $c['filhos'] ? 'group/mega' : '' ?>"><?php // altura toda da barra: o rato passa do link ao painel sem o fechar ?>
                <a href="<?= url('/categoria/' . $c['slug']) ?>" <?= $c['filhos'] ? 'aria-haspopup="true"' : '' ?>
                   class="h-9 inline-flex items-center gap-1 rounded-lg px-3 hover:bg-elevado group-hover/mega:bg-elevado <?= rotaActiva('/categoria/' . $c['slug']) ? 'font-semibold text-marca' : 'text-tinta' ?>"><?= e($c['nome']) ?><?= $c['filhos'] ? icone('seta-baixo', 'h-3.5 w-3.5 text-suave') : '' ?></a>
                <?php if ($c['filhos']): ?>
                <div class="invisible opacity-0 group-hover/mega:visible group-hover/mega:opacity-100 group-focus-within/mega:visible group-focus-within/mega:opacity-100 transition-opacity duration-150 delay-100 absolute inset-x-0 top-full z-40 border-y border-linha bg-superficie shadow-flutuante max-h-[75vh] overflow-y-auto">
                    <?php $raiz = $c; require __DIR__ . '/mega-menu.php'; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="ml-auto flex items-center gap-1">
                <a href="<?= url('/promocoes') ?>" class="h-9 inline-flex items-center rounded-lg px-3 font-semibold text-coral hover:bg-coral-suave">Promoções</a>
                <a href="<?= url('/novidades') ?>" class="h-9 inline-flex items-center rounded-lg px-3 hover:bg-elevado">Novidades</a>
                <a href="<?= url('/marcas') ?>" class="h-9 inline-flex items-center rounded-lg px-3 hover:bg-elevado">Marcas</a>
                <a href="<?= url('/blog') ?>" class="h-9 inline-flex items-center rounded-lg px-3 hover:bg-elevado <?= rotaActiva('/blog') ? 'font-semibold text-marca' : '' ?>">Guias</a>
            </div>
        </div>
    </nav>
</header>

<!-- Menu lateral (mobile) -->
<div class="fixed inset-0 z-[60] hidden" id="menu-mobile" data-gaveta role="dialog" aria-modal="true" aria-label="Menu">
    <div class="absolute inset-0 bg-grafite/50" data-fechar></div>
    <aside class="absolute inset-y-0 left-0 w-[86%] max-w-sm bg-superficie flex flex-col animate-entrar">
        <div class="flex h-16 items-center justify-between px-4 border-b border-linha">
            <?php $tamanho = 'sm'; require __DIR__ . '/logo.php'; ?>
            <button type="button" class="btn-icone" data-fechar aria-label="Fechar menu"><?= icone('fechar') ?></button>
        </div>
        <nav class="flex-1 overflow-y-auto p-3" aria-label="Menu principal">
            <div class="grid grid-cols-2 gap-2 mb-4">
                <a href="<?= url('/promocoes') ?>" class="rounded-xl bg-coral-suave p-3 font-semibold text-coral">Promoções</a>
                <a href="<?= url('/novidades') ?>" class="rounded-xl bg-marca-suave p-3 font-semibold text-marca">Novidades</a>
            </div>
            <p class="px-2 pb-2 text-sm font-semibold text-suave">Categorias</p>
            <?php foreach ($menuCategorias as $c): ?>
            <?php if (!$c['filhos']): ?>
            <a href="<?= url('/categoria/' . $c['slug']) ?>" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-elevado">
                <img src="<?= imagemCategoria($c['slug']) ?>" alt="" class="h-9 w-9 rounded-lg bg-white p-1" loading="lazy" width="36" height="36">
                <span class="flex-1 font-medium"><?= e($c['nome']) ?></span>
                <?= icone('seta-dir', 'h-4 w-4 text-suave') ?>
            </a>
            <?php else: ?>
            <!-- Categoria com subcategorias: abre/fecha (acordeão nativo) -->
            <details class="group/cat rounded-xl open:bg-elevado/60">
                <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-2 py-2 hover:bg-elevado [&::-webkit-details-marker]:hidden">
                    <img src="<?= imagemCategoria($c['slug']) ?>" alt="" class="h-9 w-9 rounded-lg bg-white p-1" loading="lazy" width="36" height="36">
                    <span class="flex-1 font-medium"><?= e($c['nome']) ?></span>
                    <?= icone('seta-baixo', 'h-4 w-4 text-suave transition-transform group-open/cat:rotate-180') ?>
                </summary>
                <ul class="pb-2 pl-14 pr-2 text-sm">
                    <li><a href="<?= url('/categoria/' . $c['slug']) ?>" class="block py-2 font-semibold text-marca">Ver tudo em <?= e($c['nome']) ?></a></li>
                    <?php foreach ($c['filhos'] as $f): ?>
                    <li>
                        <a href="<?= url('/categoria/' . $f['slug']) ?>" class="block py-2 font-medium text-tinta"><?= e($f['nome']) ?></a>
                        <?php if ($f['filhos']): ?>
                        <ul class="mb-1 border-l border-linha pl-3">
                            <?php foreach ($f['filhos'] as $n): ?>
                            <li><a href="<?= url('/categoria/' . $n['slug']) ?>" class="block py-1.5 text-suave"><?= e($n['nome']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
            <?php endforeach; ?>
            <div class="mt-4 border-t border-linha pt-4 space-y-1 text-sm">
                <a href="<?= url('/marcas') ?>" class="block rounded-lg px-2 py-2 hover:bg-elevado">Marcas</a>
                <a href="<?= url('/blog') ?>" class="block rounded-lg px-2 py-2 hover:bg-elevado">Guias de compra e dicas</a>
                <a href="<?= url('/entregas') ?>" class="block rounded-lg px-2 py-2 hover:bg-elevado">Entregas e prazos</a>
                <a href="<?= url('/sobre') ?>" class="block rounded-lg px-2 py-2 hover:bg-elevado">Sobre a DIOTEKNO</a>
                <a href="<?= url('/contactos') ?>" class="block rounded-lg px-2 py-2 hover:bg-elevado">Contactos</a>
                <a href="<?= url('/faq') ?>" class="block rounded-lg px-2 py-2 hover:bg-elevado">Perguntas frequentes</a>
            </div>
        </nav>
        <div class="p-4 border-t border-linha pb-seguro">
            <?php if ($clienteSessao): ?>
            <a href="<?= url('/conta') ?>" class="btn-primario w-full"><?= icone('conta') ?> A minha conta</a>
            <?php else: ?>
            <a href="<?= url('/entrar') ?>" class="btn-primario w-full"><?= icone('conta') ?> Entrar ou criar conta</a>
            <?php endif; ?>
        </div>
    </aside>
</div>
