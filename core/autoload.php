<?php
// =====================================================================
// core/autoload.php — Autoload PSR-4 simples (sem Composer)
//   Core\*  -> core/
//   App\*   -> app/
// =====================================================================

spl_autoload_register(function (string $classe): void {
    $mapa = [
        'Core\\' => RAIZ . '/core/',
        'App\\'  => RAIZ . '/app/',
    ];

    foreach ($mapa as $prefixo => $pasta) {
        if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
            continue;
        }
        $relativo = substr($classe, strlen($prefixo));
        $ficheiro = $pasta . str_replace('\\', '/', $relativo) . '.php';
        if (is_file($ficheiro)) {
            require $ficheiro;
        }
        return;
    }
});

require RAIZ . '/core/helpers.php';
