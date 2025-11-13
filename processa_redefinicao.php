<?php
// processa_redefinicao.php

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS
// ... (código de conexão PDO) ...
// -----------------------------------------------------

$host = 'localhost';
$db = 'viva_db';
$user = 'root';
$pass = 'b@N¢0_|)Ad05';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E VALIDAÇÃO DE DADOS DO FORMULÁRIO
// -----------------------------------------------------

// ATENÇÃO: Os dados 'email' e 'token' virão do formulário, que devem ser campos HIDDEN na redefinir_senha.php
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
$nova_senha = $_POST['nova_senha']; 
$confirma_senha = $_POST['confirma_senha']; 

// URL de retorno em caso de erro (com o email e token já passados)
$return_url = 'redefinir_senha.php?email=' . urlencode($email) . '&token=' . urlencode($token);

// Validação básica
if (!$email || !$token || empty($nova_senha) || empty($confirma_senha)) {
    // Redireciona de volta para a redefinição, não para o login
    header('Location: ' . $return_url . '&erro=dados_incompletos');
    exit;
}

if ($nova_senha !== $confirma_senha) {
    // Retorna para a página de redefinição com erro
    header('Location: ' . $return_url . '&erro=senhas_diferentes');
    exit;
}

// -----------------------------------------------------
// 3. VALIDAÇÃO DO TOKEN
// -----------------------------------------------------

// Busca o token que não foi usado, não expirou, e corresponde ao e-mail
$stmt_token = $pdo->prepare("
    SELECT id_recuperacao 
    FROM RecuperacaoSenha 
    WHERE email_usuario = ? 
    AND token = ? 
    AND usado = 0 
    AND expira_em > NOW() 
    LIMIT 1
");
$stmt_token->execute([$email, $token]);
$token_data = $stmt_token->fetch(PDO::FETCH_ASSOC);

if (!$token_data) {
    // Token inválido, expirado ou já usado. Manda para a página de login final.
    header('Location: login.html?erro=token_invalido_ou_expirado');
    exit;
}

$id_recuperacao = $token_data['id_recuperacao'];
$senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

// -----------------------------------------------------
// 4. ATUALIZAÇÃO DA SENHA E MARCAÇÃO DO TOKEN COMO USADO
// -----------------------------------------------------

$pdo->beginTransaction();

try {
    // 4A. Atualiza a senha do usuário
    $stmt_update_senha = $pdo->prepare("UPDATE Usuario SET senha = ? WHERE email = ?");
    $stmt_update_senha->execute([$senha_hash, $email]);

    // 4B. Marca o token como usado
    $stmt_mark_used = $pdo->prepare("UPDATE RecuperacaoSenha SET usado = 1 WHERE id_recuperacao = ?");
    $stmt_mark_used->execute([$id_recuperacao]);

    $pdo->commit(); 
    // Sucesso
    header('Location: login.html?status=senha_redefinida_sucesso');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    // Em caso de erro do banco de dados, retorna para a redefinição (e o token ainda não foi marcado como usado)
    header('Location: ' . $return_url . '&erro=falha_redefinicao');
    exit;
}

?>