<?php
// =====================================================================
// config/marketing.php — Carrinho abandonado e newsletter
// =====================================================================

return [
    // Carrinho abandonado (só clientes com conta e lembretes activos)
    'lembrete1_horas'     => 1,      // primeiro email: 1 hora sem mexer no carrinho
    'lembrete2_horas'     => 24,     // segundo email: 24 horas depois do primeiro
    'carrinho_max_dias'   => 7,      // carrinhos mais antigos já não recebem lembretes
    'carrinho_minimo'     => 20000,  // valor mínimo (Kz) para valer a pena lembrar
    'cupao_lembrete2'     => 5,      // % de desconto no 2.º lembrete (0 = sem cupão)
    'cupao_validade_horas'=> 48,

    // Envio de emails — alojamento partilhado costuma limitar 100 a 500 por hora
    'limite_por_hora'     => 200,
    'lote_por_execucao'   => 50,

    // Assina os links de cancelamento. Mudar para um valor aleatório longo em produção.
    'segredo_links'       => 'mudar-este-segredo-dos-links',
];
