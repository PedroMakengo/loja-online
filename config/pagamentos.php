<?php
// =====================================================================
// config/pagamentos.php — Métodos de pagamento e dados bancários
//
// driver 'simulado': todos os métodos funcionam em desenvolvimento e
// aparecem botões "Simular pagamento". Em produção, cada método que
// depende de um operador (EMIS/GPO, ProxyPay, Afrimoney, Unitel Money,
// gateway de cartões) só deve ficar em 'metodos_activos' depois de o
// contrato e as credenciais existirem — ver app/Servicos/Pagamentos.php.
// =====================================================================

return [
    'driver' => 'simulado',

    // Ordem = ordem no checkout. Remover um código esconde o método.
    'metodos_activos' => [
        'multicaixa_express',
        'referencia_multicaixa',
        'transferencia',
        'afrimoney',
        'unitel_money',
        'cartao',
    ],

    // Referência Multicaixa (entidade atribuída pelo operador)
    'entidade'            => '01234',
    'validade_referencia' => 48,   // horas
    'validade_push'       => 15,   // minutos para aceitar no telemóvel
    'validade_transferencia' => 72, // horas para enviar o comprovativo

    // Transferência bancária
    'banco' => [
        'titular' => 'DIOTEKNO, LDA',
        'banco'   => 'Banco BAI',
        'iban'    => 'AO06 0040 0000 0000 0000 0000 0',
        'nib'     => '0040 0000 0000 0000 0000 0',
    ],

    // Segredo partilhado para validar as notificações (webhooks) dos operadores.
    // Mudar para um valor aleatório longo em produção.
    'segredo_webhook' => env('SEGREDO_WEBHOOK', 'mudar-este-segredo-em-producao'),

    // Levantamento em loja
    'levantamento' => [
        'morada' => 'Loja DIOTEKNO, Rua Rainha Ginga, Ingombota, Luanda',
        'prazo'  => 'Pronto em 2 horas (dias úteis)',
    ],
    'hora_limite_expressa' => 13, // encomendas pagas até às 13h saem no próprio dia
];
