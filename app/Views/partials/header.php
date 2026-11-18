<?php /** @var array $menuCategorias  @var int $qtdCarrinho */ ?>
<header class="sticky top-0 z-50 bg-fundo/95 backdrop-blur supports-[backdrop-filter]:bg-fundo/80 border-b border-linha">
    <!-- Faixa de informação -->
    <div class="bg-grafite text-white/90 text-xs">
        <div class="container flex h-8 items-center justify-between gap-4">
            <p class="truncate"><span class="text-white font-semibold">Entrega em 24 horas em Luanda.</span> Grátis acima de <?= kz(PORTES_GRATIS_LUANDA) ?>.</p>
            <nav class="hidden lg:flex items-center gap-5 shrink-0" aria-label="Ligações úteis">
                <?php // Saíram da barra de categorias (que agora só tem categorias, como na NCR) ?>
                <a href="<?= url('/promocoes') ?>" class="font-semibold text-coral hover:text-white">Promoções</a>
                <a href="<?= url('/novidades') ?>" class="hover:text-white">Novidades</a>
                <a href="<?= url('/marcas') ?>" class="hover:text-white">Marcas</a>
                <a href="<?= url('/blog') ?>" class="hover:text-white">Guias</a>
                <span class="h-3 w-px bg-white/20" aria-hidden="true"></span>
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
        <?php // Como na NCR: "☰ Todos os Produtos" e as categorias principais espalhadas pela largura da barra ?>
        <div class="container flex h-14 items-center justify-between gap-1 text-sm xl:text-[15px] font-medium">
            <div class="relative" data-dropdown>
                <button type="button" class="group/botao -ml-3 h-10 inline-flex items-center gap-3 rounded-lg pl-3 pr-2 font-semibold hover:bg-elevado aria-expanded:bg-elevado aria-expanded:text-marca" data-dropdown-botao aria-expanded="false">
                    <?= icone('menu', 'h-6 w-6') ?> <span class="inline-flex items-center gap-1">Todos os Produtos<?= icone('seta-baixo', 'h-4 w-4 shrink-0 text-suave transition-transform duration-200 group-aria-expanded/botao:rotate-180 group-aria-expanded/botao:text-marca') ?></span>
                </button>
                <?php // Ao estilo NCR: categorias principais à esquerda; clicar numa (ou setas ↑/↓) mostra as subcategorias à direita ?>
                <div class="absolute left-0 top-11 z-40 w-[min(1080px,calc(100vw-3rem))] cartao shadow-flutuante overflow-hidden animate-entrar" data-dropdown-painel hidden>
                    <div class="flex max-h-[72vh]">
                        <ul class="w-64 shrink-0 overflow-y-auto border-r border-linha p-2" data-cat-lista>
                            <?php foreach ($menuCategorias as $i => $c): ?>
                            <li>
                                <button type="button" data-cat-raiz="<?= (int) $c['id'] ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="cat-painel-<?= (int) $c['id'] ?>"
                                   class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left hover:bg-elevado aria-[current=true]:bg-marca-suave aria-[current=true]:text-marca">
                                    <img src="<?= imagemCategoria($c['imagem'] ?? $c['slug']) ?>" alt="" class="h-8 w-8 shrink-0 rounded-lg bg-white p-0.5" loading="lazy" width="32" height="32">
                                    <span class="flex-1 font-semibold"><?= e($c['nome']) ?></span>
                                    <?= icone('seta-dir', 'h-4 w-4 text-suave') ?>
                                </button>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="flex-1 overflow-y-auto">
                            <?php foreach ($menuCategorias as $i => $c): ?>
                            <div id="cat-painel-<?= (int) $c['id'] ?>" data-cat-painel="<?= (int) $c['id'] ?>" <?= $i === 0 ? '' : 'hidden' ?>>
                                <?php if ($c['filhos']): $raiz = $c; $compacto = true; require __DIR__ . '/mega-menu.php'; $compacto = false; ?>
                                <?php else: ?>
                                <div class="flex h-full min-h-56 flex-col items-start justify-center gap-3 p-8">
                                    <p class="font-display text-2xl font-extrabold"><?= e($c['nome']) ?></p>
                                    <?php if ($c['descricao']): ?><p class="max-w-md text-suave"><?= e($c['descricao']) ?></p><?php endif; ?>
                                    <a href="<?= url('/categoria/' . $c['slug']) ?>" class="btn-primario">Ver os <?= (int) $c['total_produtos'] ?> produtos</a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php foreach (array_slice($menuCategorias, 0, 7) as $i => $c): $activa = rotaActiva('/categoria/' . $c['slug']); $soXl = $i >= 5 ? 'hidden xl:inline-flex' : 'inline-flex'; // em ecrãs < 1280px cabem 5 (as outras ficam em "Todos os Produtos") ?>
            <?php if (!$c['filhos']): ?>
            <a href="<?= url('/categoria/' . $c['slug']) ?>"
               class="<?= $soXl ?> h-10 items-center whitespace-nowrap rounded-lg px-3 hover:bg-elevado <?= $activa ? 'text-marca' : 'text-tinta' ?>"><?= e($c['nome']) ?></a>
            <?php else: ?>
            <?php // Clicar na categoria abre o painel com as subcategorias (clicar outra vez, fora ou Escape fecha) ?>
            <div class="<?= $soXl ?> items-center" data-dropdown><?php // sem "relative": o painel ocupa a largura toda da <nav> ?>
                <button type="button" data-dropdown-botao aria-expanded="false" aria-controls="mega-<?= (int) $c['id'] ?>"
                        class="group/botao h-10 inline-flex items-center gap-1 whitespace-nowrap rounded-lg pl-3 pr-2 hover:bg-elevado aria-expanded:bg-elevado aria-expanded:text-marca <?= $activa ? 'text-marca' : 'text-tinta' ?>">
                    <?= e($c['nome']) ?><?= icone('seta-baixo', 'h-4 w-4 shrink-0 text-suave transition-transform duration-200 group-hover/botao:text-tinta group-aria-expanded/botao:rotate-180 group-aria-expanded/botao:text-marca') ?>
                </button>
                <div id="mega-<?= (int) $c['id'] ?>" data-dropdown-painel hidden
                     class="absolute inset-x-0 top-full z-40 border-y border-linha bg-superficie shadow-flutuante max-h-[75vh] overflow-y-auto animate-entrar">
                    <?php $raiz = $c; require __DIR__ . '/mega-menu.php'; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
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
        <?php
        // Ao estilo NCR: tocar numa categoria com subcategorias abre um ecrã só dela
        // (com "Voltar"); o mesmo para as subcategorias que têm sub-subcategorias.
        $linkMenu = 'flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-elevado';
        ?>
        <div class="relative flex-1 min-h-0 overflow-hidden" data-submenus>
        <nav class="h-full overflow-y-auto p-3" aria-label="Menu principal">
            <div class="grid grid-cols-2 gap-2 mb-4">
                <a href="<?= url('/promocoes') ?>" class="rounded-xl bg-coral-suave p-3 font-semibold text-coral">Promoções</a>
                <a href="<?= url('/novidades') ?>" class="rounded-xl bg-marca-suave p-3 font-semibold text-marca">Novidades</a>
            </div>
            <p class="px-2 pb-2 text-sm font-semibold text-suave">Categorias</p>
            <?php foreach ($menuCategorias as $c): ?>
            <?php if (!$c['filhos']): ?>
            <a href="<?= url('/categoria/' . $c['slug']) ?>" class="<?= $linkMenu ?>">
            <?php else: ?>
            <button type="button" data-sub-abrir="m-<?= (int) $c['id'] ?>" aria-controls="m-<?= (int) $c['id'] ?>" aria-expanded="false" class="<?= $linkMenu ?> w-full text-left">
            <?php endif; ?>
                <img src="<?= imagemCategoria($c['imagem'] ?? $c['slug']) ?>" alt="" class="h-9 w-9 rounded-lg bg-white p-1" loading="lazy" width="36" height="36">
                <span class="flex-1 font-medium"><?= e($c['nome']) ?></span>
                <?= icone('seta-dir', 'h-4 w-4 text-suave') ?>
            <?= $c['filhos'] ? '</button>' : '</a>' ?>
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
        <?php
        // Um ecrã por categoria (e por subcategoria) com filhas. $pai = ecrã a que "Voltar" regressa.
        $ecras = [];
        foreach ($menuCategorias as $c) {
            if (!$c['filhos']) continue;
            $ecras[] = [$c, null, 'Menu'];
            foreach ($c['filhos'] as $f) {
                if ($f['filhos']) $ecras[] = [$f, 'm-' . (int) $c['id'], $c['nome']];
            }
        }
        foreach ($ecras as [$no, $pai, $voltarPara]): ?>
        <div id="m-<?= (int) $no['id'] ?>" data-sub="m-<?= (int) $no['id'] ?>" data-sub-pai="<?= e((string) $pai) ?>" hidden
             class="absolute inset-0 overflow-y-auto bg-superficie p-3 animate-entrar" role="region" aria-label="<?= e($no['nome']) ?>">
            <button type="button" data-sub-voltar class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold text-suave hover:bg-elevado hover:text-tinta">
                <?= icone('seta-esq', 'h-4 w-4') ?> <?= e($voltarPara) ?>
            </button>
            <p class="px-2 pt-2 font-display text-xl font-extrabold"><?= e($no['nome']) ?></p>
            <a href="<?= url('/categoria/' . $no['slug']) ?>" class="mt-1 mb-3 block px-2 py-1 text-sm font-semibold text-marca">Ver todos os <?= (int) $no['total_produtos'] ?> produtos</a>
            <ul class="border-t border-linha">
                <?php foreach ($no['filhos'] as $f): ?>
                <li class="border-b border-linha">
                    <?php if ($f['filhos']): ?>
                    <button type="button" data-sub-abrir="m-<?= (int) $f['id'] ?>" aria-controls="m-<?= (int) $f['id'] ?>" aria-expanded="false" class="flex w-full items-center gap-3 px-2 py-3 text-left font-medium hover:bg-elevado">
                        <span class="flex-1"><?= e($f['nome']) ?></span><?= icone('seta-dir', 'h-4 w-4 text-suave') ?>
                    </button>
                    <?php else: ?>
                    <a href="<?= url('/categoria/' . $f['slug']) ?>" class="flex items-center gap-3 px-2 py-3 font-medium hover:bg-elevado">
                        <span class="flex-1"><?= e($f['nome']) ?></span>
                    </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="p-4 border-t border-linha pb-seguro">
            <?php if ($clienteSessao): ?>
            <a href="<?= url('/conta') ?>" class="btn-primario w-full"><?= icone('conta') ?> A minha conta</a>
            <?php else: ?>
            <a href="<?= url('/entrar') ?>" class="btn-primario w-full"><?= icone('conta') ?> Entrar ou criar conta</a>
            <?php endif; ?>
        </div>
    </aside>
</div>
