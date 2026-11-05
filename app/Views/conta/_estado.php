<?php /** Distintivo de estado do pedido. Espera $estadoPedido */
[$rotuloEstado, $corEstado] = \App\Models\Pedido::ESTADOS[$estadoPedido] ?? [$estadoPedido, 'suave'];
$classesEstado = [
    'aviso'   => 'bg-aviso/15 text-aviso',
    'marca'   => 'bg-marca-suave text-marca',
    'sucesso' => 'bg-sucesso/15 text-sucesso',
    'suave'   => 'bg-elevado text-suave',
][$corEstado];
?>
<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold <?= $classesEstado ?>"><?= e($rotuloEstado) ?></span>
