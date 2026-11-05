<?php
/** @var int $saldo @var array $historico @var array $regras @var array $porAvaliar */
use App\Servicos\Fidelidade;
$tipos = ['compra' => 'Compra', 'avaliacao' => 'Avaliação', 'resgate' => 'Usados', 'estorno' => 'Devolvidos', 'anulacao' => 'Anulados', 'bonus' => 'Bónus', 'ajuste' => 'Ajuste da loja'];
$faltam = max(0, $regras['minimo_resgate'] - $saldo);
?>
<div class="container pt-6 pb-14">
    <div class="grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-10">
        <?php require __DIR__ . '/_menu.php'; ?>
        <div class="min-w-0 space-y-6">
            <h1 class="font-display text-3xl font-extrabold">Os meus pontos</h1>

            <!-- Saldo em formato de etiqueta, o motivo visual da loja -->
            <section class="relative overflow-hidden rounded-2xl bg-grafite p-6 sm:p-8 text-white">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07] [background-image:linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] [background-size:48px_48px]" aria-hidden="true"></div>
                <div class="relative flex flex-col sm:flex-row sm:items-center gap-6 justify-between">
                    <div>
                        <p class="text-sm text-white/70">Saldo disponível</p>
                        <p class="font-display text-5xl font-extrabold num"><?= number_format($saldo, 0, ',', ' ') ?> <span class="text-2xl text-white/70">pontos</span></p>
                        <p class="mt-2 text-sm text-white/80">
                            <?= $faltam > 0
                                ? 'Faltam ' . plural($faltam, 'ponto', 'pontos') . ' para poder usar no checkout.'
                                : 'Pode usar no próximo checkout.' ?>
                        </p>
                    </div>
                    <div class="etiqueta-grande -rotate-2 self-start sm:self-center">
                        <span class="text-xs font-semibold text-grafite/60">Vale em desconto</span>
                        <span class="font-display text-3xl font-extrabold leading-none num"><?= kz(Fidelidade::valorEmKz($saldo)) ?></span>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-3" aria-label="Como funciona">
                <div class="cartao p-4"><p class="font-semibold">Ganhe ao comprar</p><p class="mt-1 text-sm text-suave">1 ponto por cada <?= kz($regras['kz_por_ponto']) ?>, quando o pagamento é confirmado. Mais <?= $regras['bonus_primeira_compra'] ?> pontos na primeira compra.</p></div>
                <div class="cartao p-4"><p class="font-semibold">Ganhe ao avaliar</p><p class="mt-1 text-sm text-suave"><?= $regras['pontos_avaliacao'] ?> pontos por cada avaliação aprovada de um produto que recebeu.</p></div>
                <div class="cartao p-4"><p class="font-semibold">Use no checkout</p><p class="mt-1 text-sm text-suave">Cada ponto vale <?= kz($regras['valor_ponto']) ?>. A partir de <?= $regras['minimo_resgate'] ?> pontos, até <?= $regras['maximo_percentagem'] ?>% do valor dos produtos.</p></div>
            </section>

            <?php if ($porAvaliar): ?>
            <p class="rounded-xl bg-marca-suave p-4 text-sm">Tem <?= plural(count($porAvaliar), 'produto', 'produtos') ?> por avaliar, o que pode valer até <strong><?= count($porAvaliar) * $regras['pontos_avaliacao'] ?> pontos</strong>. <a href="<?= url('/conta') ?>" class="link">Ver produtos</a></p>
            <?php endif; ?>

            <section class="cartao" aria-labelledby="t-hist">
                <h2 id="t-hist" class="border-b border-linha px-5 py-4 font-display text-lg font-bold">Histórico</h2>
                <?php if ($historico): ?>
                <ul class="divide-y divide-linha">
                    <?php foreach ($historico as $m): ?>
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium"><?= e($m['descricao']) ?></p>
                            <p class="text-xs text-suave"><?= e($tipos[$m['tipo']] ?? $m['tipo']) ?>, <span class="num"><?= date('d/m/Y', strtotime($m['criado_em'])) ?></span></p>
                        </div>
                        <p class="shrink-0 font-display text-lg font-bold num <?= $m['pontos'] > 0 ? 'text-sucesso' : 'text-suave' ?>"><?= $m['pontos'] > 0 ? '+' : '−' ?><?= number_format(abs($m['pontos']), 0, ',', ' ') ?></p>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="px-5 py-8 text-center text-sm text-suave">Ainda não tem movimentos. Os pontos da primeira compra aparecem aqui assim que o pagamento for confirmado.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
