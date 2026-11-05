<?php /** Cabeçalho simples das páginas de conteúdo. $tituloPagina, $introPagina */ ?>
<header class="mb-8 max-w-3xl">
    <h1 class="font-display text-3xl sm:text-4xl font-extrabold"><?= e($tituloPagina) ?></h1>
    <?php if (!empty($introPagina)): ?><p class="mt-3 text-lg text-suave"><?= e($introPagina) ?></p><?php endif; ?>
</header>
