<?php
// =====================================================================
// core/Validador.php — Validação de formulários com mensagens em PT.
// Uso:
//   $v = new Validador($_POST);
//   $v->obrigatorio('nome', 'Indique o seu nome.')->email('email');
//   if ($v->falhou()) { ... $v->erros() ... }
//   $dados = $v->dados();   // valores higienizados
// =====================================================================

namespace Core;

class Validador
{
    private array $erros = [];
    private array $dados = [];

    public function __construct(private array $input) {}

    private function valor(string $campo): string
    {
        return trim((string) ($this->input[$campo] ?? ''));
    }

    private function erro(string $campo, string $msg): self
    {
        $this->erros[$campo] ??= $msg;
        return $this;
    }

    public function texto(string $campo, int $max = 255, bool $obrigatorio = true, ?string $msg = null): self
    {
        $v = preg_replace('/\s+/u', ' ', $this->valor($campo));
        if ($obrigatorio && $v === '') {
            return $this->erro($campo, $msg ?? 'Este campo é obrigatório.');
        }
        if (mb_strlen($v) > $max) {
            return $this->erro($campo, "Use no máximo $max caracteres.");
        }
        $this->dados[$campo] = $v;
        return $this;
    }

    public function nome(string $campo): self
    {
        $this->texto($campo, 140, true, 'Indique o nome completo.');
        if (!isset($this->erros[$campo]) && !str_contains($this->dados[$campo], ' ')) {
            $this->erro($campo, 'Indique o nome e o apelido.');
        }
        return $this;
    }

    public function email(string $campo): self
    {
        $v = mb_strtolower($this->valor($campo));
        if ($v === '') {
            return $this->erro($campo, 'Indique o seu email.');
        }
        if (!filter_var($v, FILTER_VALIDATE_EMAIL) || mb_strlen($v) > 160) {
            return $this->erro($campo, 'O email não parece válido. Exemplo: nome@gmail.com');
        }
        $this->dados[$campo] = $v;
        return $this;
    }

    public function telefone(string $campo, bool $obrigatorio = true): self
    {
        $v = $this->valor($campo);
        if ($v === '' && !$obrigatorio) {
            $this->dados[$campo] = null;
            return $this;
        }
        $norm = telefoneAngola($v);
        if (!$norm) {
            return $this->erro($campo, 'Indique um número angolano com 9 dígitos, a começar por 9. Exemplo: 923 456 789');
        }
        $this->dados[$campo] = $norm;
        return $this;
    }

    public function nif(string $campo): self
    {
        $v = strtoupper(preg_replace('/\s+/', '', $this->valor($campo)));
        if ($v !== '' && !preg_match('/^[0-9A-Z]{9,14}$/', $v)) {
            return $this->erro($campo, 'O NIF deve ter entre 9 e 14 letras ou números, sem espaços.');
        }
        $this->dados[$campo] = $v;
        return $this;
    }

    public function senha(string $campo, ?string $confirmacao = null): self
    {
        $v = (string) ($this->input[$campo] ?? '');
        if (mb_strlen($v) < 8) {
            return $this->erro($campo, 'A senha precisa de pelo menos 8 caracteres.');
        }
        if (!preg_match('/\d/', $v) || !preg_match('/\p{L}/u', $v)) {
            return $this->erro($campo, 'Use letras e pelo menos um número.');
        }
        if ($confirmacao !== null && $v !== (string) ($this->input[$confirmacao] ?? '')) {
            return $this->erro($confirmacao, 'As senhas não coincidem.');
        }
        $this->dados[$campo] = $v;
        return $this;
    }

    public function emLista(string $campo, array $permitidos, string $msg): self
    {
        $v = $this->valor($campo);
        if (!in_array($v, $permitidos, true)) {
            return $this->erro($campo, $msg);
        }
        $this->dados[$campo] = $v;
        return $this;
    }

    public function booleano(string $campo): self
    {
        $this->dados[$campo] = !empty($this->input[$campo]);
        return $this;
    }

    public function adicionarErro(string $campo, string $msg): self
    {
        return $this->erro($campo, $msg);
    }

    public function falhou(): bool { return (bool) $this->erros; }
    public function erros(): array { return $this->erros; }
    public function dados(): array { return $this->dados; }
}
