<?php /** @var array $avaliacoes @var array $pg @var string $estado @var array $contagens */
$tituloAdmin = 'Avaliações';
$subtituloAdmin = 'Publique o que é útil para outros clientes, mesmo que seja negativo. Rejeite apenas insultos, dados pessoais, publicidade ou conteúdo que não fala do produto.';
require __DIR__ . '/_titulo.php'; ?>
<nav class="faixa mb-4" aria-label="Filtrar">
    <?php foreach (['pendente' => 'Por moderar', 'aprovada' => 'Publicadas', 'rejeitada' => 'Rejeitadas', 'todas' => 'Todas'] as $k => $t): ?>
    <a href="<?= url('/admin/avaliacoes?estado=' . $k) ?>" class="chip <?= $estado === $k ? 'chip-activo' : '' ?>"><?= $t ?> <span class="text-xs opacity-70 num"><?= $k === 'todas' ? array_sum($contagens) : (int) ($contagens[$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
</nav>
<div class="space-y-3">
    <?php foreach ($avaliacoes as $a): ?>
    <article class="cartao p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="flex text-aviso" aria-label="<?= (int) $a['nota'] ?> de 5 estrelas"><?php for ($s = 1; $s <= 5; $s++): ?><?= icone('estrela', 'h-4 w-4 ' . ($s <= $a['nota'] ? 'fill-current' : 'text-linha')) ?><?php endfor; ?></p>
                <p class="mt-1 text-sm"><a href="<?= url('/admin/produtos/' . $a['produto_id']) ?>" class="font-semibold hover:text-marca"><?= e($a['produto']) ?></a></p>
                <p class="text-xs text-suave">Por <a href="<?= url('/admin/clientes/' . $a['cliente_id']) ?>" class="hover:text-tinta"><?= e($a['cliente']) ?></a><?= $a['numero'] ? ', pedido <a href="' . url('/admin/pedidos/' . $a['numero']) . '" class="num hover:text-tinta">' . e($a['numero']) . '</a>' : '' ?>, <span class="num"><?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?></span></p>
            </div>
            <span class="rounded-md px-2 py-0.5 text-xs font-semibold <?= ['pendente' => 'bg-aviso/15 text-aviso', 'aprovada' => 'bg-sucesso/15 text-sucesso', 'rejeitada' => 'bg-elevado text-suave'][$a['estado']] ?>"><?= ['pendente' => 'Por moderar', 'aprovada' => 'Publicada', 'rejeitada' => 'Rejeitada'][$a['estado']] ?></span>
        </div>
        <?php if ($a['titulo']): ?><p class="mt-3 font-semibold"><?= e($a['titulo']) ?></p><?php endif; ?>
        <p class="mt-1 whitespace-pre-line text-sm leading-6"><?= $a['comentario'] ? e($a['comentario']) : '<span class="text-suave">Só deu a nota, sem comentário.</span>' ?></p>

        <form action="<?= url('/admin/avaliacoes/' . $a['id']) ?>" method="post" class="mt-4 space-y-2 border-t border-linha pt-4">
            <?= \Core\Csrf::campo() ?>
            <input type="hidden" name="voltar_estado" value="<?= e($estado) ?>">
            <label for="resp-<?= $a['id'] ?>" class="text-xs font-semibold text-suave">Resposta pública da loja (opcional)</label>
            <textarea id="resp-<?= $a['id'] ?>" name="resposta" rows="2" maxlength="600" class="campo text-sm" placeholder="Ex.: Obrigado! Se precisar de ajuda com a configuração, fale connosco no WhatsApp."><?= e($a['resposta_loja'] ?? '') ?></textarea>
            <div class="flex flex-wrap gap-2">
                <?php if ($a['estado'] !== 'aprovada'): ?><button name="accao" value="aprovar" class="btn-primario h-9 px-4 text-sm">Publicar</button><?php endif; ?>
                <?php if ($a['estado'] !== 'rejeitada'): ?><button name="accao" value="rejeitar" class="btn-secundario h-9 px-4 text-sm">Rejeitar</button><?php endif; ?>
                <?php if ($a['estado'] === 'aprovada'): ?><button name="accao" value="responder" class="btn-secundario h-9 px-4 text-sm">Guardar resposta</button><?php endif; ?>
            </div>
        </form>
    </article>
    <?php endforeach; ?>
    <?php if (!$avaliacoes): ?><p class="cartao p-8 text-center text-suave"><?= $estado === 'pendente' ? 'Não há avaliações à espera de moderação.' : 'Sem avaliações nesta lista.' ?></p><?php endif; ?>
</div>
<?php require __DIR__ . '/_paginacao.php'; ?>
