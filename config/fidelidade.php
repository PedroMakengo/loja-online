<?php
// =====================================================================
// config/fidelidade.php — Regras do programa de pontos DIOTEKNO
// =====================================================================

return [
    'activo'               => true,
    'kz_por_ponto'         => 1000,   // ganha 1 ponto por cada 1 000 Kz pagos (sem portes)
    'valor_ponto'          => 10,     // 1 ponto vale 10 Kz de desconto (devolve 1%)
    'minimo_resgate'       => 500,    // só se pode usar a partir de 500 pontos (5 000 Kz)
    'maximo_percentagem'   => 20,     // os pontos pagam no máximo 20% dos produtos
    'pontos_avaliacao'     => 50,     // por cada avaliação aprovada
    'bonus_primeira_compra' => 200,   // oferta na primeira compra paga
];
