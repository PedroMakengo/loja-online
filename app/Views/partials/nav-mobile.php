<?php /** Barra de navegação inferior — só mobile */ ?>
<nav class="lg:hidden fixed inset-x-0 bottom-0 z-50 bg-superficie/95 backdrop-blur border-t border-linha pb-seguro" aria-label="Navegação rápida">
    <ul class="grid grid-cols-5 text-[11px] font-medium">
        <?php
        $itensNav = [
            ['/', 'casa', 'Início'],
            ['/categorias', 'grelha', 'Categorias'],
            ['/promocoes', 'etiqueta', 'Promoções'],
            ['/favoritos', 'coracao', 'Favoritos'],
            ['/conta', 'conta', 'Conta'],
        ];
        foreach ($itensNav as [$href, $ic, $rotulo]):
            $activo = rotaActiva($href);
        ?>
        <li>
            <a href="<?= url($href) ?>" class="flex h-16 flex-col items-center justify-center gap-1 <?= $activo ? 'text-marca' : 'text-suave' ?>" <?= $activo ? 'aria-current="page"' : '' ?>>
                <?php if ($ic === 'etiqueta'): ?>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H9l-6-8z"/><circle cx="9" cy="12" r="1.4"/></svg>
                <?php else: ?>
                    <?= icone($ic, 'h-6 w-6') ?>
                <?php endif; ?>
                <?= $rotulo ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
