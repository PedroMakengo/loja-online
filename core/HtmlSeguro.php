<?php
// =====================================================================
// core/HtmlSeguro.php — Limpa o HTML das descrições de produto:
// mantém só formatação básica e ligações http(s). Remove scripts,
// estilos, atributos on*, iframes, etc. (protecção contra XSS)
// =====================================================================

namespace Core;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSeguro
{
    private const PERMITIDAS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'a', 'blockquote', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
    private const REMOVER_COM_CONTEUDO = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'noscript', 'template'];

    public static function limpar(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        // Texto sem tags: converte parágrafos (linha em branco) e quebras
        if ($html === strip_tags($html)) {
            $paragrafos = preg_split('/\R{2,}/', $html);
            return implode('', array_map(fn ($p) => '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8'), false) . '</p>', $paragrafos));
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $raiz = $doc->getElementById('raiz');
        if (!$raiz) {
            return '';
        }
        self::filtrar($raiz);

        $saida = '';
        foreach ($raiz->childNodes as $filho) {
            $saida .= $doc->saveHTML($filho);
        }
        return trim($saida);
    }

    private static function filtrar(DOMNode $no): void
    {
        foreach (iterator_to_array($no->childNodes) as $filho) {
            if ($filho instanceof DOMElement) {
                $tag = strtolower($filho->tagName);
                if (in_array($tag, self::REMOVER_COM_CONTEUDO, true)) {
                    $no->removeChild($filho);
                    continue;
                }
                self::filtrar($filho);
                if (!in_array($tag, self::PERMITIDAS, true)) {
                    // Desembrulha: mantém o texto, remove a tag
                    while ($filho->firstChild) {
                        $no->insertBefore($filho->firstChild, $filho);
                    }
                    $no->removeChild($filho);
                    continue;
                }
                $href = $tag === 'a' ? trim($filho->getAttribute('href')) : '';
                foreach (iterator_to_array($filho->attributes) as $attr) {
                    $filho->removeAttribute($attr->name);
                }
                if ($tag === 'a') {
                    if (preg_match('#^(https?://|/|mailto:)#i', $href)) {
                        $filho->setAttribute('href', $href);
                        if (str_starts_with(strtolower($href), 'http')) {
                            $filho->setAttribute('rel', 'noopener nofollow');
                            $filho->setAttribute('target', '_blank');
                        }
                    }
                }
            } elseif ($filho->nodeType === XML_COMMENT_NODE || $filho->nodeType === XML_PI_NODE) {
                $no->removeChild($filho);
            }
        }
    }
}
