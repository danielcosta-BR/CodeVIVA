<?php
// processa_recuperacao.php (Na pasta raiz)

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS (COM SENHA CUSTOMIZADA)
// -----------------------------------------------------

$host = 'localhost';
$db = 'viva_db';
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
     // Redireciona para o sucesso simulado, por segurança (não diz se o email existe ou não)
     header('Location: forgot-password.html?status=processo_iniciado');
     exit;
}

// -----------------------------------------------------
// 4. GERAÇÃO E ARMAZENAMENTO DO TOKEN
// -----------------------------------------------------

$token = bin2hex(random_bytes(32)); 
// 3600 segundos = 1 hora
$expira_em = date("Y-m-d H:i:s", time() + 3600); 

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

// Construindo o caminho REAL da URL do seu projeto!
// Substitua "redefinir_senha.php" pelo nome do seu arquivo de redefinição
$url_base = "http://localhost/projetoviva+/CodeVIVA/"; 
$recuperacao_url = $url_base . "redefinir_senha.php?email=" . urlencode($email) . "&token=" . $token;

// [Função de Envio de E-mail entra aqui]

// -------------------------------------------------------------------------------------------------
// ✅ MUDANÇA CRUCIAL: Exibir o link se não for possível enviar o e-mail (ambiente local)
// -------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Link de Recuperação de Senha</title>
    <link rel='stylesheet' type='text/css' media='screen' href='recuperacao.css'>
</head>
<body>

    <div class="box">
        <h2>Simulação de Envio de E-mail para Recuperação de Senha</h2>
        <p class="alert-success">Instruções de redefinição enviadas para <span id="emailstyle"><?php echo htmlspecialchars($email); ?></span>.</p>
        
        <p><strong>ATENÇÃO:</strong> Como esta é uma simulação, você deve copiar o link abaixo e colar na barra de endereços do seu navegador para continuar o processo de redefinição:</p>
        
        <p><strong>LINK DE REDEFINIÇÃO:</strong></p>
        <p><a href="<?php echo htmlspecialchars($recuperacao_url); ?>"><?php echo htmlspecialchars($recuperacao_url); ?></a></p>
        
        <p>O token expira em <?php echo $expira_em; ?>.</p>
    </div>
</body>
</html>
<?php
// O exit é removido aqui para que o HTML seja exibido em vez de redirecionar
exit; 
?>