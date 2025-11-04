<?php
// processa_recuperacao.php (Na pasta raiz)

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
     die("Erro de conexão com o banco de dados."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E VALIDAÇÃO DE DADOS
// -----------------------------------------------------

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
     header('Location: forgot-password.html?erro=email_invalido');
     exit;
}

// -----------------------------------------------------
// 3. VERIFICAÇÃO DE EXISTÊNCIA DO USUÁRIO
// -----------------------------------------------------

$stmt_user = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE email = ?");
$stmt_user->execute([$email]);
$user_exists = $stmt_user->fetchColumn();

if ($user_exists == 0) {
     // Redireciona para o sucesso simulado, por segurança
     header('Location: forgot-password.html?status=processo_iniciado');
     exit;
}

// -----------------------------------------------------
// 4. GERAÇÃO E ARMAZENAMENTO DO TOKEN
// -----------------------------------------------------

$token = bin2hex(random_bytes(32)); 
$expira_em = date("Y-m-d H:i:s", time() + 3600); // 1 hora

try {
     $pdo->beginTransaction();

     // Limpa tokens antigos não usados
     $stmt_clean = $pdo->prepare("DELETE FROM RecuperacaoSenha WHERE email_usuario = ? AND usado = 0");
     $stmt_clean->execute([$email]);

     // Insere o novo token
     $stmt_insert = $pdo->prepare("INSERT INTO RecuperacaoSenha (email_usuario, token, expira_em, usado) VALUES (?, ?, ?, 0)");
     $stmt_insert->execute([$email, $token, $expira_em]);

     $pdo->commit();

} catch (PDOException $e) {
     $pdo->rollBack();
     header('Location: forgot-password.html?erro=falha_sistema');
     exit;
}

// -----------------------------------------------------
// 5. SIMULAÇÃO DE ENVIO DE E-MAIL E REDIRECIONAMENTO
// -----------------------------------------------------

// ATENÇÃO: Se for usar redefinir_senha.html, precisa renomeá-lo para .php
$recuperacao_url = "http://localhost/seu-projeto/redefinir_senha.php?email=" . urlencode($email) . "&token=" . $token;

// [Função de Envio de E-mail entra aqui]

header('Location: forgot-password.html?status=instrucoes_enviadas');
exit;
?>