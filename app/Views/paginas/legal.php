<?php /** @var string $tipo 'privacidade'|'termos' */ ?>
<div class="container pt-6 pb-14">
    <?php if ($tipo === 'privacidade'): ?>
        <?php $tituloPagina = 'Política de privacidade'; $introPagina = 'Como recolhemos, usamos e protegemos os seus dados pessoais.'; require __DIR__ . '/_cabecalho.php'; ?>
        <div class="prosa">
            <p>A DIOTEKNO trata os dados pessoais de acordo com a Lei n.º 22/11 de Protecção de Dados Pessoais da República de Angola.</p>
            <h2>Que dados recolhemos</h2>
            <p>Nome, email, telefone, NIF e morada de entrega, quando cria conta ou faz uma compra; e dados de navegação técnicos necessários ao funcionamento do site (por exemplo, o carrinho).</p>
            <h2>Para que os usamos</h2>
            <ul><li>Processar e entregar encomendas e emitir facturas.</li><li>Prestar apoio ao cliente e garantia.</li><li>Enviar promoções, apenas se tiver aceitado.</li></ul>
            <h2>Os seus direitos</h2>
            <p>Pode pedir acesso, correcção ou eliminação dos seus dados a qualquer momento através de <a href="mailto:<?= e(EMAIL_LOJA) ?>"><?= e(EMAIL_LOJA) ?></a>.</p>
            <p class="text-sm text-suave">Texto-base a validar pelo departamento jurídico do cliente antes da publicação.</p>
        </div>
    <?php else: ?>
        <?php $tituloPagina = 'Termos e condições'; $introPagina = 'As regras que se aplicam às compras feitas na loja online DIOTEKNO.'; require __DIR__ . '/_cabecalho.php'; ?>
        <div class="prosa">
            <h2>Preços e disponibilidade</h2>
            <p>Os preços estão em Kwanzas e incluem IVA. A encomenda só é confirmada após a validação do pagamento e sujeita à disponibilidade de stock.</p>
            <h2>Pagamentos</h2>
            <p>Aceitamos Multicaixa Express, referência Multicaixa, transferência bancária, Afrimoney, Unitel Money e cartões Visa e Mastercard. As referências Multicaixa expiram ao fim de 48 horas.</p>
            <h2>Entregas</h2>
            <p>Os prazos indicados contam a partir da confirmação do pagamento, em dias úteis. Ver <a href="<?= url('/entregas') ?>">entregas e prazos</a>.</p>
            <h2>Trocas e garantia</h2>
            <p>Produtos com defeito de fabrico comunicados nos primeiros 7 dias são trocados. Após esse prazo aplica-se a garantia oficial do fabricante.</p>
            <p class="text-sm text-suave">Texto-base a validar pelo departamento jurídico do cliente antes da publicação.</p>
        </div>
    <?php endif; ?>
</div>
