<?php
// =====================================================================
// core/Upload.php — Recepção segura de imagens de produto/banner.
// Valida pelo conteúdo (getimagesize), limita tamanho e dimensões,
// gera nome aleatório e grava em assets/img/<pasta>/.
// =====================================================================

namespace Core;

use RuntimeException;

class Upload
{
    private const TIPOS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    private const MAX_BYTES = 4 * 1024 * 1024;

    /**
     * Normaliza $_FILES['campo'] (simples ou múltiplo) numa lista.
     */
    public static function lista(?array $f): array
    {
        if (!$f || !isset($f['name'])) {
            return [];
        }
        if (!is_array($f['name'])) {
            return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
        }
        $lista = [];
        foreach ($f['name'] as $i => $_) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $lista[] = ['name' => $f['name'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $lista;
    }

    /** Grava a imagem e devolve o caminho relativo a assets/img/ (ex.: "produtos/uploads/abc.jpg"). */
    public static function imagem(array $f, string $pasta, string $prefixo = ''): string
    {
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > self::MAX_BYTES) {
            throw new RuntimeException("A imagem {$f['name']} tem mais de 4 MB. Reduza-a e tente de novo.");
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new RuntimeException("Não foi possível receber {$f['name']}. Tente de novo.");
        }
        $info = @getimagesize($f['tmp_name']);
        if (!$info || !isset(self::TIPOS[$info[2]])) {
            throw new RuntimeException("{$f['name']} não é uma imagem JPG, PNG ou WEBP válida.");
        }
        if ($info[0] > 6000 || $info[1] > 6000) {
            throw new RuntimeException("{$f['name']} é demasiado grande (máximo 6000 × 6000 píxeis).");
        }

        $destino = RAIZ . '/assets/img/' . trim($pasta, '/');
        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }
        $nome = ($prefixo ? slugify($prefixo) . '-' : '') . bin2hex(random_bytes(5)) . '.' . self::TIPOS[$info[2]];
        if (!move_uploaded_file($f['tmp_name'], $destino . '/' . $nome)) {
            throw new RuntimeException("Não foi possível guardar {$f['name']}.");
        }
        return trim($pasta, '/') . '/' . $nome;
    }

    public static function apagar(?string $relativo): void
    {
        if (!$relativo || str_contains($relativo, '..')) {
            return;
        }
        $caminho = RAIZ . '/assets/img/' . $relativo;
        if (is_file($caminho) && str_contains($relativo, '/uploads/')) {
            @unlink($caminho);
        }
    }
}
