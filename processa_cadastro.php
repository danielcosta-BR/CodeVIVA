<?php

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS (Substitua pelos seus dados reais)
// -----------------------------------------------------

$host = 'localhost';
$db   = 'viva_db'; // Nome do seu banco de dados
$user = 'root';   // Seu usuário do MySQL
$pass = 'b@N¢0_|)Ad05';       // Sua senha do MySQL

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     // Em produção, registre o erro, mas mostre uma mensagem genérica ao usuário.
     die("Erro de conexão com o sistema. Tente novamente mais tarde."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E VALIDAÇÃO DE DADOS INICIAIS
// -----------------------------------------------------

$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = $_POST['senha']; 
$confirma_senha = $_POST['confirma_senha']; // Campo de confirmação de senha
$funcao = filter_input(INPUT_POST, 'funcao_usuario', FILTER_SANITIZE_SPECIAL_CHARS);

// Validação de campos obrigatórios
if (!$nome || !$email || empty($senha) || empty($confirma_senha) || !$funcao) {
    header('Location: register.html?erro=campos_vazios');
    exit;
}

// -----------------------------------------------------
// 3. VERIFICAÇÃO DE IGUALDADE DAS SENHAS (Novo Código de Segurança)
// -----------------------------------------------------

if ($senha !== $confirma_senha) {
    // Redireciona de volta para o formulário de registro com mensagem de erro
    header('Location: register.html?erro=senhas_nao_coincidem');
    exit;
}

// -----------------------------------------------------
// 4. HASH DA SENHA E INÍCIO DO PROCESSO DE CADASTRO
// -----------------------------------------------------

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);


if ($funcao === 'paciente') {
    // 4A: CADASTRO DIRETO para Pacientes
    try {
        // Assume id_posto NULL por padrão, pois o vínculo será feito depois ou por um Admin/Enfermeiro
        $stmt = $pdo->prepare("INSERT INTO Usuario (nome_completo, email, senha, funcao, id_posto) VALUES (?, ?, ?, 'paciente', NULL)");
        $stmt->execute([$nome, $email, $senha_hash]);
        
        // Sucesso
        header('Location: login.html?status=paciente_cadastrado');
        exit;
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Código SQLSTATE para duplicidade (UNIQUE constraint)
            header('Location: register.html?erro=email_ja_existe');
            exit;
        }
        // Em caso de outro erro, registre e redirecione
        header('Location: register.html?erro=falha_cadastro');
        // Em debug: die("Erro ao registrar paciente: " . $e->getMessage());
        exit;
    }
    
} elseif ($funcao === 'enfermeiro') {
    // 4B: PRÉ-CADASTRO TEMPORÁRIO para Enfermeiros
    try {
        // Insere os dados na tabela temporária UsuarioPreCadastro
        $stmt = $pdo->prepare("INSERT INTO UsuarioPreCadastro (nome_completo, email, senha_hash) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha_hash]);
        
        // Redireciona para a tela de verificação, passando o email na URL para pré-preenchimento
        header('Location: verificacao_enfermeiro.html?email=' . urlencode($email));
        exit;
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Código SQLSTATE para duplicidade (UNIQUE constraint)
            header('Location: register.html?erro=email_ja_existe');
            exit;
        }
        // Em caso de outro erro, registre e redirecione
        header('Location: register.html?erro=falha_pre_cadastro');
        // Em debug: die("Erro ao pré-registrar enfermeiro: " . $e->getMessage());
        exit;
    }
    
} else {
    // Função inválida (caso alguém tente manipular a requisição)
    header('Location: register.html?erro=funcao_invalida');
    exit;
}
?>