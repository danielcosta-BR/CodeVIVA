<?php
// processa_login.php (Na pasta raiz)

session_start();

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
     die("Erro de conexão com o sistema. Tente novamente mais tarde."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E SANITIZAÇÃO DE DADOS
// -----------------------------------------------------

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha_digitada = $_POST['senha']; 

if (!$email || empty($senha_digitada)) {
     header('Location: login.html?erro=campos_vazios');
     exit;
}

// -----------------------------------------------------
// 3. BUSCA E AUTENTICAÇÃO DO USUÁRIO
// -----------------------------------------------------

try {
     $stmt = $pdo->prepare("SELECT id_usuario, nome_completo, senha, funcao FROM Usuario WHERE email = ? LIMIT 1");
     $stmt->execute([$email]);
     $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

     if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
          
          // Sucesso na autenticação!

          // -----------------------------------------------------
          // 4. INÍCIO DE SESSÃO E REDIRECIONAMENTO POR FUNÇÃO
          // -----------------------------------------------------
          
          $_SESSION['id_usuario'] = $usuario['id_usuario'];
          $_SESSION['nome_completo'] = $usuario['nome_completo'];
          $_SESSION['funcao'] = $usuario['funcao'];
          
          $funcao = $usuario['funcao'];

          if ($funcao === 'administrador') {
               header('Location: Usuários/administrador.php');
               exit;
          } elseif ($funcao === 'enfermeiro') {
               header('Location: Usuários/enfermeiro.php');
               exit;
          } elseif ($funcao === 'paciente') {
               header('Location: Usuários/paciente.php');
               exit;
          } else {
               session_destroy();
               header('Location: login.html?erro=funcao_desconhecida');
               exit;
          }

     } else {
          // Senha ou E-mail incorreto (por segurança, trate ambos da mesma forma)
          header('Location: login.html?erro=credenciais_invalidas');
          exit;
     }

} catch (PDOException $e) {
     header('Location: login.html?erro=falha_sistema');
     exit;
}
?>