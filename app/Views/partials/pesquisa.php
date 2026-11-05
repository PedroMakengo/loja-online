<?php /** Pesquisa com sugestões automáticas. $idPesquisa = id único */ ?>
<form action="<?= url('/pesquisa') ?>" method="get" role="search" class="relative" data-pesquisa>
    <label for="<?= e($idPesquisa) ?>" class="sr-only">Pesquisar produtos</label>
    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-suave"><?= icone('pesquisa') ?></span>
    <input id="<?= e($idPesquisa) ?>" name="q" type="search" autocomplete="off" enterkeyhint="search"
           value="<?= e($_GET['q'] ?? '') ?>"
           placeholder="Pesquisar iPhone, PS5, TV…"
           class="campo h-12 pl-11 pr-14 sm:pr-28 rounded-2xl bg-superficie text-[15px]"
           role="combobox" aria-expanded="false" aria-controls="<?= e($idPesquisa) ?>-lista" aria-autocomplete="list"
           data-pesquisa-campo>
    <button type="submit" class="absolute right-1.5 top-1.5 h-9 inline-flex items-center rounded-xl bg-tinta px-3 sm:px-4 text-sm font-semibold text-superficie hover:bg-marca transition-colors" aria-label="Pesquisar">
        <span class="sm:hidden"><?= icone('pesquisa', 'h-5 w-5') ?></span><span class="hidden sm:inline">Pesquisar</span>
    </button>

    <div id="<?= e($idPesquisa) ?>-lista" class="absolute inset-x-0 top-[3.4rem] z-50 cartao shadow-flutuante overflow-hidden animate-entrar"
         role="listbox" data-pesquisa-lista hidden></div>
</form>
