<div class="container pt-10 pb-16">
    <div class="mx-auto max-w-xl text-center">
        <p class="font-display text-[7rem] leading-none font-extrabold text-marca num">404</p>
        <h1 class="mt-2 font-display text-2xl sm:text-3xl font-extrabold">Esta página não existe</h1>
        <p class="mt-2 text-suave">O endereço pode estar errado ou o produto já não está à venda. Pesquise o que procura:</p>
        <div class="mt-6 text-left"><?php $idPesquisa = 'pesquisa-404'; require RAIZ . '/app/Views/partials/pesquisa.php'; ?></div>
        <a href="<?= url('/') ?>" class="btn-secundario mt-6">Voltar à página inicial</a>
    </div>
</div>
