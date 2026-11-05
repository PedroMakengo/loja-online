<?php
/** Layout do painel. @var string $conteudo @var array $seo */
use Core\AdminAuth;
use Core\Database;
use Core\Flash;

$adminSessao = AdminAuth::admin();
$mensagensFlash = Flash::ler();

// Contadores do menu: só o que exige acção
$db = Database::ligacao();
$contadores = $adminSessao ? $db->query(
    "SELECT
        (SELECT COUNT(*) FROM pedidos WHERE estado IN ('pago','em_preparacao')) AS a_preparar,
        (SELECT COUNT(*) FROM pagamentos pg JOIN pedidos p ON p.id = pg.pedido_id
          WHERE pg.metodo = 'transferencia' AND pg.estado = 'pendente' AND pg.comprovativo IS NOT NULL AND p.estado = 'pendente') AS transferencias,
        (SELECT COUNT(*) FROM produtos WHERE estado = 'activo' AND stock <= stock_minimo) AS stock_baixo,
        (SELECT COUNT(*) FROM contactos WHERE lido = 0) AS mensagens,
        (SELECT COUNT(*) FROM avaliacoes WHERE estado = 'pendente') AS avaliacoes"
)->fetch() : [];

$menuAdmin = [
    ['Geral', [
        ['/admin', 'casa', 'Resumo', 'painel', null],
        ['/admin/relatorios', 'grelha', 'Relatórios', 'relatorios', null],
    ]],
    ['Vendas', [
        ['/admin/pedidos', 'camiao', 'Pedidos', 'pedidos', ($contadores['a_preparar'] ?? 0) + ($contadores['transferencias'] ?? 0)],
        ['/admin/clientes', 'conta', 'Clientes', 'clientes', null],
        ['/admin/mensagens', 'email', 'Mensagens', 'mensagens', $contadores['mensagens'] ?? 0],
        ['/admin/avaliacoes', 'estrela', 'Avaliações', 'clientes', $contadores['avaliacoes'] ?? 0],
    ]],
    ['Catálogo', [
        ['/admin/produtos', 'loja', 'Produtos', 'produtos', $contadores['stock_baixo'] ?? 0],
        ['/admin/c/categorias', 'filtro', 'Categorias', 'catalogo', null],
        ['/admin/c/marcas', 'estrela', 'Marcas', 'catalogo', null],
    ]],
    ['Marketing e entregas', [
        ['/admin/c/banners', 'sol', 'Banners', 'marketing', null],
        ['/admin/c/cupoes', 'cartao', 'Cupões', 'marketing', null],
        ['/admin/c/zonas_entrega', 'localizacao', 'Zonas de entrega', 'marketing', null],
        ['/admin/marketing/carrinhos', 'carrinho', 'Carrinhos abandonados', 'marketing', null],
        ['/admin/newsletter', 'email', 'Newsletter', 'marketing', null],
        ['/admin/blog', 'menu', 'Blog', 'marketing', null],
    ]],
    ['Sistema', [
        ['/admin/administradores', 'escudo', 'Administradores', 'admins', null],
    ]],
];
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($seo['titulo']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= \Core\Csrf::token() ?>">
    <link rel="icon" href="<?= asset('img/favicon-32.png') ?>" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script>(function(){try{var t=localStorage.getItem('dtk-tema');if(t==='escuro'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}})();</script>
</head>
<body class="min-h-screen bg-fundo" data-base="<?= e(BASE_PATH) ?>" data-sessao="0">
<?php if ($adminSessao): ?>
<div class="lg:grid lg:grid-cols-[248px_1fr] print:block min-h-screen">
    <!-- Barra lateral (gaveta no mobile) -->
    <div id="menu-admin" class="fixed inset-0 z-[60] hidden lg:static lg:z-auto lg:block lg:bg-superficie lg:border-r lg:border-linha print:!hidden" data-gaveta>
        <div class="absolute inset-0 bg-grafite/50 lg:hidden" data-fechar></div>
        <aside class="absolute inset-y-0 left-0 w-72 lg:w-auto lg:sticky lg:top-0 lg:h-screen flex flex-col bg-superficie border-r border-linha lg:border-r-0">
            <div class="flex h-16 items-center justify-between px-4 border-b border-linha">
                <a href="<?= url('/admin') ?>" class="flex items-center gap-2">
                    <?php $tamanho = 'sm'; require RAIZ . '/app/Views/partials/logo.php'; ?>
                    <span class="rounded-md bg-elevado px-1.5 py-0.5 text-[11px] font-semibold text-suave">Painel</span>
                </a>
                <button type="button" class="btn-icone lg:hidden" data-fechar aria-label="Fechar menu"><?= icone('fechar') ?></button>
            </div>
            <nav class="flex-1 overflow-y-auto p-3 space-y-5" aria-label="Painel">
                <?php foreach ($menuAdmin as [$grupo, $itens]):
                    $visiveis = array_filter($itens, fn ($i) => AdminAuth::pode($i[3]));
                    if (!$visiveis) continue; ?>
                <div>
                    <p class="px-3 pb-1.5 text-xs font-semibold text-suave"><?= $grupo ?></p>
                    <?php foreach ($visiveis as [$href, $ic, $rotulo, , $contador]):
                        $activo = $href === '/admin' ? rotaActiva('/admin') && !preg_match('#^/admin/.+#', strtok(str_replace(BASE_PATH, '', $_SERVER['REQUEST_URI']), '?')) : rotaActiva($href); ?>
                    <a href="<?= url($href) ?>" <?= $activo ? 'aria-current="page"' : '' ?>
                       class="flex items-center gap-3 rounded-xl px-3 h-10 text-sm font-medium <?= $activo ? 'bg-tinta text-superficie' : 'text-tinta hover:bg-elevado' ?>">
                        <?= icone($ic, 'h-[18px] w-[18px]') ?><span class="flex-1"><?= $rotulo ?></span>
                        <?php if ($contador): ?><span class="min-w-[1.4rem] rounded-full px-1.5 text-center text-[11px] font-bold leading-5 num <?= $activo ? 'bg-superficie text-tinta' : 'bg-coral text-white' ?>"><?= (int) $contador ?></span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </nav>
            <div class="border-t border-linha p-3">
                <a href="<?= url('/') ?>" target="_blank" class="flex items-center gap-3 rounded-xl px-3 h-10 text-sm text-suave hover:bg-elevado"><?= icone('loja', 'h-[18px] w-[18px]') ?> Ver a loja</a>
            </div>
        </aside>
    </div>

    <div class="min-w-0 flex flex-col">
        <header class="print:hidden sticky top-0 z-40 flex h-16 items-center gap-2 border-b border-linha bg-fundo/95 backdrop-blur px-4 lg:px-8">
            <button type="button" class="btn-icone -ml-2 lg:hidden" data-abrir="menu-admin" aria-label="Abrir menu"><?= icone('menu', 'h-6 w-6') ?></button>
            <form action="<?= url('/admin/pedidos') ?>" method="get" class="relative hidden sm:block w-full max-w-sm" role="search">
                <label for="busca-admin" class="sr-only">Procurar pedido ou cliente</label>
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-suave"><?= icone('pesquisa', 'h-4 w-4') ?></span>
                <input id="busca-admin" name="q" placeholder="Nº do pedido, nome ou telefone" class="campo h-10 pl-9">
            </form>
            <div class="ml-auto flex items-center gap-1">
                <button type="button" class="btn-icone" data-tema aria-label="Alternar modo claro/escuro">
                    <span class="dark:hidden"><?= icone('lua') ?></span><span class="hidden dark:inline"><?= icone('sol') ?></span>
                </button>
                <div class="relative" data-dropdown>
                    <button type="button" class="flex items-center gap-2 rounded-xl px-2 h-10 hover:bg-elevado" data-dropdown-botao aria-expanded="false">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-marca text-sm font-bold text-white dark:text-grafite"><?= e(mb_strtoupper(mb_substr($adminSessao['nome'], 0, 1))) ?></span>
                        <span class="hidden sm:block text-left leading-tight">
                            <span class="block text-sm font-semibold"><?= e(explode(' ', $adminSessao['nome'])[0]) ?></span>
                            <span class="block text-xs text-suave"><?= e(AdminAuth::PAPEIS[$adminSessao['papel']]) ?></span>
                        </span>
                    </button>
                    <div class="absolute right-0 top-12 w-56 cartao shadow-flutuante p-2" data-dropdown-painel hidden>
                        <a href="<?= url('/admin/perfil') ?>" class="block rounded-lg px-3 py-2 text-sm hover:bg-elevado">O meu perfil e senha</a>
                        <form action="<?= url('/admin/sair') ?>" method="post"><?= \Core\Csrf::campo() ?>
                            <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-coral hover:bg-elevado">Terminar sessão</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <main id="conteudo" class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
            <?php foreach (array_filter($mensagensFlash, fn ($m) => $m['tipo'] === 'importante') as $m): ?>
            <div class="mb-6 rounded-xl border border-aviso/40 bg-aviso/10 p-4 text-sm font-medium" role="alert"><?= e($m['mensagem']) ?></div>
            <?php endforeach; $mensagensFlash = array_values(array_filter($mensagensFlash, fn ($m) => $m['tipo'] !== 'importante')); ?>
            <?= $conteudo ?>
        </main>
    </div>
</div>
<?php else: ?>
    <main id="conteudo"><?= $conteudo ?></main>
<?php endif; ?>

<div id="toasts" class="fixed inset-x-0 top-3 z-[90] flex flex-col items-center gap-2 px-4 pointer-events-none" aria-live="polite"></div>
<?php if ($mensagensFlash): ?>
<script type="application/json" id="flash-inicial"><?= json_encode($mensagensFlash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
