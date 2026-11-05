/* =====================================================================
   DIOTEKNO — admin.js (só no painel). Usa as funções globais do app.js
   através de eventos; não depende de bibliotecas.
   ===================================================================== */
(() => {
  'use strict';
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const kz = (n) => new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 0 }).format(n).replace(/\s/g, '\u202F') + '\u00A0Kz';
  const aviso = (msg, erro = false) => {
    const caixa = $('#toasts'); if (!caixa) return;
    const el = document.createElement('div');
    el.className = 'pointer-events-auto rounded-xl px-4 py-3 text-sm font-medium shadow-flutuante animate-entrar ' + (erro ? 'bg-coral text-white' : 'bg-grafite text-white');
    el.textContent = msg; caixa.appendChild(el); setTimeout(() => el.remove(), 3000);
  };

  // ------------------------------------------------------------------
  // Stock rápido na lista de produtos (guarda ao sair do campo / Enter)
  // ------------------------------------------------------------------
  $$('[data-stock-rapido]').forEach((form) => {
    const campo = $('input[name="stock"]', form);
    let original = campo.value;
    const guardar = async () => {
      if (campo.value === original) return;
      campo.disabled = true;
      try {
        const r = await fetch(form.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': CSRF },
          body: new URLSearchParams({ stock: campo.value }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensagem);
        original = campo.value;
        campo.classList.toggle('!border-aviso', d.baixo);
        campo.classList.toggle('text-aviso', d.baixo);
        campo.classList.add('!border-sucesso'); setTimeout(() => campo.classList.remove('!border-sucesso'), 1200);
      } catch (e) {
        campo.value = original; aviso(e.message || 'Não foi possível guardar.', true);
      } finally { campo.disabled = false; }
    };
    campo.addEventListener('change', guardar);
    form.addEventListener('submit', (e) => { e.preventDefault(); guardar(); });
  });

  // ------------------------------------------------------------------
  // Especificações: adicionar / remover linhas
  // ------------------------------------------------------------------
  const specs = $('[data-specs]');
  if (specs) {
    document.addEventListener('click', (e) => {
      if (e.target.closest('[data-adicionar-spec]')) {
        const nova = $('[data-spec]', specs).cloneNode(true);
        $$('input', nova).forEach((i) => (i.value = ''));
        // mantém a secção da linha anterior, que costuma repetir-se
        const ultimas = $$('[data-spec]', specs);
        $('input[name="spec_grupo[]"]', nova).value = $('input[name="spec_grupo[]"]', ultimas[ultimas.length - 1]).value;
        specs.appendChild(nova);
        $('input[name="spec_nome[]"]', nova).focus();
      }
      const rem = e.target.closest('[data-remover-spec]');
      if (rem) {
        if ($$('[data-spec]', specs).length > 1) rem.closest('[data-spec]').remove();
        else $$('input', rem.closest('[data-spec]')).forEach((i) => (i.value = ''));
      }
    });
  }

  // ------------------------------------------------------------------
  // Pré-visualização das imagens escolhidas
  // ------------------------------------------------------------------
  const zona = $('[data-zona-imagens]');
  if (zona) {
    const input = $('input[type="file"]', zona);
    const pre = $('[data-pre-visualizacao]');
    input.addEventListener('change', () => {
      pre.innerHTML = '';
      [...input.files].forEach((f) => {
        const ok = ['image/jpeg', 'image/png', 'image/webp'].includes(f.type) && f.size <= 4 * 1024 * 1024;
        const fig = document.createElement('figure');
        fig.innerHTML = ok
          ? `<img class="aspect-square w-full rounded-xl bg-elevado object-contain p-1.5" alt="">`
          : `<div class="aspect-square w-full rounded-xl bg-coral-suave p-2 text-xs text-coral flex items-center">Não aceite: use JPG, PNG ou WEBP até 4 MB</div>`;
        fig.insertAdjacentHTML('beforeend', `<figcaption class="mt-1 truncate text-xs text-suave"></figcaption>`);
        fig.querySelector('figcaption').textContent = f.name;
        if (ok) fig.querySelector('img').src = URL.createObjectURL(f);
        pre.appendChild(fig);
      });
      $('[data-nomes-imagens]').textContent = input.files.length ? `${input.files.length} imagem(ns) escolhida(s)` : 'Pode escolher várias de uma vez';
    });
  }

  // ------------------------------------------------------------------
  // Desconto e margem calculados enquanto escreve
  // ------------------------------------------------------------------
  const preco = $('[data-preco]');
  if (preco) {
    const num = (el) => parseFloat(String(el?.value || '').replace(/[\s\u202F]/g, '').replace(',', '.'));
    const actualizar = () => {
      const p = num(preco), a = num($('[data-preco-anterior]')), c = num($('[data-custo]'));
      const d = $('[data-desconto-info]'), m = $('[data-margem-info]');
      d.textContent = a > p && p > 0 ? `Desconto de ${Math.round((1 - p / a) * 100)}% (poupa ${kz(a - p)})` : '';
      if (c > 0 && p > 0) {
        const semIva = p / 1.14, margem = semIva - c;
        m.textContent = `Margem sem IVA: ${kz(margem)} (${Math.round(margem / semIva * 100)}%)`;
        m.className = 'mt-1 text-xs ' + (margem < 0 ? 'text-coral font-semibold' : 'text-suave');
      } else m.textContent = '';
    };
    ['input', 'change'].forEach((ev) => document.addEventListener(ev, (e) => { if (e.target.matches('[data-preco],[data-preco-anterior],[data-custo]')) actualizar(); }));
    actualizar();
  }

  // ------------------------------------------------------------------
  // Editor do blog: formatação e inserção de produtos
  // ------------------------------------------------------------------
  const editor = $('[data-editor]');
  if (editor) {
    const envolver = (antes, depois, exemplo) => {
      const ini = editor.selectionStart, fim = editor.selectionEnd;
      const sel = editor.value.slice(ini, fim) || exemplo;
      editor.setRangeText(antes + sel + depois, ini, fim, 'end');
      editor.focus();
      editor.setSelectionRange(ini + antes.length, ini + antes.length + sel.length);
    };
    // Insere um bloco numa linha própria, depois da linha onde está o cursor (nunca substitui a selecção)
    const linhaPropria = (txt) => {
      const v = editor.value;
      let pos = v.indexOf('\n', editor.selectionEnd);
      if (pos === -1) pos = v.length;
      const antes = v.slice(0, pos);
      const prefixo = antes === '' ? '' : (antes.endsWith('\n\n') ? '' : (antes.endsWith('\n') ? '\n' : '\n\n'));
      const bloco = prefixo + txt + '\n';
      editor.setRangeText(bloco, pos, pos, 'end');
      editor.focus();
    };
    document.addEventListener('click', (e) => {
      const b = e.target.closest('[data-formatar]');
      if (!b) return;
      const t = b.dataset.formatar;
      if (t === 'a') {
        const link = prompt('Endereço da ligação (ex.: /categoria/smartphones ou https://…)');
        if (link) envolver(`<a href="${link.replace(/"/g, '')}">`, '</a>', 'texto da ligação');
      } else if (t === 'ul' || t === 'ol') {
        const sel = editor.value.slice(editor.selectionStart, editor.selectionEnd);
        const itens = (sel || 'Primeiro ponto\nSegundo ponto').split(/\n+/).filter(Boolean).map((l) => `  <li>${l.trim()}</li>`).join('\n');
        editor.setRangeText(`<${t}>\n${itens}\n</${t}>`, editor.selectionStart, editor.selectionEnd, 'end');
        editor.focus();
      } else if (t === 'h2' || t === 'h3' || t === 'blockquote') {
        envolver(`<${t}>`, `</${t}>`, t === 'blockquote' ? 'Dica importante' : 'Título da secção');
      } else {
        envolver(`<${t}>`, `</${t}>`, 'texto');
      }
    });
    $('[data-inserir-produto]')?.addEventListener('change', (e) => {
      if (!e.target.value) return;
      linhaPropria(`[produto:${e.target.value}]`);
      e.target.value = '';
    });
  }

  // Newsletter: lista de produtos só quando a fonte é "escolher"
  const fonte = $('[data-fonte-produtos]');
  fonte?.addEventListener('change', () => { $('[data-produtos-escolhidos]').hidden = fonte.value !== 'escolhidos'; });

  // ------------------------------------------------------------------
  // Contador de caracteres (SEO)
  // ------------------------------------------------------------------
  $$('[data-contador]').forEach((c) => {
    const info = document.createElement('p');
    info.className = 'mt-1 text-xs text-suave text-right num';
    c.after(info);
    const ideal = +c.dataset.ideal || 0;
    const f = () => {
      const n = c.value.length;
      info.textContent = `${n}${c.maxLength > 0 ? ' / ' + c.maxLength : ''} caracteres${ideal && n > ideal ? ' — o Google pode cortar depois de ' + ideal : ''}`;
      info.classList.toggle('text-aviso', !!ideal && n > ideal);
    };
    c.addEventListener('input', f); f();
  });
})();
