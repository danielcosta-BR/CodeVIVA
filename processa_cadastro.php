<?php
// processa_cadastro.php

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS (COM SENHA CUSTOMIZADA)
// -----------------------------------------------------

$host = 'localhost';
$db = 'viva_db'; 
$user = 'root'; 
$pass='b@N¢0_|)Ad05';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o sistema. Tente novamente mais tarde."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E VALIDAÇÃO DE DADOS INICIAIS
// -----------------------------------------------------

$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = $_POST['senha']; 
$confirma_senha = $_POST['confirma_senha'];
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

        // ----------------------------------------------------------------------
        // ✅ INÍCIO DO AUTO-LOGIN PARA PACIENTES (Mudança aqui)
        // ----------------------------------------------------------------------
        
        // 1. Pega o ID do usuário recém-inserido
        $id_usuario = $pdo->lastInsertId();

        // 2. Inicia a sessão (necessário no processa_cadastro.php)
        // Note: É necessário adicionar session_start() no topo deste arquivo se ainda não estiver lá!
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // 3. Define as variáveis de sessão (simulando um login bem-sucedido)
        $_SESSION['id_usuario'] = $id_usuario;
        $_SESSION['nome_completo'] = $nome;
        $_SESSION['email'] = $email;
        $_SESSION['funcao'] = 'paciente';
        
        // 4. Redireciona DIRETAMENTE para o painel do Paciente
        header('Location: Usuarios/paciente.php');
        exit;
        
    } catch (PDOException $e) {
        // ... (código de tratamento de erro) ...
    }
  
} elseif ($funcao === 'enfermeiro') {

    try {
        $stmt = $pdo->prepare("INSERT INTO UsuarioPreCadastro (nome_completo, email, senha_hash) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha_hash]);

        header('Location: verificacao_enfermeiro.php?status=cadastro_sucesso&email=' . urlencode($email));
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