<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Sobre a DIOTEKNO'; $introPagina = 'Uma loja de tecnologia angolana, feita para quem quer comprar online com a mesma confiança de uma loja física.'; require __DIR__ . '/_cabecalho.php'; ?>
    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr]">
        <div class="prosa">
            <p>A DIOTEKNO vende smartphones, computadores, televisores, electrodomésticos e acessórios das principais marcas, sempre novos, selados e com garantia oficial em Angola.</p>
            <p>Sabemos que comprar online em Angola ainda gera dúvidas: o produto é original? Chega mesmo? E se avariar? Por isso emitimos factura com NIF em todas as vendas, entregamos com a nossa própria frota em Luanda e respondemos no WhatsApp a qualquer pergunta antes e depois da compra.</p>
            <h2>O que nos distingue</h2>
            <ul>
                <li>Preços em Kwanzas, com IVA incluído e sem surpresas no checkout.</li>
                <li>Pagamento por Multicaixa Express, referência Multicaixa, transferência, Afrimoney, Unitel Money e cartões Visa e Mastercard.</li>
                <li>Entrega em 24 horas em Luanda e em 3 a 6 dias nas restantes províncias.</li>
                <li>Apoio técnico na escolha: diga-nos o que precisa e recomendamos.</li>
            </ul>
        </div>
        <aside class="cartao p-6 h-fit">
            <p class="font-display text-lg font-bold">Visite a loja</p>
            <p class="mt-2 text-sm text-suave"><?= e(MORADA_LOJA) ?></p>
            <p class="mt-1 text-sm text-suave">Segunda a sábado, das 8h às 19h.</p>
            <a href="<?= url('/contactos') ?>" class="btn-secundario mt-5 w-full">Ver contactos</a>
        </aside>
    </div>
</div>
