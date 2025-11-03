<?php

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS (Simulação - Preencher depois)
// -----------------------------------------------------

// Substitua estas variáveis pelos seus dados reais de conexão
$host = 'localhost';
$db   = 'viva_db';
$user = 'root';
$pass = 'b@N¢0_|)Ad05';

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     // Em produção, você deve logar o erro e mostrar uma mensagem genérica.
     die("Erro de conexão com o banco de dados."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E VALIDAÇÃO DE DADOS
// -----------------------------------------------------

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    // Redireciona de volta se o email for inválido
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
    // É uma boa prática de segurança não informar se o email existe ou não,
    // apenas indicar que o processo foi iniciado.
    // Redirecionamos para o sucesso simulado, mas sem processar o token.
    header('Location: forgot-password.html?status=processo_iniciado');
    exit;
}

// -----------------------------------------------------
// 4. GERAÇÃO E ARMAZENAMENTO DO TOKEN
// -----------------------------------------------------

// Gera um token criptograficamente seguro e aleatório (64 caracteres)
$token = bin2hex(random_bytes(32)); 

// Define o tempo de expiração do token (ex: 1 hora a partir de agora)
$expira_em = date("Y-m-d H:i:s", time() + 3600); // 3600 segundos = 1 hora

try {
    // 4A. Inicia uma transação para garantir limpeza de tokens antigos
    $pdo->beginTransaction();

    // 4B. Limpa tokens antigos ou não usados para este e-mail (Segurança: evita múltiplos tokens válidos)
    $stmt_clean = $pdo->prepare("DELETE FROM RecuperacaoSenha WHERE email_usuario = ? AND usado = 0");
    $stmt_clean->execute([$email]);

    // 4C. Insere o novo token no banco de dados
    $stmt_insert = $pdo->prepare("INSERT INTO RecuperacaoSenha (email_usuario, token, expira_em, usado) VALUES (?, ?, ?, 0)");
    $stmt_insert->execute([$email, $token, $expira_em]);

    // Finaliza a transação
    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    // Em caso de erro, redireciona para uma falha genérica
    header('Location: forgot-password.html?erro=falha_sistema');
    exit;
}

// -----------------------------------------------------
// 5. SIMULAÇÃO DE ENVIO DE E-MAIL E REDIRECIONAMENTO
// -----------------------------------------------------

// A URL que o usuário receberia no e-mail:
$recuperacao_url = "http://seusite.com/redefinir_senha.php?email=" . urlencode($email) . "&token=" . $token;

/*
|--------------------------------------------------------------------------
| AQUI ENTRARIA A FUNÇÃO PHP PARA ENVIO DE E-MAIL
|--------------------------------------------------------------------------
|
| Nesta etapa, você usaria PHPMailer ou a função mail() do PHP
| para enviar um e-mail para o '$email' contendo a '$recuperacao_url'.
|
| Como não temos a função de e-mail, vamos SIMULAR a resposta de sucesso.
*/

// Redireciona o usuário para uma página de sucesso, informando que
// as instruções foram enviadas (ou que ele deve verificar o token manualmente).
header('Location: forgot-password.html?status=instrucoes_enviadas');
exit;

// -----------------------------------------------------
// MENSAGEM DE ERRO/SUCESSO PARA DEBUG (Remover em produção)
// -----------------------------------------------------
/*
echo "O processo de recuperação foi iniciado para: " . $email . "<br>";
echo "Este é o link que seria enviado por e-mail: <a href='{$recuperacao_url}'>Redefinir Senha</a>";
*/

?>