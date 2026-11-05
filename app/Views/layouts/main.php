<?php
/** @var string $conteudo  @var array $seo */
use App\Models\Categoria;
use App\Models\Carrinho;
use Core\Auth;
use Core\Flash;

$menuCategorias = (new Categoria())->todasActivas();
$qtdCarrinho    = (new Carrinho())->contar();
$clienteSessao  = Auth::cliente();
$mensagensFlash = Flash::ler();
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($seo['titulo']) ?></title>
    <meta name="description" content="<?= e($seo['descricao']) ?>">
    <link rel="canonical" href="<?= e($seo['canonical']) ?>">
    <?php if ($seo['noindex']): ?><meta name="robots" content="noindex, follow"><?php endif; ?>

    <!-- Open Graph / Redes sociais -->
    <meta property="og:type" content="<?= e($seo['tipo']) ?>">
    <meta property="og:site_name" content="<?= e(NOME_SITE) ?>">
    <meta property="og:locale" content="pt_AO">
    <meta property="og:title" content="<?= e($seo['titulo']) ?>">
    <meta property="og:description" content="<?= e($seo['descricao']) ?>">
    <meta property="og:url" content="<?= e($seo['canonical']) ?>">
    <meta property="og:image" content="<?= e($seo['imagem']) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <meta name="theme-color" content="#F3F5FA" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0C101F" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="<?= \Core\Csrf::token() ?>">
    <link rel="icon" href="<?= asset('img/favicon-32.png') ?>" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
    <link rel="alternate" type="application/rss+xml" title="<?= e(NOME_SITE) ?> — Blog" href="<?= url('/blog/feed.xml') ?>">

    <link rel="preload" href="<?= BASE_PATH ?>/assets/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= BASE_PATH ?>/assets/fonts/bricolage-grotesque-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">

    <!-- Aplica o tema antes de pintar a página (evita o "flash" claro) -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('dtk-tema');
                if (t === 'escuro' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <?php foreach ($seo['jsonld'] as $bloco): ?>
    <script type="application/ld+json"><?= json_encode($bloco, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endforeach; ?>
</head>
<body class="min-h-screen flex flex-col" data-base="<?= e(BASE_PATH) ?>" data-sessao="<?= $clienteSessao ? '1' : '0' ?>">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] btn-primario">Saltar para o conteúdo</a>

    <?php require RAIZ . '/app/Views/partials/header.php'; ?>

    <main id="conteudo" class="flex-1 pb-20 lg:pb-0">
        <?= $conteudo ?>
    </main>

    <?php require RAIZ . '/app/Views/partials/footer.php'; ?>
    <?php require RAIZ . '/app/Views/partials/nav-mobile.php'; ?>
    <?php require RAIZ . '/app/Views/partials/carrinho-gaveta.php'; ?>
    <?php require RAIZ . '/app/Views/partials/carrinho-adicionado-painel.php'; ?>

    <!-- Barra de comparação -->
    <?php $totalComparar = count(\App\Servicos\Comparador::ids()); ?>
    <div class="barra-comparar fixed left-4 bottom-[5.5rem] lg:bottom-6 lg:left-1/2 lg:-translate-x-1/2 z-40 <?= $totalComparar && !rotaActiva('/comparar') ? '' : 'hidden' ?>" data-barra-comparar>
        <div class="flex items-center gap-1 rounded-2xl bg-grafite p-1.5 pl-4 text-white shadow-flutuante">
            <?= icone('comparar', 'h-5 w-5 text-white/70') ?>
            <span class="text-sm font-medium"><span data-comparar-total class="num"><?= $totalComparar ?></span><span class="hidden sm:inline"> para comparar</span></span>
            <a href="<?= url('/comparar') ?>" class="ml-2 inline-flex h-9 items-center rounded-xl bg-white px-4 text-sm font-semibold text-grafite hover:bg-white/90">Comparar</a>
            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white/70 hover:text-white" data-limpar-comparar aria-label="Limpar comparação"><?= icone('fechar', 'h-4 w-4') ?></button>
        </div>
    </div>

    <!-- WhatsApp flutuante -->
    <a href="<?= e(linkWhatsapp('Olá DIOTEKNO! Preciso de ajuda com uma compra.')) ?>" target="_blank" rel="noopener"
       class="botao-whatsapp fixed right-4 bottom-24 lg:bottom-6 z-40 inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#1FAF5A] text-white shadow-flutuante hover:bg-[#178F49] transition-colors"
       aria-label="Falar connosco no WhatsApp">
        <?= icone('whatsapp', 'h-7 w-7') ?>
    </a>

    <!-- Notificações -->
    <div id="toasts" class="fixed inset-x-0 top-3 z-[90] flex flex-col items-center gap-2 px-4 pointer-events-none" aria-live="polite"></div>

    <?php if ($mensagensFlash): ?>
    <script type="application/json" id="flash-inicial"><?= json_encode($mensagensFlash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <?php endif; ?>
    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
