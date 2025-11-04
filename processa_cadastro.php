<?php

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS (COM SENHA CUSTOMIZADA)
// -----------------------------------------------------

$host = 'localhost';
$db   = 'viva_db'; 
$user = 'root';   
$pass = 'b@N¢0_|)Ad05'; // SUA SENHA

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

// Correção: Usando 'nome' como nome do campo (conforme register.html)
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = $_POST['senha']; 
$confirma_senha = $_POST['confirma_senha'];
// Correção: Usando 'funcao_usuario' (conforme register.html)
$funcao = filter_input(INPUT_POST, 'funcao_usuario', FILTER_SANITIZE_SPECIAL_CHARS);

// Validação de campos obrigatórios
if (!$nome || !$email || empty($senha) || empty($confirma_senha) || !$funcao) {
    header('Location: register.html?erro=campos_vazios');
    exit;
}

// -----------------------------------------------------
// 3. VERIFICAÇÃO DE IGUALDADE DAS SENHAS
// -----------------------------------------------------

if ($senha !== $confirma_senha) {
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
        $stmt = $pdo->prepare("INSERT INTO Usuario (nome_completo, email, senha, funcao, id_posto) VALUES (?, ?, ?, 'paciente', NULL)");
        $stmt->execute([$nome, $email, $senha_hash]);
        
        header('Location: login.html?status=paciente_cadastrado');
        exit;
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicidade
            header('Location: register.html?erro=email_ja_existe');
            exit;
        }
        header('Location: register.html?erro=falha_cadastro');
        exit;
    }
    
} elseif ($funcao === 'enfermeiro') {
    // 4B: PRÉ-CADASTRO TEMPORÁRIO para Enfermeiros
    try {
        $stmt = $pdo->prepare("INSERT INTO UsuarioPreCadastro (nome_completo, email, senha_hash) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha_hash]);
        
        // Redireciona para a tela de verificação
        header('Location: verificacao_enfermeiro.html?email=' . urlencode($email));
        exit;
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicidade
            header('Location: register.html?erro=email_ja_existe');
            exit;
        }
        header('Location: register.html?erro=falha_pre_cadastro');
        exit;
    }
    
} else {
    header('Location: register.html?erro=funcao_invalida');
    exit;
}
?>