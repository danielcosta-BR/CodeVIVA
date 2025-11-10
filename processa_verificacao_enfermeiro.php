<?php
// processa_verificacao_enfermeiro.php (NA PASTA RAIZ)

session_start(); 

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
     header('Location: verificacao_enfermeiro.php?erro=falha_sistema');
     exit;
}

// -----------------------------------------------------
// 2. RECEBIMENTO E SANITIZAÇÃO DE DADOS
// -----------------------------------------------------

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
// Assume que o código é alfanumérico e força maiúsculas
$codigo_digitado = strtoupper(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_SPECIAL_CHARS)); 

if (!$email || empty($codigo_digitado)) {
     header('Location: verificacao_enfermeiro.php?erro=campos_vazios');
     exit;
}

// -----------------------------------------------------
// 3. INÍCIO DA TRANSAÇÃO (Garanta que tudo ou nada seja executado)
// -----------------------------------------------------

$pdo->beginTransaction();

try {
     // 4. VERIFICAÇÃO DO CÓDIGO (Deve existir e não estar usado)
     $stmt_codigo = $pdo->prepare("SELECT id_codigo, usado FROM CodigoVerificacao WHERE codigo = ? AND funcao_alvo = 'enfermeiro' LIMIT 1");
     $stmt_codigo->execute([$codigo_digitado]);
     $codigo_info = $stmt_codigo->fetch(PDO::FETCH_ASSOC);

     if (!$codigo_info || $codigo_info['usado']) {
          $pdo->rollBack();
          header('Location: verificacao_enfermeiro.php?erro=codigo_invalido');
          exit;
     }

     $id_codigo = $codigo_info['id_codigo'];

     // 5. BUSCA DADOS DO PRÉ-CADASTRO (Deve existir o e-mail)
     $stmt_pre = $pdo->prepare("SELECT id_pre_cadastro, nome_completo, senha_hash FROM UsuarioPreCadastro WHERE email = ? LIMIT 1");
     $stmt_pre->execute([$email]);
     $pre_cadastro = $stmt_pre->fetch(PDO::FETCH_ASSOC);

     if (!$pre_cadastro) {
          $pdo->rollBack();
          header('Location: verificacao_enfermeiro.php?erro=email_nao_encontrado');
          exit;
     }

     // 6. INSERÇÃO FINAL NA TABELA 'USUARIO'
     $stmt_insert = $pdo->prepare("
          INSERT INTO Usuario (nome_completo, email, senha, funcao, id_posto) 
          VALUES (?, ?, ?, 'enfermeiro', NULL)
     ");
     $stmt_insert->execute([$pre_cadastro['nome_completo'], $email, $pre_cadastro['senha_hash']]);

     // 7. ATUALIZAÇÃO E LIMPEZA

     // Marca o código como USADO
     $stmt_update_codigo = $pdo->prepare("UPDATE CodigoVerificacao SET usado = 1, data_uso = NOW() WHERE id_codigo = ?");
     $stmt_update_codigo->execute([$id_codigo]);

     // Deleta o registro do pré-cadastro
     $stmt_delete_pre = $pdo->prepare("DELETE FROM UsuarioPreCadastro WHERE id_pre_cadastro = ?");
     $stmt_delete_pre->execute([$pre_cadastro['id_pre_cadastro']]);

     // 8. FINALIZAÇÃO DA TRANSAÇÃO

     $pdo->commit();

     // -----------------------------------------------------------------
     // ✅ INÍCIO DO AUTO-LOGIN PARA ENFERMEIRO (MUDANÇAS APLICADAS AQUI)
     // -----------------------------------------------------------------

     // 1. Busca os dados do usuário recém-criado na tabela final
     $stmt_user = $pdo->prepare("SELECT id_usuario, nome_completo, funcao FROM Usuario WHERE email = ? LIMIT 1");
     $stmt_user->execute([$email]);
     $usuario_final = $stmt_user->fetch(PDO::FETCH_ASSOC);

     if ($usuario_final) {
          // 2. Define as variáveis de sessão para logar o usuário
          $_SESSION['id_usuario'] = $usuario_final['id_usuario'];
          $_SESSION['nome_completo'] = $usuario_final['nome_completo'];
          $_SESSION['email'] = $email;
          $_SESSION['funcao'] = $usuario_final['funcao'];

          // 3. Redireciona DIRETAMENTE para o painel do Enfermeiro
          header('Location: Usuários/enfermeiro.php');
          exit;
     }

     // Se por algum motivo o auto-login falhar, redireciona para a tela de login
     header('Location: login.html?status=enfermeiro_verificado');
     exit;

} catch (PDOException $e) {
     $pdo->rollBack();
     // Você pode usar $e->getMessage() para debug se necessário
     header('Location: verificacao_enfermeiro.php?erro=falha_processamento');
     exit;
}
?>