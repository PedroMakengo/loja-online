<?php /** @var array $menuCategorias */ ?>
<!-- Benefícios -->
<section class="border-t border-linha bg-superficie" aria-label="Porque comprar na DIOTEKNO">
    <div class="container grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-6 py-8">
        <?php
        $beneficios = [
            ['cartao',  'Multicaixa Express', 'Pague no telemóvel, sem cartão.'],
            ['camiao',  'Entrega nacional',   '24 h em Luanda, 3 a 6 dias nas províncias.'],
            ['suporte', 'Suporte online',     'WhatsApp de segunda a sábado.'],
            ['escudo',  'Garantia oficial',   'Produtos novos e selados, com factura.'],
        ];
        foreach ($beneficios as [$ic, $t, $d]): ?>
        <div class="flex items-start gap-3">
            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"><?= icone($ic, 'h-6 w-6') ?></span>
            <div>
                <p class="text-sm font-bold"><?= $t ?></p>
                <p class="text-xs sm:text-sm text-suave"><?= $d ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="bg-grafite text-white/75">
    <div class="container py-12 grid gap-10 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <a href="<?= url('/') ?>" class="inline-block" aria-label="<?= e(NOME_SITE) ?>">
                <?php $tamanho = 'md'; $claro = true; require __DIR__ . '/logo.php'; ?>
            </a>
            <p class="mt-4 max-w-sm text-sm leading-6"><?= e(SLOGAN_SITE) ?>.</p>
            <ul class="mt-6 space-y-2 text-sm">
                <li class="flex gap-2"><?= icone('localizacao', 'h-5 w-5 shrink-0 text-white/50') ?><?= e(MORADA_LOJA) ?></li>
                <li class="flex gap-2"><?= icone('telefone', 'h-5 w-5 shrink-0 text-white/50') ?><a href="tel:<?= e(str_replace(' ', '', TELEFONE_LOJA)) ?>" class="hover:text-white num"><?= e(TELEFONE_LOJA) ?></a></li>
                <li class="flex gap-2"><?= icone('email', 'h-5 w-5 shrink-0 text-white/50') ?><a href="mailto:<?= e(EMAIL_LOJA) ?>" class="hover:text-white"><?= e(EMAIL_LOJA) ?></a></li>
            </ul>
        </div>

        <nav class="lg:col-span-2" aria-label="Categorias">
            <p class="mb-3 text-sm font-semibold text-white">Categorias</p>
            <ul class="space-y-2 text-sm">
                <?php foreach (array_slice($menuCategorias, 0, 7) as $c): ?>
                <li><a href="<?= url('/categoria/' . $c['slug']) ?>" class="hover:text-white"><?= e($c['nome']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <nav class="lg:col-span-2" aria-label="Ajuda">
            <p class="mb-3 text-sm font-semibold text-white">Ajuda</p>
            <ul class="space-y-2 text-sm">
                <li><a href="<?= url('/blog') ?>" class="hover:text-white">Guias de compra</a></li>
                <li><a href="<?= url('/entregas') ?>" class="hover:text-white">Entregas e prazos</a></li>
                <li><a href="<?= url('/faq') ?>" class="hover:text-white">Perguntas frequentes</a></li>
                <li><a href="<?= url('/contactos') ?>" class="hover:text-white">Contactos</a></li>
                <li><a href="<?= url('/termos') ?>" class="hover:text-white">Termos e condições</a></li>
                <li><a href="<?= url('/privacidade') ?>" class="hover:text-white">Política de privacidade</a></li>
            </ul>
        </nav>

        <div class="lg:col-span-4">
            <p class="text-sm font-semibold text-white">Receba as promoções primeiro</p>
            <p class="mt-1 text-sm">Uma mensagem por semana, com as melhores ofertas. Pode sair quando quiser.</p>
            <form action="<?= url('/newsletter') ?>" method="post" class="mt-4 flex gap-2" data-ajax-form>
                <?= \Core\Csrf::campo() ?>
                <input type="hidden" name="origem" value="rodape">
                <label for="news-rodape" class="sr-only">Email</label>
                <input id="news-rodape" type="email" name="email" required placeholder="O seu email"
                       class="h-11 min-w-0 flex-1 rounded-xl border border-white/15 bg-white/5 px-3.5 text-sm text-white placeholder:text-white/40 focus:border-white/50 focus:outline-none">
                <button class="btn h-11 bg-white text-grafite hover:bg-white/90">Subscrever</button>
            </form>

            <p class="mt-8 mb-3 text-sm font-semibold text-white">Formas de pagamento</p>
            <ul class="flex flex-wrap gap-2 text-xs font-semibold">
                <?php foreach (['Multicaixa Express', 'Referência Multicaixa', 'Transferência', 'Afrimoney', 'Unitel Money', 'Visa', 'Mastercard'] as $mp): ?>
                <li class="rounded-lg border border-white/15 px-2.5 py-1.5"><?= $mp ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container flex flex-col sm:flex-row gap-2 justify-between py-5 text-xs text-white/50">
            <p>© <?= date('Y') ?> <?= e(NOME_SITE) ?>. NIF <?= e(NIF_LOJA) ?>. Preços em Kwanzas com IVA incluído.</p>
            <p>Luanda, Angola</p>
        </div>
    </div>
</footer>
