<?php
/** @var array $banners @var array $categoriasDestaque @var array $maisVendidos @var array $ofertas @var array $novidades @var array $marcas @var ?string $fimOfertas */
// Fundo de cada tema (sem fotografia): gradiente com um foco de luz atrás do produto
$temas = [
    'grafite' => ['bg-[radial-gradient(90%_120%_at_78%_45%,#1D3FD8_0%,#121A3F_42%,#0A0F22_100%)]', 'bg-[#2EC5F5]'],
    'azul'    => ['bg-[radial-gradient(90%_120%_at_78%_45%,#22C6F2_0%,#2346F5_45%,#1530B8_100%)]', 'bg-white'],
    'verde'   => ['bg-[radial-gradient(90%_120%_at_78%_45%,#1FB98B_0%,#0F5E47_48%,#08372A_100%)]', 'bg-[#7CF2C9]'],
    'coral'   => ['bg-[radial-gradient(90%_120%_at_78%_45%,#FF8A63_0%,#C9352B_48%,#7A1812_100%)]', 'bg-[#FFC2A8]'],
];
?>

<!-- ============ BANNER PRINCIPAL ============ -->
<section class="container pt-4 lg:pt-6" aria-roledescription="carrossel" aria-label="Destaques">
    <div class="relative" data-carrossel>
        <div class="flex overflow-x-auto snap-x snap-mandatory sem-scrollbar rounded-3xl" data-carrossel-pista>
            <?php foreach ($banners as $i => $b):
                [$fundo, $brilho] = $temas[$b['tema']] ?? $temas['grafite'];
                $foto  = $b['imagem'] ?? null;
                $temProduto = $b['produto_id'] && $b['produto_preco'] !== null;
                $desc  = $temProduto ? descontoPercent($b['produto_preco_anterior'] ? (float) $b['produto_preco_anterior'] : null, (float) $b['produto_preco']) : null;
                $carga = $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"';
            ?>
            <div class="relative w-full shrink-0 snap-start overflow-hidden text-white <?= $foto ? 'bg-grafite' : $fundo ?>"
                 role="group" aria-roledescription="destaque" aria-label="<?= $i + 1 ?> de <?= count($banners) ?>">

                <?php if ($foto): ?>
                <!-- Fotografia real a ocupar o banner; véu escuro do lado do texto para garantir leitura -->
                <img src="<?= asset('img/' . $foto) ?>" alt="" class="absolute inset-0 h-full w-full object-cover object-[70%_center]" width="2400" height="900" <?= $carga ?>>
                <div class="absolute inset-0 bg-gradient-to-t from-[#060A18]/90 via-[#060A18]/40 to-transparent md:bg-gradient-to-r md:from-[#060A18]/85 md:via-[#060A18]/45 md:to-transparent" aria-hidden="true"></div>
                <?php else: ?>
                <!-- Profundidade: anéis suaves e brilho, sem grelha -->
                <div class="pointer-events-none absolute -right-24 top-1/2 h-[560px] w-[560px] -translate-y-1/2 rounded-full border border-white/10 md:right-[4%]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-8 top-1/2 h-[400px] w-[400px] -translate-y-1/2 rounded-full border border-white/[0.07] md:right-[12%]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -left-32 -bottom-40 h-96 w-96 rounded-full bg-white/[0.06] blur-3xl" aria-hidden="true"></div>
                <?php endif; ?>

                <div class="relative grid items-center gap-6 px-6 pt-8 pb-16 sm:px-10 sm:py-12 lg:px-14 <?= $foto ? 'min-h-[440px] sm:min-h-[420px] lg:min-h-[460px] content-end md:content-center md:grid-cols-2' : 'md:grid-cols-[1.1fr_1fr] lg:min-h-[460px]' ?>">
                    <div class="max-w-xl">
                        <?php if ($desc): ?>
                        <span class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs sm:text-sm font-semibold backdrop-blur-sm ring-1 ring-white/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#FF8A63]"></span> Poupe <?= $desc ?>% por tempo limitado
                        </span>
                        <?php endif; ?>
                        <h2 class="font-display text-[2rem] leading-[1.02] sm:text-5xl lg:text-[3.75rem] font-extrabold tracking-tight [text-wrap:balance]"><?= e($b['titulo']) ?></h2>
                        <?php if ($b['subtitulo']): ?>
                        <p class="mt-4 text-[15px] sm:text-lg leading-relaxed text-white/80 max-w-md"><?= e($b['subtitulo']) ?></p>
                        <?php endif; ?>
                        <?php if ($b['link']): ?>
                        <a href="<?= url($b['link']) ?>" class="btn group mt-6 sm:mt-8 h-12 px-6 gap-2 rounded-full bg-white text-grafite font-semibold shadow-lg shadow-black/20 hover:bg-white/90">
                            <?= e($b['texto_botao'] ?: 'Ver mais') ?>
                            <?= icone('seta-dir', 'h-4 w-4 transition-transform group-hover:translate-x-0.5') ?>
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($temProduto && !$foto): ?>
                    <div class="relative mx-auto w-full max-w-[250px] sm:max-w-xs md:max-w-[360px] md:justify-self-end md:mr-6">
                        <div class="absolute -inset-6 rounded-full <?= $brilho ?> opacity-30 blur-3xl" aria-hidden="true"></div>
                        <!-- Fotografia oficial num cartão branco: as fotos dos fabricantes vêm em fundo branco -->
                        <div class="relative rotate-2 rounded-[2rem] bg-white p-5 sm:p-7 shadow-[0_40px_80px_-20px_rgba(0,0,0,0.55)] ring-1 ring-white/40">
                            <img src="<?= imagemProduto(['imagem' => $b['produto_imagem'], 'categoria_slug' => $b['categoria_slug']]) ?>" alt="<?= e($b['produto_nome']) ?>"
                                 class="-rotate-2 w-full aspect-square object-contain" width="420" height="420" <?= $carga ?>>
                        </div>
                        <?php require __DIR__ . '/_banner-preco.php'; ?>
                    </div>
                    <?php elseif ($temProduto): ?>
                    <div class="absolute bottom-20 right-8 lg:right-14 hidden md:block">
                        <?php require __DIR__ . '/_banner-preco.php'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($banners) > 1): ?>
        <div class="absolute bottom-5 left-6 sm:left-10 lg:left-14 flex items-center gap-2" data-carrossel-pontos>
            <?php foreach ($banners as $i => $b): ?>
            <button type="button" class="h-1.5 rounded-full bg-white/35 transition-all w-1.5 hover:bg-white/60 aria-[current=true]:w-8 aria-[current=true]:bg-white"
                    data-ir="<?= $i ?>" aria-label="Ir para destaque <?= $i + 1 ?>" <?= $i === 0 ? 'aria-current="true"' : '' ?>></button>
            <?php endforeach; ?>
        </div>
        <div class="absolute bottom-4 right-4 sm:right-6 hidden md:flex gap-2">
            <button type="button" class="btn-icone rounded-full bg-white/15 text-white ring-1 ring-white/20 backdrop-blur-md hover:bg-white/25" data-anterior aria-label="Destaque anterior"><?= icone('seta-esq') ?></button>
            <button type="button" class="btn-icone rounded-full bg-white/15 text-white ring-1 ring-white/20 backdrop-blur-md hover:bg-white/25" data-seguinte aria-label="Destaque seguinte"><?= icone('seta-dir') ?></button>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ CATEGORIAS EM DESTAQUE ============ -->
<section class="container mt-10" aria-labelledby="t-categorias">
    <div class="mb-4 flex items-end justify-between gap-4">
        <h2 id="t-categorias" class="titulo-seccao">Compre por categoria</h2>
        <a href="<?= url('/categorias') ?>" class="link text-sm">Todas as categorias</a>
    </div>
    <div class="faixa lg:grid lg:grid-cols-5 lg:gap-4">
        <?php foreach ($categoriasDestaque as $c): ?>
        <a href="<?= url('/categoria/' . $c['slug']) ?>" class="group w-36 sm:w-44 lg:w-auto cartao p-3 hover:border-tinta/25 transition-colors">
            <div class="aspect-[4/3] rounded-xl bg-white flex items-center justify-center overflow-hidden">
                <img src="<?= imagemCategoria($c['slug']) ?>" alt="" class="h-[85%] w-[85%] object-contain transition-transform duration-300 group-hover:scale-105" loading="lazy" width="200" height="150">
            </div>
            <p class="mt-3 font-display text-base font-bold"><?= e($c['nome']) ?></p>
            <p class="text-xs text-suave num"><?= (int) $c['total_produtos'] ?> produtos</p>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ PROMOÇÕES DO DIA ============ -->
<?php if ($ofertas): ?>
<section class="mt-12 bg-coral-suave/60 border-y border-coral/15 py-8" aria-labelledby="t-ofertas">
    <div class="container">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h2 id="t-ofertas" class="titulo-seccao">Promoções do dia</h2>
                <?php if ($fimOfertas): ?>
                <p class="inline-flex items-center gap-2 rounded-xl bg-coral px-3 h-9 text-sm font-semibold text-white" data-contagem="<?= e($fimOfertas) ?>">
                    <?= icone('relogio', 'h-4 w-4') ?> Terminam em <span class="num font-display font-bold" data-contagem-texto>--:--:--</span>
                </p>
                <?php endif; ?>
            </div>
            <a href="<?= url('/promocoes') ?>" class="link text-sm !text-coral">Ver todas as promoções</a>
        </div>
        <div class="faixa sm:grid sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-4">
            <?php foreach ($ofertas as $p): ?>
            <div class="w-[46%] sm:w-auto"><?php require RAIZ . '/app/Views/partials/produto-card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ MAIS VENDIDOS ============ -->
<section class="container mt-12" aria-labelledby="t-vendidos">
    <div class="mb-4 flex items-end justify-between gap-4">
        <h2 id="t-vendidos" class="titulo-seccao">Os mais vendidos</h2>
        <a href="<?= url('/produtos?ordem=mais-vendidos') ?>" class="link text-sm">Ver mais</a>
    </div>
    <div class="faixa sm:grid sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-4">
        <?php foreach ($maisVendidos as $p): ?>
        <div class="w-[46%] sm:w-auto"><?php require RAIZ . '/app/Views/partials/produto-card.php'; ?></div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ MARCAS PARCEIRAS ============ -->
<section class="container mt-12" aria-labelledby="t-marcas">
    <div class="mb-4 flex items-end justify-between gap-4">
        <h2 id="t-marcas" class="titulo-seccao">Marcas oficiais</h2>
        <a href="<?= url('/marcas') ?>" class="link text-sm">Todas as marcas</a>
    </div>
    <ul class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 sm:gap-3">
        <?php foreach ($marcas as $m): ?>
        <li>
            <a href="<?= url('/marca/' . $m['slug']) ?>" class="flex h-16 items-center justify-center cartao font-display text-lg font-bold text-tinta/70 hover:text-tinta hover:border-tinta/25 transition-colors">
                <?= e($m['nome']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>

<!-- ============ NOVIDADES ============ -->
<?php if ($novidades): ?>
<section class="container mt-12 mb-14" aria-labelledby="t-novidades">
    <div class="mb-4 flex items-end justify-between gap-4">
        <h2 id="t-novidades" class="titulo-seccao">Acabados de chegar</h2>
        <a href="<?= url('/novidades') ?>" class="link text-sm">Ver novidades</a>
    </div>
    <div class="faixa sm:grid sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-4">
        <?php foreach ($novidades as $p): ?>
        <div class="w-[46%] sm:w-auto"><?php require RAIZ . '/app/Views/partials/produto-card.php'; ?></div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============ ATENDIMENTO ============ -->
<section class="container mb-14">
    <div class="cartao p-6 sm:p-8 flex flex-col md:flex-row md:items-center gap-5 justify-between">
        <div>
            <h2 class="font-display text-xl sm:text-2xl font-bold">Não sabe qual escolher?</h2>
            <p class="mt-1 text-suave max-w-xl">Diga-nos para que precisa e o orçamento. Um técnico responde no WhatsApp com 2 ou 3 opções.</p>
        </div>
        <a href="<?= e(linkWhatsapp('Olá! Preciso de ajuda para escolher um produto.')) ?>" target="_blank" rel="noopener" class="btn-whatsapp h-12 px-6 shrink-0">
            <?= icone('whatsapp') ?> Pedir recomendação
        </a>
    </div>
</section>
