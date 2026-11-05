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

    <header class="border-b border-linha bg-superficie">
        <div class="container flex h-16 items-center justify-between gap-4">
            <a href="<?= url('/') ?>" aria-label="<?= e(NOME_SITE) ?> — página inicial"><?php $tamanho = 'md'; require RAIZ . '/app/Views/partials/logo.php'; ?></a>
            <p class="flex items-center gap-2 text-sm font-medium text-sucesso"><?= icone('escudo', 'h-5 w-5') ?> <span>Compra segura</span></p>
            <div class="flex items-center gap-1">
                <button type="button" class="btn-icone" data-tema aria-label="Alternar modo claro/escuro">
                    <span class="dark:hidden"><?= icone('lua') ?></span><span class="hidden dark:inline"><?= icone('sol') ?></span>
                </button>
                <a href="<?= url('/carrinho') ?>" class="btn-fantasma h-10 px-3 hidden sm:inline-flex"><?= icone('seta-esq', 'h-4 w-4') ?> Voltar ao carrinho</a>
            </div>
        </div>
    </header>

    <main id="conteudo" class="flex-1">
        <?= $conteudo ?>
    </main>

    <footer class="border-t border-linha py-6 text-center text-xs text-suave">
        <p>Dúvidas na compra? <a href="<?= e(linkWhatsapp('Olá! Tenho uma dúvida no checkout.')) ?>" target="_blank" rel="noopener" class="link">Fale connosco no WhatsApp</a> ou ligue <span class="num"><?= e(TELEFONE_LOJA) ?></span>.</p>
        <p class="mt-1">© <?= date('Y') ?> <?= e(NOME_SITE) ?>. Preços em Kwanzas com IVA incluído.</p>
    </footer>

    <div id="toasts" class="fixed inset-x-0 top-3 z-[90] flex flex-col items-center gap-2 px-4 pointer-events-none" aria-live="polite"></div>
    <?php if ($mensagensFlash): ?>
    <script type="application/json" id="flash-inicial"><?= json_encode($mensagensFlash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <?php endif; ?>
    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
