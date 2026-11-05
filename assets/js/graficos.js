/* =====================================================================
   DIOTEKNO — graficos.js
   Gráficos em SVG/HTML, sem dependências, no estilo dos charts do shadcn/ui
   (área com gradiente, barras arredondadas, donut com total ao centro,
   tooltip com indicador de cor). Cores por variáveis CSS (--serie-N),
   por isso o modo escuro muda sem redesenhar.

   Uso:  <div data-grafico='{"tipo":"area", ...}'></div>
   Tipos: area · barras · barras-h · donut · calor · faisca
   Tabela: um botão [data-grafico-tabela] dentro do mesmo [data-cartao-grafico]
   mostra/esconde os dados em tabela (acessibilidade e leitura exacta).
   ===================================================================== */
(() => {
  'use strict';

  const NS = 'http://www.w3.org/2000/svg';
  let uid = 0;

  // ------------------------------------------------------------------
  // Formatação (pt-AO: 1.499.800,00 Kz)
  // ------------------------------------------------------------------
  const nf2 = new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const nf0 = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 0 });
  const nf1 = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 1 });
  const formatar = (v, f) => f === 'kz' ? nf2.format(v) + ' Kz' : f === 'pct' ? nf1.format(v) + '%' : nf0.format(v);
  const compacto = (v) => {
    const a = Math.abs(v);
    if (a >= 1e9) return nf1.format(v / 1e9) + ' mM';
    if (a >= 1e6) return nf1.format(v / 1e6) + ' M';
    if (a >= 1e3) return nf1.format(v / 1e3) + ' mil';
    return nf0.format(v);
  };
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const cor = (c) => `var(--${c})`;

  function el(tag, attrs = {}, pai = null) {
    const n = document.createElementNS(NS, tag);
    for (const [k, v] of Object.entries(attrs)) if (v !== null && v !== undefined) n.setAttribute(k, v);
    if (pai) pai.appendChild(n);
    return n;
  }

  /** Passo "redondo" (~4 divisões) e topo do eixo logo acima do máximo dos dados. */
  function escala(max) {
    if (max <= 0) return { max: 4, passo: 1 };
    const bruto = max / 4;
    const p = Math.pow(10, Math.floor(Math.log10(bruto)));
    const n = bruto / p;
    const passo = (n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10) * p;
    return { max: Math.ceil(max / passo - 1e-9) * passo, passo };
  }

  /** Curva monótona (Fritsch–Carlson): suave como o "natural" do shadcn, sem ultrapassar os dados. */
  function curva(pts) {
    const n = pts.length;
    if (n === 0) return '';
    if (n === 1) return `M${pts[0][0]},${pts[0][1]}`;
    const dx = [], dy = [], m = [], t = [];
    for (let i = 0; i < n - 1; i++) { dx[i] = pts[i + 1][0] - pts[i][0]; dy[i] = pts[i + 1][1] - pts[i][1]; m[i] = dy[i] / dx[i]; }
    t[0] = m[0]; t[n - 1] = m[n - 2];
    for (let i = 1; i < n - 1; i++) t[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2;
    for (let i = 0; i < n - 1; i++) {
      if (m[i] === 0) { t[i] = 0; t[i + 1] = 0; continue; }
      const a = t[i] / m[i], b = t[i + 1] / m[i], s = a * a + b * b;
      if (s > 9) { const k = 3 / Math.sqrt(s); t[i] = k * a * m[i]; t[i + 1] = k * b * m[i]; }
    }
    let d = `M${pts[0][0]},${pts[0][1]}`;
    for (let i = 0; i < n - 1; i++) {
      const h = dx[i] / 3;
      d += `C${pts[i][0] + h},${pts[i][1] + t[i] * h} ${pts[i + 1][0] - h},${pts[i + 1][1] - t[i + 1] * h} ${pts[i + 1][0]},${pts[i + 1][1]}`;
    }
    return d;
  }

  /** Rectângulo com cantos de 4px só na ponta dos dados (base recta). */
  function barraV(x, y, w, h, r = 4) {
    r = Math.min(r, w / 2, h);
    return `M${x},${y + h}V${y + r}Q${x},${y} ${x + r},${y}H${x + w - r}Q${x + w},${y} ${x + w},${y + r}V${y + h}Z`;
  }

  // ------------------------------------------------------------------
  // Tooltip (estilo shadcn: cartão, rótulo, linhas com indicador de cor)
  // ------------------------------------------------------------------
  function tooltip(caixa) {
    let t = caixa.querySelector(':scope > .grafico-tooltip');
    if (!t) { t = document.createElement('div'); t.className = 'grafico-tooltip'; t.hidden = true; caixa.appendChild(t); }
    return {
      mostrar(titulo, linhas, x, y) {
        t.innerHTML = (titulo ? `<p class="grafico-tooltip-titulo">${esc(titulo)}</p>` : '') + linhas.map((l) =>
          `<p class="grafico-tooltip-linha">${l.cor ? `<span class="grafico-indicador" style="background:${cor(l.cor)}"></span>` : ''}` +
          `<span class="grafico-tooltip-nome">${esc(l.nome)}</span><span class="grafico-tooltip-valor">${esc(l.valor)}</span></p>`).join('');
        t.hidden = false;
        const lw = caixa.clientWidth, tw = t.offsetWidth, th = t.offsetHeight;
        let left = x + 14; if (left + tw > lw) left = Math.max(0, x - tw - 14);
        t.style.left = left + 'px';
        t.style.top = Math.max(0, Math.min(y - th / 2, caixa.clientHeight - th)) + 'px';
      },
      esconder() { t.hidden = true; },
    };
  }

  function vazio(caixa, altura) {
    caixa.innerHTML = `<div class="grafico-vazio" style="height:${altura}px">Sem dados no período.</div>`;
  }

  const temValores = (dados, chaves) => dados.some((d) => chaves.some((k) => +d[k] > 0));

  // ------------------------------------------------------------------
  // Área (e linha) — séries temporais
  // ------------------------------------------------------------------
  function area(caixa, c) {
    const H = c.altura || 260, W = caixa.clientWidth, dados = c.dados, series = c.series;
    if (!dados.length || !temValores(dados, series.map((s) => s.chave))) return vazio(caixa, H);
    const m = { t: 12, r: 12, b: 28, l: 58 };
    const pw = W - m.l - m.r, ph = H - m.t - m.b;
    const { max, passo } = escala(Math.max(...dados.flatMap((d) => series.map((s) => +d[s.chave] || 0))));
    const x = (i) => m.l + (dados.length === 1 ? pw / 2 : (i * pw) / (dados.length - 1));
    const y = (v) => m.t + ph - (v / max) * ph;

    const svg = el('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, class: 'grafico-svg', role: 'img', 'aria-label': c.titulo || '' });
    const defs = el('defs', {}, svg);
    // Grelha horizontal (linhas finas, sólidas) + eixo Y compacto
    for (let v = 0; v <= max + 1e-9; v += passo) {
      el('line', { x1: m.l, x2: W - m.r, y1: y(v), y2: y(v), class: 'grafico-grelha' }, svg);
      el('text', { x: m.l - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'grafico-eixo' }, svg).textContent = compacto(v);
    }
    // Eixo X: só os rótulos que cabem (~1 por 72px)
    const cada = Math.max(1, Math.ceil(dados.length / Math.max(2, Math.floor(pw / 72))));
    dados.forEach((d, i) => {
      if (i % cada && i !== dados.length - 1) return;
      if (i === dados.length - 1 && i % cada && (i % cada) < cada / 2) return; // evita colar no anterior
      el('text', { x: x(i), y: H - 8, 'text-anchor': i === 0 && dados.length > 1 ? 'start' : i === dados.length - 1 && dados.length > 1 ? 'end' : 'middle', class: 'grafico-eixo' }, svg).textContent = d.rotulo;
    });
    // Séries (a anterior primeiro, por baixo)
    series.slice().reverse().forEach((s) => {
      const pts = dados.map((d, i) => [x(i), y(+d[s.chave] || 0)]);
      const linha = curva(pts);
      if (s.preencher !== false) {
        const id = 'g' + ++uid;
        const g = el('linearGradient', { id, x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
        el('stop', { offset: '5%', style: `stop-color:${cor(s.cor)};stop-opacity:${s.cor === 'anterior' ? 0.10 : 0.28}` }, g);
        el('stop', { offset: '95%', style: `stop-color:${cor(s.cor)};stop-opacity:0.02` }, g);
        el('path', { d: `${linha}L${pts[pts.length - 1][0]},${y(0)}L${pts[0][0]},${y(0)}Z`, fill: `url(#${id})` }, svg);
      }
      el('path', { d: linha, fill: 'none', style: `stroke:${cor(s.cor)}`, 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-dasharray': s.tracejado ? '4 4' : null }, svg);
    });
    // Camada de hover: linha vertical + pontos com anel da cor da superfície
    const guia = el('line', { y1: m.t, y2: m.t + ph, class: 'grafico-guia', visibility: 'hidden' }, svg);
    const pontos = series.map((s) => el('circle', { r: 4.5, style: `fill:${cor(s.cor)}`, class: 'grafico-ponto', visibility: 'hidden' }, svg));
    const alvo = el('rect', { x: m.l, y: m.t, width: pw, height: ph, fill: 'transparent' }, svg);
    caixa.replaceChildren(svg);
    const tip = tooltip(caixa);
    const mover = (ev) => {
      const r = svg.getBoundingClientRect();
      const px = ev.clientX - r.left;
      const i = dados.length === 1 ? 0 : Math.max(0, Math.min(dados.length - 1, Math.round(((px - m.l) / pw) * (dados.length - 1))));
      guia.setAttribute('x1', x(i)); guia.setAttribute('x2', x(i)); guia.setAttribute('visibility', 'visible');
      series.forEach((s, k) => { pontos[k].setAttribute('cx', x(i)); pontos[k].setAttribute('cy', y(+dados[i][s.chave] || 0)); pontos[k].setAttribute('visibility', 'visible'); });
      tip.mostrar(dados[i].rotulo, series.map((s) => ({ cor: s.cor, nome: s.nome, valor: formatar(+dados[i][s.chave] || 0, c.formato) })), x(i), ev.clientY - r.top);
    };
    alvo.addEventListener('pointermove', mover);
    alvo.addEventListener('pointerdown', mover);
    alvo.addEventListener('pointerleave', () => { guia.setAttribute('visibility', 'hidden'); pontos.forEach((p) => p.setAttribute('visibility', 'hidden')); tip.esconder(); });
  }

  // ------------------------------------------------------------------
  // Barras verticais (agrupadas ou empilhadas)
  // ------------------------------------------------------------------
  function barras(caixa, c) {
    const H = c.altura || 260, W = caixa.clientWidth, dados = c.dados, series = c.series;
    if (!dados.length || !temValores(dados, series.map((s) => s.chave))) return vazio(caixa, H);
    const m = { t: 12, r: 8, b: 28, l: 58 };
    const pw = W - m.l - m.r, ph = H - m.t - m.b;
    const totais = dados.map((d) => c.empilhado ? series.reduce((a, s) => a + (+d[s.chave] || 0), 0) : Math.max(...series.map((s) => +d[s.chave] || 0)));
    const { max, passo } = escala(Math.max(...totais));
    const y = (v) => m.t + ph - (v / max) * ph;
    const banda = pw / dados.length;
    const nBarras = c.empilhado ? 1 : series.length;
    const larg = Math.max(3, Math.min(c.larguraMax || 28, (banda * 0.7 - (nBarras - 1) * 2) / nBarras));

    const svg = el('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, class: 'grafico-svg', role: 'img', 'aria-label': c.titulo || '' });
    for (let v = 0; v <= max + 1e-9; v += passo) {
      el('line', { x1: m.l, x2: W - m.r, y1: y(v), y2: y(v), class: 'grafico-grelha' }, svg);
      el('text', { x: m.l - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'grafico-eixo' }, svg).textContent = compacto(v);
    }
    const realce = el('rect', { y: m.t, height: ph, width: banda, rx: 6, class: 'grafico-realce', visibility: 'hidden' }, svg);
    const cada = Math.max(1, Math.ceil(dados.length / Math.max(2, Math.floor(pw / 56))));
    dados.forEach((d, i) => {
      const cx = m.l + banda * i + banda / 2;
      if (i % cada === 0) el('text', { x: cx, y: H - 8, 'text-anchor': 'middle', class: 'grafico-eixo' }, svg).textContent = d.rotulo;
      if (c.empilhado) {
        let base = 0;
        const visiveis = series.filter((s) => +d[s.chave] > 0);
        visiveis.forEach((s, k) => {
          const v = +d[s.chave];
          const topo = y(base + v), fundo = y(base);
          const gap = k > 0 ? 2 : 0; // 2px de superfície entre segmentos
          const h = Math.max(0, fundo - topo - gap);
          el('path', { d: k === visiveis.length - 1 ? barraV(cx - larg / 2, topo, larg, h) : `M${cx - larg / 2},${topo}h${larg}v${h}h${-larg}Z`, style: `fill:${cor(s.cor)}` }, svg);
          base += v;
        });
      } else {
        const total = nBarras * larg + (nBarras - 1) * 2;
        series.forEach((s, k) => {
          const v = +d[s.chave] || 0;
          if (v <= 0) return;
          el('path', { d: barraV(cx - total / 2 + k * (larg + 2), y(v), larg, y(0) - y(v)), style: `fill:${cor(s.cor)}` }, svg);
        });
      }
    });
    const alvo = el('rect', { x: m.l, y: m.t, width: pw, height: ph, fill: 'transparent' }, svg);
    caixa.replaceChildren(svg);
    const tip = tooltip(caixa);
    const mover = (ev) => {
      const r = svg.getBoundingClientRect();
      const i = Math.max(0, Math.min(dados.length - 1, Math.floor((ev.clientX - r.left - m.l) / banda)));
      realce.setAttribute('x', m.l + banda * i); realce.setAttribute('visibility', 'visible');
      const linhas = series.map((s) => ({ cor: s.cor, nome: s.nome, valor: formatar(+dados[i][s.chave] || 0, c.formato) }));
      if (c.empilhado && series.length > 1) linhas.push({ nome: 'Total', valor: formatar(totais[i], c.formato) });
      tip.mostrar(dados[i].rotulo, linhas, m.l + banda * i + banda / 2, ev.clientY - r.top);
    };
    alvo.addEventListener('pointermove', mover);
    alvo.addEventListener('pointerdown', mover);
    alvo.addEventListener('pointerleave', () => { realce.setAttribute('visibility', 'hidden'); tip.esconder(); });
  }

  // ------------------------------------------------------------------
  // Barras horizontais (ranking) — HTML, para rótulos longos com reticências
  // ------------------------------------------------------------------
  function barrasH(caixa, c) {
    const dados = c.dados;
    if (!dados.length || !dados.some((d) => d.valor > 0)) return vazio(caixa, 160);
    const max = Math.max(...dados.map((d) => d.valor)) || 1;
    const base = dados[0].valor || 1;
    caixa.innerHTML = '<ul class="grafico-ranking">' + dados.map((d, i) => {
      const pct = (d.valor / max) * 100;
      const corBarra = d.perda ? 'grafico-perda' : (c.cores ? c.cores[i] : (c.cor || 'serie-1'));
      const extra = c.pctDoPrimeiro && i > 0 ? ` <span class="grafico-ranking-pct">${nf1.format((d.valor / base) * 100)}%</span>` : '';
      return `<li class="grafico-ranking-linha" data-i="${i}">
        <span class="grafico-ranking-rotulo" title="${esc(d.rotulo)}">${esc(d.rotulo)}</span>
        <span class="grafico-ranking-pista"><span class="grafico-ranking-barra" style="width:${Math.max(pct, d.valor > 0 ? 1.5 : 0)}%;background:${cor(corBarra)}"></span></span>
        <span class="grafico-ranking-valor">${esc(c.formato === 'kz' ? compacto(d.valor) : formatar(d.valor, c.formato))}${extra}</span>
      </li>`;
    }).join('') + '</ul>';
    const tip = tooltip(caixa);
    caixa.querySelectorAll('.grafico-ranking-linha').forEach((li) => {
      const d = dados[+li.dataset.i];
      const mostrar = (ev) => {
        const r = caixa.getBoundingClientRect();
        const linhas = [{ cor: d.perda ? 'grafico-perda' : (c.cores ? c.cores[+li.dataset.i] : (c.cor || 'serie-1')), nome: c.nomeValor || 'Valor', valor: formatar(d.valor, c.formato) }];
        (c.extras || []).forEach((x) => d[x.chave] !== undefined && linhas.push({ nome: x.nome, valor: formatar(d[x.chave], x.formato) }));
        if (c.pctDoPrimeiro) linhas.push({ nome: 'Do total criado', valor: nf1.format((d.valor / base) * 100) + '%' });
        tip.mostrar(d.rotulo, linhas, ev.clientX - r.left, ev.clientY - r.top);
      };
      li.addEventListener('pointermove', mostrar);
      li.addEventListener('pointerleave', () => tip.esconder());
    });
  }

  // ------------------------------------------------------------------
  // Donut com total ao centro + legenda com valores
  // ------------------------------------------------------------------
  function donut(caixa, c) {
    const dados = c.dados.filter((d) => d.valor > 0);
    if (!dados.length) return vazio(caixa, 220);
    const total = dados.reduce((a, d) => a + d.valor, 0);
    const S = 200, R = S / 2 - 6, r = R * 0.62, cx = S / 2, cy = S / 2;
    const corDe = (d, i) => d.rotulo === 'Outros' ? 'grafico-outros' : 'serie-' + (i + 1);
    const wrap = document.createElement('div');
    wrap.className = 'grafico-donut';
    const svg = el('svg', { width: S, height: S, viewBox: `0 0 ${S} ${S}`, class: 'grafico-svg shrink-0', role: 'img', 'aria-label': c.titulo || '' });
    const arco = (a0, a1, ro, ri) => {
      const p = (a, rr) => [cx + rr * Math.sin(a), cy - rr * Math.cos(a)];
      const g = a1 - a0 > Math.PI ? 1 : 0;
      const [x0, y0] = p(a0, ro), [x1, y1] = p(a1, ro), [x2, y2] = p(a1, ri), [x3, y3] = p(a0, ri);
      return `M${x0},${y0}A${ro},${ro} 0 ${g} 1 ${x1},${y1}L${x2},${y2}A${ri},${ri} 0 ${g} 0 ${x3},${y3}Z`;
    };
    let a = 0;
    const fatias = dados.map((d, i) => {
      const ang = (d.valor / total) * Math.PI * 2;
      const a0 = a, a1 = a + (dados.length === 1 ? ang - 1e-4 : ang);
      a += ang;
      const p = el('path', { d: arco(a0, a1, R, r), style: `fill:${cor(corDe(d, i))}`, class: 'grafico-fatia' }, svg);
      return { p, a0, a1, d, i };
    });
    el('text', { x: cx, y: cy - 2, 'text-anchor': 'middle', class: 'grafico-centro-valor' }, svg).textContent = c.formato === 'kz' ? compacto(total) : formatar(total, c.formato);
    el('text', { x: cx, y: cy + 18, 'text-anchor': 'middle', class: 'grafico-centro-rotulo' }, svg).textContent = c.centro || 'Total';
    const legenda = document.createElement('ul');
    legenda.className = 'grafico-legenda-lista';
    legenda.innerHTML = dados.map((d, i) => `<li><span class="grafico-indicador" style="background:${cor(corDe(d, i))}"></span>
      <span class="grafico-legenda-nome">${esc(d.rotulo)}</span>
      <span class="grafico-legenda-pct">${nf1.format((d.valor / total) * 100)}%</span></li>`).join('');
    wrap.append(svg, legenda);
    caixa.replaceChildren(wrap);
    const tip = tooltip(caixa);
    fatias.forEach(({ p, a0, a1, d, i }) => {
      p.addEventListener('pointermove', (ev) => {
        p.setAttribute('d', arco(a0, a1, R + 4, r));
        const rc = caixa.getBoundingClientRect();
        const linhas = [{ cor: corDe(d, i), nome: c.nomeValor || 'Valor', valor: formatar(d.valor, c.formato) },
          { nome: 'Peso', valor: nf1.format((d.valor / total) * 100) + '%' }];
        (c.extras || []).forEach((x) => d[x.chave] !== undefined && linhas.push({ nome: x.nome, valor: formatar(d[x.chave], x.formato) }));
        tip.mostrar(d.rotulo, linhas, ev.clientX - rc.left, ev.clientY - rc.top);
      });
      p.addEventListener('pointerleave', () => { p.setAttribute('d', arco(a0, a1, R, r)); tip.esconder(); });
    });
  }

  // ------------------------------------------------------------------
  // Mapa de calor (dia da semana × hora) — rampa sequencial de um só tom
  // ------------------------------------------------------------------
  function calor(caixa, c) {
    const { colunas, linhas } = c.dados;
    const max = Math.max(0, ...linhas.flatMap((l) => l.valores));
    if (!max) return vazio(caixa, 220);
    const W = caixa.clientWidth, m = { l: 38, t: 4, b: 22 }, gap = 2;
    const cw = (W - m.l) / colunas.length, ch = Math.max(16, Math.min(28, cw));
    const H = m.t + linhas.length * ch + m.b;
    const svg = el('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, class: 'grafico-svg', role: 'img', 'aria-label': c.titulo || '' });
    const passo = (v) => v === 0 ? 0 : Math.min(5, Math.ceil((v / max) * 5));
    linhas.forEach((l, r) => {
      el('text', { x: m.l - 8, y: m.t + r * ch + ch / 2 + 4, 'text-anchor': 'end', class: 'grafico-eixo' }, svg).textContent = l.rotulo;
      l.valores.forEach((v, k) => {
        const cel = el('rect', { x: m.l + k * cw + gap / 2, y: m.t + r * ch + gap / 2, width: cw - gap, height: ch - gap, rx: 3, class: 'grafico-celula', style: `fill:${cor('seq-' + passo(v))}` }, svg);
        cel.dataset.r = r; cel.dataset.k = k;
      });
    });
    const cada = cw < 22 ? 3 : cw < 34 ? 2 : 1;
    colunas.forEach((col, k) => { if (k % cada === 0) el('text', { x: m.l + k * cw + cw / 2, y: H - 6, 'text-anchor': 'middle', class: 'grafico-eixo' }, svg).textContent = col; });
    const escalaEl = document.createElement('div');
    escalaEl.className = 'grafico-escala';
    escalaEl.innerHTML = '<span>Menos</span>' + [0, 1, 2, 3, 4, 5].map((s) => `<span class="grafico-escala-passo" style="background:${cor('seq-' + s)}"></span>`).join('') + '<span>Mais</span>';
    caixa.replaceChildren(svg, escalaEl);
    const tip = tooltip(caixa);
    svg.addEventListener('pointermove', (ev) => {
      const alvo = ev.target.closest('.grafico-celula');
      if (!alvo) return tip.esconder();
      const l = linhas[+alvo.dataset.r], v = l.valores[+alvo.dataset.k];
      const rc = caixa.getBoundingClientRect();
      tip.mostrar(`${l.rotulo}, ${colunas[+alvo.dataset.k]}`, [{ cor: 'seq-' + Math.max(1, passo(v)), nome: c.nomeValor || 'Pedidos', valor: formatar(v, c.formato) }], ev.clientX - rc.left, ev.clientY - rc.top);
    });
    svg.addEventListener('pointerleave', () => tip.esconder());
  }

  // ------------------------------------------------------------------
  // Faísca (sparkline) para os cartões de indicadores
  // ------------------------------------------------------------------
  function faisca(caixa, c) {
    const H = c.altura || 40, W = caixa.clientWidth, v = c.valores || [];
    if (v.length < 2 || !v.some((x) => x > 0)) { caixa.innerHTML = ''; return; }
    const max = Math.max(...v) || 1;
    const pts = v.map((x, i) => [2 + (i * (W - 6)) / (v.length - 1), H - 3 - (x / max) * (H - 8)]);
    const svg = el('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, 'aria-hidden': 'true' });
    const id = 'f' + ++uid;
    const g = el('linearGradient', { id, x1: 0, y1: 0, x2: 0, y2: 1 }, el('defs', {}, svg));
    el('stop', { offset: '0%', style: `stop-color:${cor(c.cor || 'serie-1')};stop-opacity:.25` }, g);
    el('stop', { offset: '100%', style: `stop-color:${cor(c.cor || 'serie-1')};stop-opacity:0` }, g);
    const linha = curva(pts);
    el('path', { d: `${linha}L${pts[pts.length - 1][0]},${H}L${pts[0][0]},${H}Z`, fill: `url(#${id})` }, svg);
    el('path', { d: linha, fill: 'none', style: `stroke:${cor(c.cor || 'serie-1')}`, 'stroke-width': 2, 'stroke-linecap': 'round' }, svg);
    const u = pts[pts.length - 1];
    el('circle', { cx: u[0], cy: u[1], r: 3.5, style: `fill:${cor(c.cor || 'serie-1')}`, class: 'grafico-ponto' }, svg);
    caixa.replaceChildren(svg);
  }

  // ------------------------------------------------------------------
  // Vista em tabela (o mesmo dado, legível sem cor)
  // ------------------------------------------------------------------
  function tabela(c) {
    const th = (t, dir) => `<th class="${dir ? 'text-right' : 'text-left'}">${esc(t)}</th>`;
    const td = (t, dir) => `<td class="${dir ? 'text-right num' : ''}">${esc(t)}</td>`;
    let cab = [], linhas = [];
    if (c.tipo === 'area' || c.tipo === 'barras') {
      cab = [th(c.rotuloX || 'Período'), ...c.series.map((s) => th(s.nome, 1))];
      linhas = c.dados.map((d) => [td(d.rotulo), ...c.series.map((s) => td(formatar(+d[s.chave] || 0, c.formato), 1))]);
    } else if (c.tipo === 'calor') {
      cab = [th('Dia'), ...c.dados.colunas.map((x) => th(x, 1))];
      linhas = c.dados.linhas.map((l) => [td(l.rotulo), ...l.valores.map((v) => td(v, 1))]);
    } else {
      cab = [th('Nome'), th(c.nomeValor || 'Valor', 1), ...(c.extras || []).map((x) => th(x.nome, 1))];
      linhas = c.dados.map((d) => [td(d.rotulo), td(formatar(d.valor, c.formato), 1), ...(c.extras || []).map((x) => td(d[x.chave] !== undefined ? formatar(d[x.chave], x.formato) : '—', 1))]);
    }
    return `<div class="grafico-tabela"><table><thead><tr>${cab.join('')}</tr></thead><tbody>${linhas.map((l) => `<tr>${l.join('')}</tr>`).join('')}</tbody></table></div>`;
  }

  // ------------------------------------------------------------------
  // Arranque + redimensionamento
  // ------------------------------------------------------------------
  const tipos = { area, barras, 'barras-h': barrasH, donut, calor, faisca };

  function desenhar(caixa) {
    if (!caixa._cfg) {
      try { caixa._cfg = JSON.parse(caixa.dataset.grafico); } catch (_) { return; }
    }
    const f = tipos[caixa._cfg.tipo];
    if (f && caixa.clientWidth > 0) f(caixa, caixa._cfg);
  }

  function iniciar() {
    const caixas = [...document.querySelectorAll('[data-grafico]')];
    caixas.forEach(desenhar);
    if ('ResizeObserver' in window) {
      const larguras = new WeakMap();
      const ro = new ResizeObserver((entradas) => entradas.forEach((e) => {
        const w = Math.round(e.contentRect.width);
        if (larguras.get(e.target) === w) return;
        larguras.set(e.target, w);
        requestAnimationFrame(() => desenhar(e.target));
      }));
      caixas.forEach((c) => ro.observe(c));
    }
    // Fecha a tabela de um cartão e volta a mostrar o gráfico
    const fecharTabela = (cartao) => {
      cartao.querySelectorAll('[data-grafico-tabela-conteudo]').forEach((t) => t.remove());
      cartao.querySelectorAll('[data-grafico]').forEach((c) => { if (c.dataset.escondidoPelaTabela) { c.hidden = false; delete c.dataset.escondidoPelaTabela; } });
      cartao.querySelectorAll('[data-grafico-tabela]').forEach((b) => { b.setAttribute('aria-pressed', 'false'); b.textContent = 'Ver tabela'; });
    };

    document.addEventListener('click', (e) => {
      // Separadores (ex.: Receita / Pedidos) dentro de um cartão
      const sep = e.target.closest('[data-separador]');
      if (sep) {
        const cartao = sep.closest('[data-separadores]');
        fecharTabela(cartao);
        cartao.querySelectorAll('[data-separador]').forEach((b) => b.setAttribute('aria-selected', String(b === sep)));
        cartao.querySelectorAll('[data-painel-separador]').forEach((p) => { p.hidden = p.dataset.painelSeparador !== sep.dataset.separador; });
        return;
      }

      const b = e.target.closest('[data-grafico-tabela]');
      if (!b) return;
      const cartao = b.closest('[data-cartao-grafico]');
      if (!cartao) return;
      if (b.getAttribute('aria-pressed') === 'true') { fecharTabela(cartao); return; }
      // O gráfico visível do cartão (com separadores, só um está à vista)
      const caixa = [...cartao.querySelectorAll('[data-grafico]')].find((c) => !c.closest('[hidden]'));
      if (!caixa) return;
      const t = document.createElement('div');
      t.dataset.graficoTabelaConteudo = '';
      t.innerHTML = tabela(caixa._cfg || JSON.parse(caixa.dataset.grafico));
      caixa.after(t);
      caixa.hidden = true;
      caixa.dataset.escondidoPelaTabela = '1';
      b.setAttribute('aria-pressed', 'true');
      b.textContent = 'Ver gráfico';
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
  else iniciar();
})();
