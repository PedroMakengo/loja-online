/* =====================================================================
   DIOTEKNO — app.js
   JavaScript vanilla, sem dependências. Usa delegação de eventos para
   continuar a funcionar depois de regiões serem trocadas por AJAX.
   ===================================================================== */
(() => {
  'use strict';

  const BASE = document.body.dataset.base || '';
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const reduzMovimento = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const url = (c) => BASE + c;

  // ------------------------------------------------------------------
  // Pedidos
  // ------------------------------------------------------------------
  async function pedido(endereco, dados = null) {
    const opcoes = { headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': CSRF, Accept: 'application/json' } };
    if (dados) {
      opcoes.method = 'POST';
      opcoes.body = dados instanceof FormData ? dados : new URLSearchParams(dados);
    }
    const r = await fetch(endereco, opcoes);
    let json = {};
    try { json = await r.json(); } catch (_) { json = { ok: false, mensagem: 'Resposta inesperada do servidor.' }; }
    if (!r.ok && json.ok === undefined) json.ok = false;
    return json;
  }

  // ------------------------------------------------------------------
  // Notificações (toasts)
  // ------------------------------------------------------------------
  function toast(mensagem, tipo = 'ok', accao = null) {
    if (!mensagem) return;
    const caixa = $('#toasts');
    const el = document.createElement('div');
    el.className = 'pointer-events-auto flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium shadow-flutuante animate-entrar max-w-md w-full sm:w-auto ' +
      (tipo === 'erro' ? 'bg-coral text-white' : 'bg-grafite text-white');
    el.setAttribute('role', tipo === 'erro' ? 'alert' : 'status');
    el.textContent = mensagem;
    if (accao) {
      const a = document.createElement('a');
      a.href = accao.href;
      a.textContent = accao.texto;
      a.className = 'ml-auto shrink-0 font-semibold underline underline-offset-4';
      if (accao.aoClicar) a.addEventListener('click', accao.aoClicar);
      el.appendChild(a);
    }
    caixa.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3800);
  }

  // ------------------------------------------------------------------
  // Tema claro / escuro
  // ------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-tema]')) return;
    const escuro = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('dtk-tema', escuro ? 'escuro' : 'claro'); } catch (_) {}
  });

  // ------------------------------------------------------------------
  // Gavetas (menu mobile, carrinho, filtros)
  // ------------------------------------------------------------------
  let ultimoFoco = null;

  function abrirGaveta(id) {
    const g = document.getElementById(id);
    if (!g) return;
    ultimoFoco = document.activeElement;
    g.classList.remove('hidden');
    g.dataset.aberta = '1';
    document.body.style.overflow = 'hidden';
    setTimeout(() => g.querySelector('button, a, input')?.focus(), 30);
    if (id === 'carrinho') carregarMiniCarrinho();
  }

  function fecharGaveta(g) {
    if (!g) return;
    g.classList.add('hidden');
    delete g.dataset.aberta;
    if (!$('[data-gaveta][data-aberta]')) document.body.style.overflow = '';
    ultimoFoco?.focus?.();
  }

  document.addEventListener('click', (e) => {
    const abrir = e.target.closest('[data-abrir]');
    if (abrir) {
      // No próprio /carrinho a ligação navega normalmente
      if (abrir.dataset.abrir === 'carrinho' && location.pathname === url('/carrinho')) return;
      e.preventDefault();
      abrirGaveta(abrir.dataset.abrir);
      return;
    }
    const fechar = e.target.closest('[data-fechar]');
    if (fechar) fecharGaveta(fechar.closest('[data-gaveta]'));
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const aberta = $$('[data-gaveta][data-aberta]').pop();
    if (aberta) fecharGaveta(aberta);
    fecharDropdowns();
  });

  // ------------------------------------------------------------------
  // Dropdown "Todas as categorias"
  // ------------------------------------------------------------------
  function fecharDropdowns(excepto = null) {
    $$('[data-dropdown]').forEach((d) => {
      if (d === excepto) return;
      $('[data-dropdown-painel]', d).hidden = true;
      $('[data-dropdown-botao]', d).setAttribute('aria-expanded', 'false');
    });
  }
  document.addEventListener('click', (e) => {
    const botao = e.target.closest('[data-dropdown-botao]');
    const dd = e.target.closest('[data-dropdown]');
    if (botao) {
      const painel = $('[data-dropdown-painel]', dd);
      painel.hidden = !painel.hidden;
      botao.setAttribute('aria-expanded', String(!painel.hidden));
      fecharDropdowns(dd);
      return;
    }
    if (!dd) fecharDropdowns();
  });

  // ------------------------------------------------------------------
  // Pesquisa inteligente com sugestões
  // ------------------------------------------------------------------
  const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  $$('[data-pesquisa]').forEach((form) => {
    const campo = $('[data-pesquisa-campo]', form);
    const lista = $('[data-pesquisa-lista]', form);
    let temporizador, controlador, indice = -1;

    const fechar = () => { lista.hidden = true; campo.setAttribute('aria-expanded', 'false'); indice = -1; };
    const opcoes = () => $$('[role="option"]', lista);

    function realcar(n) {
      const ops = opcoes();
      if (!ops.length) return;
      indice = (n + ops.length) % ops.length;
      ops.forEach((o, i) => {
        o.classList.toggle('bg-elevado', i === indice);
        o.setAttribute('aria-selected', String(i === indice));
      });
      ops[indice].scrollIntoView({ block: 'nearest' });
    }

    async function procurar() {
      const q = campo.value.trim();
      if (q.length < 2) { fechar(); return; }
      controlador?.abort();
      controlador = new AbortController();
      try {
        const r = await fetch(url('/api/pesquisa?q=' + encodeURIComponent(q)), { signal: controlador.signal, headers: { 'X-Requested-With': 'fetch' } });
        const d = await r.json();
        let html = '';
        if (d.categorias?.length) {
          html += '<div class="flex flex-wrap gap-2 p-3 border-b border-linha">' +
            d.categorias.map((c) => `<a role="option" href="${esc(c.url)}" class="chip h-8">${esc(c.nome)}</a>`).join('') + '</div>';
        }
        if (d.produtos?.length) {
          html += '<ul class="max-h-[60vh] overflow-y-auto py-1">' + d.produtos.map((p) => `
            <li><a role="option" href="${esc(p.url)}" class="flex items-center gap-3 px-3 py-2 hover:bg-elevado">
              <img src="${esc(p.imagem)}" alt="" class="h-12 w-12 shrink-0 rounded-lg bg-elevado object-contain p-1" width="48" height="48">
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-tinta">${esc(p.nome)}</span>
                ${p.marca ? `<span class="block text-xs text-suave">${esc(p.marca)}</span>` : ''}
              </span>
              <span class="text-right shrink-0">
                ${p.anterior ? `<span class="block preco-anterior text-xs">${esc(p.anterior)}</span>` : ''}
                <span class="block preco text-sm ${p.anterior ? 'text-coral' : ''}">${esc(p.preco)}</span>
              </span>
            </a></li>`).join('') + '</ul>';
          html += `<a role="option" href="${esc(d.todos)}" class="block border-t border-linha px-4 py-3 text-sm font-semibold text-marca hover:bg-elevado">Ver todos os resultados para "${esc(q)}"</a>`;
        } else {
          html += `<p class="px-4 py-5 text-sm text-suave">Sem resultados para "${esc(q)}". Experimente outra palavra, como a marca ou o modelo.</p>`;
        }
        lista.innerHTML = html;
        lista.hidden = false;
        campo.setAttribute('aria-expanded', 'true');
        indice = -1;
      } catch (err) {
        if (err.name !== 'AbortError') fechar();
      }
    }

    campo.addEventListener('input', () => { clearTimeout(temporizador); temporizador = setTimeout(procurar, 220); });
    campo.addEventListener('focus', () => { if (lista.innerHTML.trim() && campo.value.trim().length >= 2) { lista.hidden = false; } });
    campo.addEventListener('keydown', (e) => {
      if (lista.hidden) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); realcar(indice + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); realcar(indice - 1); }
      else if (e.key === 'Enter' && indice >= 0) { e.preventDefault(); opcoes()[indice].click(); }
      else if (e.key === 'Escape') fechar();
    });
    document.addEventListener('click', (e) => { if (!form.contains(e.target)) fechar(); });
  });

  // ------------------------------------------------------------------
  // Banner principal (carrossel com scroll-snap)
  // ------------------------------------------------------------------
  $$('[data-carrossel]').forEach((c) => {
    const pista = $('[data-carrossel-pista]', c);
    const slides = [...pista.children];
    const pontos = $$('[data-ir]', c);
    if (slides.length < 2) return;
    let actual = 0, auto;

    const irPara = (n) => {
      actual = (n + slides.length) % slides.length;
      pista.scrollTo({ left: slides[actual].offsetLeft, behavior: reduzMovimento ? 'auto' : 'smooth' });
    };
    const observador = new IntersectionObserver((entradas) => {
      entradas.forEach((en) => {
        if (en.isIntersecting && en.intersectionRatio > 0.6) {
          actual = slides.indexOf(en.target);
          pontos.forEach((p, i) => p.setAttribute('aria-current', String(i === actual)));
        }
      });
    }, { root: pista, threshold: 0.6 });
    slides.forEach((s) => observador.observe(s));

    c.addEventListener('click', (e) => {
      if (e.target.closest('[data-ir]')) irPara(+e.target.closest('[data-ir]').dataset.ir);
      if (e.target.closest('[data-anterior]')) irPara(actual - 1);
      if (e.target.closest('[data-seguinte]')) irPara(actual + 1);
    });

    const parar = () => clearInterval(auto);
    const iniciar = () => { if (!reduzMovimento) { parar(); auto = setInterval(() => irPara(actual + 1), 6500); } };
    ['mouseenter', 'focusin', 'touchstart'].forEach((ev) => c.addEventListener(ev, parar, { passive: true }));
    ['mouseleave', 'focusout'].forEach((ev) => c.addEventListener(ev, iniciar));
    document.addEventListener('visibilitychange', () => (document.hidden ? parar() : iniciar()));
    iniciar();
  });

  // ------------------------------------------------------------------
  // Contagem decrescente (promoções)
  // ------------------------------------------------------------------
  function iniciarContagens() {
    $$('[data-contagem]:not([data-contagem-ok])').forEach((el) => {
      el.dataset.contagemOk = '1';
      const fim = new Date(el.dataset.contagem).getTime();
      const alvo = $('[data-contagem-texto]', el);
      const tick = () => {
        let s = Math.max(0, Math.floor((fim - Date.now()) / 1000));
        if (s === 0) { el.hidden = true; return; }
        const d = Math.floor(s / 86400); s %= 86400;
        const h = String(Math.floor(s / 3600)).padStart(2, '0');
        const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
        const ss = String(s % 60).padStart(2, '0');
        alvo.textContent = (d ? d + 'd ' : '') + `${h}:${m}:${ss}`;
        setTimeout(tick, 1000);
      };
      tick();
    });
  }

  // ------------------------------------------------------------------
  // Carrinho
  // ------------------------------------------------------------------
  function actualizarContador(qtd) {
    $$('[data-carrinho-qtd]').forEach((b) => {
      b.textContent = qtd;
      b.classList.toggle('hidden', !qtd);
      if (!reduzMovimento) { b.classList.remove('animate-pulsar'); void b.offsetWidth; b.classList.add('animate-pulsar'); }
    });
  }

  function aplicarRespostaCarrinho(d) {
    if (typeof d.quantidade === 'number') actualizarContador(d.quantidade);
    const conteudo = $('[data-carrinho-conteudo]');
    if (conteudo && d.html) conteudo.innerHTML = d.html;
    if (location.pathname === url('/carrinho')) recarregarRegioes(location.href, false);
  }

  async function carregarMiniCarrinho() {
    const d = await pedido(url('/carrinho/mini'));
    aplicarRespostaCarrinho(d);
  }

  document.addEventListener('click', async (e) => {
    // Adicionar
    const add = e.target.closest('[data-adicionar]');
    if (add) {
      e.preventDefault();
      let quantidade = 1;
      if (add.hasAttribute('data-com-quantidade')) quantidade = +($('[data-qtd-campo]')?.value || 1);
      add.disabled = true;
      const d = await pedido(url('/carrinho/adicionar'), { produto_id: add.dataset.adicionar, quantidade });
      add.disabled = false;
      if (!d.ok) { toast(d.mensagem, 'erro'); return; }
      aplicarRespostaCarrinho(d);
      if (add.hasAttribute('data-comprar-ja')) { location.href = url('/checkout'); return; }
      toast(d.mensagem, 'ok', { texto: 'Ver carrinho', href: url('/carrinho'), aoClicar: (ev) => { ev.preventDefault(); abrirGaveta('carrinho'); } });
      return;
    }

    // Alterar quantidade (gaveta e página do carrinho)
    const passo = e.target.closest('[data-qtd]');
    if (passo) {
      const caixa = passo.closest('[data-quantidade]');
      const valor = $('[data-qtd-valor]', caixa);
      const nova = +valor.textContent + +passo.dataset.qtd;
      if (nova < 1) {
        aplicarRespostaCarrinho(await pedido(url('/carrinho/remover'), { produto_id: caixa.dataset.quantidade }));
        return;
      }
      valor.textContent = nova;
      aplicarRespostaCarrinho(await pedido(url('/carrinho/actualizar'), { produto_id: caixa.dataset.quantidade, quantidade: nova }));
      return;
    }

    // Remover
    const rem = e.target.closest('[data-remover]');
    if (rem) {
      rem.closest('[data-item]')?.classList.add('opacity-40');
      const d = await pedido(url('/carrinho/remover'), { produto_id: rem.dataset.remover });
      aplicarRespostaCarrinho(d);
      toast(d.mensagem);
    }
  });

  // Quantidade na página de produto
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-qtd-produto] [data-passo]');
    if (!b) return;
    const caixa = b.closest('[data-qtd-produto]');
    const campo = $('[data-qtd-campo]', caixa);
    const max = +caixa.dataset.max || 10;
    campo.value = Math.min(max, Math.max(1, (+campo.value || 1) + +b.dataset.passo));
  });

  // Estimativa de entrega na página do carrinho
  function estimarEntrega() {
    const sel = $('[data-estimar-entrega]');
    if (!sel) return;
    const zonas = JSON.parse(sel.dataset.zonas || '[]');
    const subtotal = +sel.dataset.subtotal;
    const gratis = +sel.dataset.gratis;
    const zona = zonas.find((z) => z.provincia === sel.value && +z.expressa === 0);
    const precoEl = $('[data-entrega-preco]');
    const prazoEl = $('[data-entrega-prazo]');
    if (!zona) { precoEl.textContent = '—'; prazoEl.textContent = ''; return; }
    const ehGratis = zona.provincia === 'Luanda' && subtotal >= gratis;
    precoEl.textContent = ehGratis ? 'Grátis' : new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 0 }).format(+zona.preco).replace(/\s/g, '\u202F') + '\u00A0Kz';
    precoEl.classList.toggle('text-sucesso', ehGratis);
    prazoEl.textContent = +zona.prazo_min === +zona.prazo_max ? `${zona.prazo_min} dia útil` : `${zona.prazo_min} a ${zona.prazo_max} dias úteis`;
  }
  document.addEventListener('change', (e) => { if (e.target.matches('[data-estimar-entrega]')) estimarEntrega(); });

  // ------------------------------------------------------------------
  // Galeria do produto
  // ------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const mini = e.target.closest('[data-galeria-mini]');
    if (!mini) return;
    const g = mini.closest('[data-galeria]');
    $('[data-galeria-principal]', g).src = mini.dataset.galeriaMini;
    $$('[data-galeria-mini]', g).forEach((m) => m.classList.toggle('border-marca', m === mini));
    $$('[data-galeria-mini]', g).forEach((m) => m.classList.toggle('border-transparent', m !== mini));
  });

  // ------------------------------------------------------------------
  // Formulários AJAX (contacto, newsletter, cupões)
  // ------------------------------------------------------------------
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-ajax-form]');
    if (!form) return;
    e.preventDefault();
    const botao = form.querySelector('button[type="submit"], button:not([type])');
    const textoOriginal = botao?.innerHTML;
    if (botao) { botao.disabled = true; botao.textContent = 'A enviar…'; }
    const d = await pedido(form.action, new FormData(form));
    if (botao) { botao.disabled = false; botao.innerHTML = textoOriginal; }
    toast(d.mensagem, d.ok ? 'ok' : 'erro');
    if (d.ok && form.hasAttribute('data-limpar')) form.reset();
    if (d.ok && form.matches('[action$="/newsletter"]')) form.reset();
    if (form.hasAttribute('data-recarregar-carrinho')) aplicarRespostaCarrinho(d);
  });

  // ------------------------------------------------------------------
  // Catálogo: filtros, ordenação e paginação sem recarregar a página
  // ------------------------------------------------------------------
  async function recarregarRegioes(endereco, empurrarHistorico = true) {
    const regioes = $$('[data-actualizar][id]');
    regioes.forEach((r) => r.setAttribute('aria-busy', 'true'));
    try {
      const r = await fetch(endereco, { headers: { 'X-Requested-With': 'fetch-html' } });
      const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
      regioes.forEach((antiga) => {
        const nova = doc.getElementById(antiga.id);
        if (nova) antiga.replaceWith(nova);
      });
      const total = $('#resultados strong')?.textContent;
      $$('[data-total-botao]').forEach((b) => { if (total) b.textContent = total; });
      if (empurrarHistorico) history.pushState({ catalogo: true }, '', endereco);
      iniciarContagens();
      estimarEntrega();
    } catch (_) {
      location.href = endereco;
    }
  }

  function urlDoFormulario(form) {
    const params = new URLSearchParams();
    for (const [k, v] of new FormData(form)) {
      if (k === '_csrf' || v === '' || (k === 'ordem' && v === 'relevancia')) continue;
      params.append(k, v);
    }
    const qs = params.toString();
    return form.action + (qs ? '?' + qs : '');
  }

  document.addEventListener('change', (e) => {
    const form = e.target.closest('[data-filtros]');
    if (!form || e.target.hasAttribute('data-sem-auto')) return;
    recarregarRegioes(urlDoFormulario(form));
  });

  document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-filtros]');
    if (!form) return;
    e.preventDefault();
    recarregarRegioes(urlDoFormulario(form));
  });

  document.addEventListener('click', (e) => {
    const a = e.target.closest('[data-ligacao-filtro]');
    if (!a || e.metaKey || e.ctrlKey) return;
    e.preventDefault();
    const sobe = a.closest('nav[aria-label="Paginação"]');
    recarregarRegioes(a.href).then(() => {
      // Sincroniza checkboxes/inputs do painel com o novo URL
      const p = new URL(a.href).searchParams;
      const form = $('[data-filtros]');
      if (form) {
        $$('input[type="checkbox"]', form).forEach((cb) => {
          cb.checked = cb.name === 'marca[]' ? p.getAll('marca[]').includes(cb.value) || p.getAll('marca').includes(cb.value) : p.has(cb.name);
        });
        ['min', 'max'].forEach((n) => { const i = form.querySelector(`[name="${n}"]`); if (i) i.value = p.get(n) || ''; });
      }
      if (sobe) $('#resultados')?.scrollIntoView({ behavior: reduzMovimento ? 'auto' : 'smooth' });
    });
  });

  window.addEventListener('popstate', () => {
    if ($('[data-filtros]')) recarregarRegioes(location.href, false);
  });

  // ------------------------------------------------------------------
  // Favoritos (coração nos cartões e na página de produto)
  // ------------------------------------------------------------------
  function marcarFavorito(id, activo) {
    $$(`[data-favorito="${id}"]`).forEach((b) => {
      b.setAttribute('aria-pressed', String(activo));
      b.setAttribute('aria-label', activo ? 'Remover dos favoritos' : 'Guardar nos favoritos');
      b.querySelector('svg')?.classList.toggle('fill-current', activo);
      if (activo && !reduzMovimento) { b.classList.remove('animate-pulsar'); void b.offsetWidth; b.classList.add('animate-pulsar'); }
    });
  }

  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-favorito]');
    if (!b) return;
    e.preventDefault();
    const id = b.dataset.favorito;
    const estavaActivo = b.getAttribute('aria-pressed') === 'true';

    // Sem sessão: não fazemos o pedido, levamos ao login e voltamos aqui
    if (document.body.dataset.sessao !== '1') {
      const voltar = location.pathname.replace(BASE, '') + location.search;
      toast('Entre na sua conta para guardar favoritos.', 'ok', { texto: 'Entrar', href: url('/entrar?voltar=' + encodeURIComponent(voltar)) });
      return;
    }

    marcarFavorito(id, !estavaActivo); // resposta imediata
    const d = await pedido(url('/favoritos/alternar'), { produto_id: id });
    if (!d.ok) { marcarFavorito(id, estavaActivo); toast(d.mensagem, 'erro'); return; }
    marcarFavorito(id, d.favorito);

    // Na página de favoritos, o cartão sai da lista
    const lista = b.closest('[data-lista-favoritos]');
    if (lista && !d.favorito) {
      const cartao = b.closest('article');
      cartao.style.transition = 'opacity .25s';
      cartao.style.opacity = '0';
      setTimeout(() => { cartao.remove(); if (!lista.children.length) location.reload(); }, 250);
    }
    toast(d.mensagem, 'ok', d.favorito ? { texto: 'Ver favoritos', href: url('/favoritos') } : null);
  });

  // ------------------------------------------------------------------
  // Mostrar / ocultar senha
  // ------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-mostrar-senha]');
    if (!b) return;
    const campo = b.parentElement.querySelector('input');
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    b.setAttribute('aria-pressed', String(mostrar));
    b.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    b.classList.toggle('text-marca', mostrar);
  });

  // Confirmação antes de acções destrutivas
  document.addEventListener('submit', (e) => {
    const f = e.target.closest('[data-confirmar]');
    if (f && !confirm(f.dataset.confirmar)) e.preventDefault();
  }, true);

  // Mensagens vindas do servidor (depois de um redirect)
  try {
    const flash = JSON.parse($('#flash-inicial')?.textContent || '[]');
    flash.forEach((m) => toast(m.mensagem, m.tipo === 'erro' ? 'erro' : 'ok'));
  } catch (_) {}

  // Foca o primeiro campo com erro
  $('[aria-invalid="true"]')?.focus();

  // ------------------------------------------------------------------
  // Checkout
  // ------------------------------------------------------------------
  const formCheckout = $('[data-checkout]');
  if (formCheckout) {
    // Recalcula entrega e totais ao mudar a morada ou o tipo de entrega
    formCheckout.addEventListener('change', async (e) => {
      if (!e.target.matches('[data-recalcular]')) return;
      const fd = new FormData();
      fd.append('endereco_id', formCheckout.querySelector('[name="endereco_id"]:checked')?.value || '');
      fd.append('metodo_entrega', e.target.name === 'endereco_id' ? '' : (formCheckout.querySelector('[name="metodo_entrega"]:checked')?.value || ''));
      if (formCheckout.querySelector('[name="usar_pontos"]')?.checked) fd.append('usar_pontos', '1');
      $('#checkout-resumo')?.setAttribute('aria-busy', 'true');
      const d = await pedido(url('/checkout/calcular'), fd);
      if (!d.ok) return;
      const trocar = (id, html) => { const el = document.getElementById(id); if (el) el.outerHTML = html; };
      // Mantém a opção de entrega escolhida se continuar disponível
      const escolhida = e.target.name !== 'endereco_id' ? formCheckout.querySelector('[name="metodo_entrega"]:checked')?.value : null;
      trocar('checkout-entrega', d.entrega);
      trocar('checkout-resumo', d.resumo);
      if (escolhida) { const r = formCheckout.querySelector(`[name="metodo_entrega"][value="${escolhida}"]`); if (r) r.checked = true; }
    });

    // Evita duplo envio
    formCheckout.addEventListener('submit', () => {
      const b = $('[data-confirmar-pedido]', formCheckout);
      setTimeout(() => { b.disabled = true; b.textContent = 'A criar o pedido…'; }, 0);
    });
  }

  // Campo de telefone só para Multicaixa Express / Afrimoney / Unitel Money
  document.addEventListener('change', (e) => {
    if (!e.target.matches('[data-metodo-pagamento]')) return;
    const caixa = e.target.closest('form')?.querySelector('[data-campo-telefone]');
    if (caixa) caixa.hidden = e.target.dataset.pedeTelefone !== '1';
  });

  // Copiar entidade / referência / IBAN
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-copiar]');
    if (!b) return;
    try { await navigator.clipboard.writeText(b.dataset.copiar); toast('Copiado.'); }
    catch (_) { toast('Não foi possível copiar. Seleccione e copie manualmente.', 'erro'); }
  });

  // Aguardar confirmação do pagamento (referência / push): consulta o estado e recarrega quando muda
  const aguardar = $('[data-aguardar-pagamento]');
  if (aguardar) {
    let intervalo = 5000;
    const verificar = async () => {
      if (document.hidden) { setTimeout(verificar, intervalo); return; }
      try {
        const r = await fetch(aguardar.dataset.aguardarPagamento, { headers: { 'X-Requested-With': 'fetch' } });
        const d = await r.json();
        if (d.estado !== 'pendente' || d.pagamento !== 'pendente') { location.reload(); return; }
      } catch (_) {}
      intervalo = Math.min(intervalo * 1.3, 30000);
      setTimeout(verificar, intervalo);
    };
    setTimeout(verificar, intervalo);
  }

  // ------------------------------------------------------------------
  // Comparador
  // ------------------------------------------------------------------
  function actualizarBarraComparar(total) {
    const barra = $('[data-barra-comparar]');
    if (!barra) return;
    $$('[data-comparar-total]').forEach((t) => (t.textContent = total));
    barra.classList.toggle('hidden', !total || location.pathname === url('/comparar'));
  }

  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-comparar]');
    if (b) {
      e.preventDefault();
      const id = b.dataset.comparar;
      const d = await pedido(url('/comparar/alternar'), { produto_id: id });
      if (!d.ok) { toast(d.mensagem, 'erro'); return; }
      if (b.hasAttribute('data-recarregar')) { location.reload(); return; }
      $$(`[data-comparar="${id}"]`).forEach((x) => {
        x.setAttribute('aria-pressed', String(d.activo));
        x.setAttribute('aria-label', d.activo ? 'Retirar da comparação' : 'Comparar');
      });
      actualizarBarraComparar(d.total);
      toast(d.mensagem, 'ok', d.activo && d.total >= 2 ? { texto: 'Comparar agora', href: url('/comparar') } : null);
      return;
    }
    if (e.target.closest('[data-limpar-comparar]')) {
      const d = await pedido(url('/comparar/limpar'), {});
      if (d.ok) {
        $$('[data-comparar]').forEach((x) => x.setAttribute('aria-pressed', 'false'));
        actualizarBarraComparar(0);
      }
    }
  });

  // Só diferenças na tabela de comparação
  document.addEventListener('change', (e) => {
    if (!e.target.matches('[data-so-diferencas]')) return;
    $$('[data-tabela-comparar] tbody tr.igual').forEach((tr) => (tr.hidden = e.target.checked));
  });

  // ------------------------------------------------------------------
  // Avaliações: ver mais e ordenar
  // ------------------------------------------------------------------
  const listaAval = $('[data-lista-avaliacoes]');
  if (listaAval) {
    const botao = $('[data-mais-avaliacoes]');
    const ordem = $('[data-ordem-avaliacoes]');
    const carregar = async (pagina, substituir) => {
      const r = await fetch(`${listaAval.dataset.url}?pagina=${pagina}&ordem=${ordem.value}`, { headers: { 'X-Requested-With': 'fetch' } });
      const d = await r.json();
      if (substituir) listaAval.innerHTML = d.html; else listaAval.insertAdjacentHTML('beforeend', d.html);
      if (botao) { botao.dataset.pagina = pagina + 1; botao.hidden = !d.temMais; }
    };
    botao?.addEventListener('click', () => carregar(+botao.dataset.pagina, false));
    ordem?.addEventListener('change', () => carregar(1, true));
  }

  // ------------------------------------------------------------------
  // Blog: índice marca a secção que está a ser lida
  // ------------------------------------------------------------------
  const links = $$('[data-indice-link]');
  if (links.length) {
    const obs = new IntersectionObserver((ents) => {
      ents.forEach((en) => {
        if (!en.isIntersecting) return;
        links.forEach((l) => l.setAttribute('aria-current', String(l.dataset.indiceLink === en.target.id)));
      });
    }, { rootMargin: '-140px 0px -65% 0px' });
    links.forEach((l) => { const alvo = document.getElementById(l.dataset.indiceLink); if (alvo) obs.observe(alvo); });
  }

  // ------------------------------------------------------------------
  iniciarContagens();
  estimarEntrega();
})();
