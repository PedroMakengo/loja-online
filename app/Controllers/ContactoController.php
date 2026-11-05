<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Contacto;
use App\Models\Newsletter;

class ContactoController extends Controller
{
    public function index(): void
    {
        $this->view('paginas/contactos', ['seo' => ['titulo' => 'Contactos', 'descricao' => 'Fale com a DIOTEKNO por WhatsApp, telefone ou email. Loja física em Luanda.']]);
    }

    public function enviar(): void
    {
        // Armadilha anti-spam
        if (!empty($_POST['website'])) {
            $this->json(['ok' => true, 'mensagem' => 'Mensagem enviada.']);
        }

        $dados = [
            'nome'     => trim(mb_substr((string) ($_POST['nome'] ?? ''), 0, 120)),
            'email'    => trim(mb_substr((string) ($_POST['email'] ?? ''), 0, 160)),
            'telefone' => trim(mb_substr((string) ($_POST['telefone'] ?? ''), 0, 30)) ?: null,
            'assunto'  => trim(mb_substr((string) ($_POST['assunto'] ?? ''), 0, 120)),
            'mensagem' => trim(mb_substr((string) ($_POST['mensagem'] ?? ''), 0, 3000)),
        ];

        if ($dados['nome'] === '' || $dados['mensagem'] === '' || $dados['assunto'] === '') {
            $this->json(['ok' => false, 'mensagem' => 'Preencha o nome, o assunto e a mensagem.'], 422);
        }
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'mensagem' => 'O email não parece válido. Confirme e tente de novo.'], 422);
        }

        (new Contacto())->inserir($dados);
        $this->json(['ok' => true, 'mensagem' => 'Mensagem enviada. Respondemos em até 24 horas úteis.']);
    }

    public function newsletter(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'mensagem' => 'Indique um email válido.'], 422);
        }
        $novo = (new Newsletter())->subscrever($email, mb_substr((string) ($_POST['origem'] ?? 'site'), 0, 40));
        $this->json(['ok' => true, 'mensagem' => $novo ? 'Subscrição feita. A primeira mensagem chega esta semana.' : 'Este email já está subscrito.']);
    }
}
