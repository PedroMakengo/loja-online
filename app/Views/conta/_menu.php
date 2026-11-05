<?php
/** Navegação da conta. Espera $seccao */
use Core\Auth;
$itensConta = [
    ['painel',    '/conta',           'casa',        'Resumo'],
    ['pedidos',   '/conta/pedidos',   'camiao',      'Pedidos'],
    ['favoritos', '/favoritos',       'coracao',     'Favoritos'],
    ['pontos',    '/conta/pontos',    'estrela',     'Pontos'],
    ['enderecos', '/conta/enderecos', 'localizacao', 'Moradas'],
    ['dados',     '/conta/dados',     'conta',       'Dados pessoais'],
];
?>
<aside class="min-w-0 lg:sticky lg:top-40 lg:self-start">
    <div class="hidden lg:flex items-center gap-3 mb-4 px-1">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-marca text-white font-display font-bold dark:text-grafite">
            <?= e(mb_strtoupper(mb_substr(Auth::primeiroNome(), 0, 1))) ?>
        </span>
        <div class="min-w-0">
            <p class="truncate font-semibold"><?= e(Auth::cliente()['nome']) ?></p>
            <p class="truncate text-xs text-suave"><?= e(Auth::cliente()['email']) ?></p>
        </div>
    </div>
    <nav aria-label="A minha conta" class="faixa lg:flex lg:flex-col lg:gap-1 lg:overflow-visible">
        <?php foreach ($itensConta as [$chave, $href, $ic, $rotulo]): $activo = $seccao === $chave; ?>
        <a href="<?= url($href) ?>" <?= $activo ? 'aria-current="page"' : '' ?>
           class="inline-flex items-center gap-2.5 rounded-xl px-3 h-10 lg:h-11 text-sm font-medium whitespace-nowrap border lg:border-0 <?= $activo ? 'bg-tinta text-superficie border-tinta' : 'bg-superficie lg:bg-transparent border-linha hover:bg-elevado' ?>">
            <?= icone($ic, 'h-5 w-5') ?> <?= $rotulo ?>
        </a>
        <?php endforeach; ?>
        <form action="<?= url('/sair') ?>" method="post" class="shrink-0 lg:mt-2 lg:border-t lg:border-linha lg:pt-2">
            <?= \Core\Csrf::campo() ?>
            <button class="inline-flex w-full items-center gap-2.5 rounded-xl px-3 h-10 lg:h-11 text-sm font-medium whitespace-nowrap border border-linha lg:border-0 bg-superficie lg:bg-transparent text-suave hover:text-coral hover:bg-elevado">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h10"/></svg>
                Terminar sessão
            </button>
        </form>
    </nav>
</aside>
