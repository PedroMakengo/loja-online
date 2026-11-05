<?php
// =====================================================================
// core/helpers.php — Funções globais usadas nas views e controllers
// =====================================================================

/** Escapa HTML. */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL interno, respeitando a subpasta onde o projecto está instalado. */
function url(string $caminho = '/'): string
{
    return BASE_PATH . '/' . ltrim($caminho, '/');
}

/** URL de um asset com versão para controlo de cache. */
function asset(string $caminho): string
{
    return BASE_PATH . '/assets/' . ltrim($caminho, '/') . '?v=' . VERSAO_ASSETS;
}

/** Preço em Kwanzas: 389900 -> "389 900 Kz" */
function kz(float|int|string|null $valor): string
{
    // Formato angolano: 349.900,00 Kz
    return number_format((float) $valor, 2, ',', '.') . "\u{00A0}" . MOEDA;
}

/** Percentagem de desconto entre preço anterior e actual. */
function descontoPercent(?float $anterior, float $actual): int
{
    if (!$anterior || $anterior <= $actual) {
        return 0;
    }
    return (int) round((1 - $actual / $anterior) * 100);
}

/** Slug a partir de um texto em português. */
function slugify(string $texto): string
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $texto = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $texto));
    return trim($texto, '-');
}

/** Pedido AJAX (fetch envia X-Requested-With: fetch no app.js). */
function eAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']);
}

/** Caminho activo — para marcar links no menu. */
function rotaActiva(string $prefixo): bool
{
    $caminho = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if (BASE_PATH !== '' && str_starts_with($caminho, BASE_PATH)) {
        $caminho = substr($caminho, strlen(BASE_PATH)) ?: '/';
    }
    return $prefixo === '/' ? $caminho === '/' : str_starts_with($caminho, $prefixo);
}

/** Imagem do produto ou ilustração da categoria. */
function imagemProduto(array $p): string
{
    if (!empty($p['imagem'])) {
        return str_starts_with($p['imagem'], 'http') ? $p['imagem'] : asset('img/produtos/' . $p['imagem']);
    }
    return imagemCategoria($p['categoria_slug'] ?? 'acessorios');
}

/** Fotografia que representa a categoria (assets/img/categorias/<slug>.webp). */
function imagemCategoria(string $slug): string
{
    $slug = is_file(RAIZ . "/assets/img/categorias/$slug.webp") ? $slug : 'acessorios';
    return asset("img/categorias/$slug.webp");
}

/** Link "Falar no WhatsApp" com mensagem pré-preenchida. */
function linkWhatsapp(string $mensagem = ''): string
{
    return 'https://wa.me/' . WHATSAPP_LOJA . ($mensagem ? '?text=' . rawurlencode($mensagem) : '');
}

/** Ícone SVG inline (definidos em app/Views/partials/icones.php). */
function icone(string $nome, string $classes = 'h-5 w-5'): string
{
    static $icones = null;
    $icones ??= require RAIZ . '/app/Views/partials/icones.php';
    $path = $icones[$nome] ?? '';
    return '<svg class="' . e($classes) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** URL actual com parâmetros GET alterados (null remove). Para paginação e filtros. */
function urlActual(array $mudar = []): string
{
    $caminho = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $params  = array_merge($_GET, $mudar);
    $params  = array_filter($params, fn ($v) => $v !== null && $v !== '' && $v !== []);
    $qs      = http_build_query($params);
    return $caminho . ($qs ? '?' . $qs : '');
}

/** Valor antigo de um campo (depois de erro de validação). */
function antigo(string $campo, string $padrao = ''): string
{
    return e((string) (\Core\Flash::antigo()[$campo] ?? $padrao));
}

/** Mensagem de erro de um campo, com o HTML pronto (ou string vazia). */
function erroCampo(string $campo): string
{
    $erro = \Core\Flash::erros()[$campo] ?? null;
    return $erro ? '<p class="mt-1.5 text-sm text-coral" id="erro-' . e($campo) . '">' . e($erro) . '</p>' : '';
}

/** Atributos de acessibilidade para campos com erro. */
function ariaErro(string $campo): string
{
    return isset(\Core\Flash::erros()[$campo]) ? 'aria-invalid="true" aria-describedby="erro-' . e($campo) . '"' : '';
}

/** Caminho interno seguro para redirecção (evita open redirect). */
function caminhoSeguro(?string $caminho, string $padrao = '/conta'): string
{
    $caminho = (string) $caminho;
    if ($caminho === '' || !str_starts_with($caminho, '/') || str_starts_with($caminho, '//') || str_contains($caminho, '\\')) {
        return $padrao;
    }
    if (BASE_PATH !== '' && str_starts_with($caminho, BASE_PATH)) {
        $caminho = substr($caminho, strlen(BASE_PATH)) ?: '/';
    }
    return $caminho;
}

/** Normaliza telefone angolano: "923 000 000" -> "+244923000000". Devolve null se inválido. */
function telefoneAngola(?string $tel): ?string
{
    $d = preg_replace('/\D+/', '', (string) $tel);
    if (str_starts_with($d, '00244')) { $d = substr($d, 5); }
    if (str_starts_with($d, '244'))   { $d = substr($d, 3); }
    return preg_match('/^9\d{8}$/', $d) ? '+244' . $d : null;
}

/** "+244923000000" -> "923 000 000" */
function telefoneFormatado(?string $tel): string
{
    $d = preg_replace('/^\+?244/', '', (string) $tel);
    return preg_match('/^\d{9}$/', $d) ? substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6) : (string) $tel;
}

/** Data por extenso em português: "março de 2026". */
function strftime_pt(string $data, bool $comDia = false): string
{
    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $t = strtotime($data);
    return ($comDia ? date('j', $t) . ' de ' : '') . $meses[(int) date('n', $t) - 1] . ' de ' . date('Y', $t);
}

/** IDs dos produtos favoritos do cliente com sessão (carregados uma vez por pedido). */
function favoritosIds(): array
{
    static $ids = null;
    if ($ids === null) {
        $id  = \Core\Auth::id();
        $ids = $id ? (new \App\Models\Favorito())->ids($id) : [];
    }
    return $ids;
}

/** "1 pedido" / "3 pedidos". */
function plural(int|float|string $n, string $singular, string $plural): string
{
    $n = (int) $n;
    return $n . ' ' . ($n === 1 ? $singular : $plural);
}
