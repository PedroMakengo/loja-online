<?php /** Gaveta lateral do carrinho — conteúdo carregado por AJAX (/carrinho/mini) */ ?>
<div class="fixed inset-0 z-[70] hidden" id="carrinho" data-gaveta role="dialog" aria-modal="true" aria-labelledby="carrinho-titulo">
    <div class="absolute inset-0 bg-grafite/50" data-fechar></div>
    <aside class="absolute inset-y-0 right-0 w-full max-w-md bg-superficie flex flex-col animate-entrar">
        <div class="flex h-16 items-center justify-between px-4 border-b border-linha">
            <h2 id="carrinho-titulo" class="font-display text-lg font-bold">O seu carrinho</h2>
            <button type="button" class="btn-icone" data-fechar aria-label="Fechar carrinho"><?= icone('fechar') ?></button>
        </div>
        <div class="flex-1 overflow-y-auto" data-carrinho-conteudo>
            <div class="p-4 space-y-3">
                <div class="skeleton h-20"></div><div class="skeleton h-20"></div>
            </div>
        </div>
    </aside>
</div>
