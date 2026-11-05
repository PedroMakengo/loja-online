<?php
// =====================================================================
// app/Servicos/Entrega.php — Opções e custos de entrega por província
// =====================================================================

namespace App\Servicos;

use App\Models\ZonaEntrega;

class Entrega
{
    /**
     * Opções disponíveis para a província, já com preço final.
     * Devolve [codigo => ['codigo','nome','preco','prazo','descricao','gratis']]
     */
    public static function opcoes(string $provincia, float $subtotal): array
    {
        $cfg    = require RAIZ . '/config/pagamentos.php';
        $zonas  = (new ZonaEntrega())->paraProvincia($provincia);
        $opcoes = [];

        foreach ($zonas as $z) {
            if ((int) $z['expressa'] === 1) {
                $antesDoLimite = (int) date('G') < $cfg['hora_limite_expressa'] && date('N') < 7;
                $opcoes['expressa'] = [
                    'codigo'    => 'expressa',
                    'nome'      => 'Entrega expressa',
                    'preco'     => (float) $z['preco'],
                    'gratis'    => false,
                    'prazo'     => $antesDoLimite ? 'Hoje, depois do pagamento' : 'Amanhã de manhã',
                    'descricao' => 'Para encomendas pagas até às ' . $cfg['hora_limite_expressa'] . 'h. Entrega pela frota DIOTEKNO.',
                ];
                continue;
            }
            $gratis = $provincia === 'Luanda' && $subtotal >= PORTES_GRATIS_LUANDA;
            $prazo  = $z['prazo_min'] == $z['prazo_max']
                ? ((int) $z['prazo_min'] === 1 ? 'Até 24 horas úteis' : $z['prazo_min'] . ' dias úteis')
                : $z['prazo_min'] . ' a ' . $z['prazo_max'] . ' dias úteis';
            $opcoes['normal'] = [
                'codigo'    => 'normal',
                'nome'      => 'Entrega ao domicílio',
                'preco'     => $gratis ? 0.0 : (float) $z['preco'],
                'gratis'    => $gratis,
                'prazo'     => $prazo,
                'descricao' => $z['transportadora'] ? 'Entregue pela ' . lcfirst($z['transportadora']) . '.' : '',
            ];
        }

        // Normal primeiro, depois expressa, levantamento no fim
        uksort($opcoes, fn ($a, $b) => ($a === 'normal' ? 0 : 1) <=> ($b === 'normal' ? 0 : 1));

        $opcoes['levantamento'] = [
            'codigo'    => 'levantamento',
            'nome'      => 'Levantar na loja',
            'preco'     => 0.0,
            'gratis'    => true,
            'prazo'     => $cfg['levantamento']['prazo'],
            'descricao' => $cfg['levantamento']['morada'] . '.',
        ];

        return $opcoes;
    }
}
