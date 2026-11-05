<div class="container pt-6 pb-14">
    <?php $tituloPagina = 'Contactos'; $introPagina = 'Respondemos no WhatsApp em minutos durante o horário de atendimento. Para pedidos formais, use o formulário.'; require __DIR__ . '/_cabecalho.php'; ?>
    <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
        <div class="space-y-3">
            <a href="<?= e(linkWhatsapp()) ?>" target="_blank" rel="noopener" class="flex items-center gap-4 cartao p-5 hover:border-tinta/25">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#1FAF5A]/15 text-[#1FAF5A]"><?= icone('whatsapp', 'h-6 w-6') ?></span>
                <span><span class="block font-semibold">WhatsApp</span><span class="text-sm text-suave num"><?= e(TELEFONE_LOJA) ?></span></span>
            </a>
            <a href="tel:<?= e(str_replace(' ', '', TELEFONE_LOJA)) ?>" class="flex items-center gap-4 cartao p-5 hover:border-tinta/25">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-marca-suave text-marca"><?= icone('telefone', 'h-6 w-6') ?></span>
                <span><span class="block font-semibold">Telefone</span><span class="text-sm text-suave num"><?= e(TELEFONE_LOJA) ?></span></span>
            </a>
            <a href="mailto:<?= e(EMAIL_LOJA) ?>" class="flex items-center gap-4 cartao p-5 hover:border-tinta/25">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-marca-suave text-marca"><?= icone('email', 'h-6 w-6') ?></span>
                <span><span class="block font-semibold">Email</span><span class="text-sm text-suave"><?= e(EMAIL_LOJA) ?></span></span>
            </a>
            <div class="flex items-center gap-4 cartao p-5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-marca-suave text-marca"><?= icone('loja', 'h-6 w-6') ?></span>
                <span><span class="block font-semibold">Loja física</span><span class="text-sm text-suave"><?= e(MORADA_LOJA) ?>. Seg–Sáb, 8h às 19h.</span></span>
            </div>
        </div>

        <form action="<?= url('/contactos/enviar') ?>" method="post" class="cartao p-5 sm:p-6 space-y-4" data-ajax-form data-limpar>
            <?= \Core\Csrf::campo() ?>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label for="c-nome" class="rotulo">Nome</label><input id="c-nome" name="nome" required maxlength="120" class="campo" autocomplete="name"></div>
                <div><label for="c-tel" class="rotulo">Telefone <span class="font-normal text-suave">(opcional)</span></label><input id="c-tel" name="telefone" type="tel" maxlength="30" class="campo" autocomplete="tel" placeholder="9XX XXX XXX"></div>
            </div>
            <div><label for="c-email" class="rotulo">Email</label><input id="c-email" name="email" type="email" required maxlength="160" class="campo" autocomplete="email"></div>
            <div>
                <label for="c-assunto" class="rotulo">Assunto</label>
                <select id="c-assunto" name="assunto" class="campo" required>
                    <option>Dúvida sobre um produto</option>
                    <option>Estado de uma encomenda</option>
                    <option>Garantia e assistência</option>
                    <option>Vendas para empresas</option>
                    <option>Outro assunto</option>
                </select>
            </div>
            <div><label for="c-msg" class="rotulo">Mensagem</label><textarea id="c-msg" name="mensagem" rows="5" required maxlength="3000" class="campo"></textarea></div>
            <!-- Armadilha anti-spam: humanos não vêem este campo -->
            <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
            <button class="btn-primario h-12 w-full sm:w-auto px-8">Enviar mensagem</button>
        </form>
    </div>
</div>
