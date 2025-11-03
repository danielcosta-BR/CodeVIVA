<?php
// Inicia a sessão para armazenar informações do usuário após o login.
session_start();

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
     die("Erro de conexão com o sistema. Tente novamente mais tarde."); 
}

// -----------------------------------------------------
// 2. RECEBIMENTO E SANITIZAÇÃO DE DADOS
// -----------------------------------------------------

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha_digitada = $_POST['senha']; 

if (!$email || empty($senha_digitada)) {
    // Redireciona com erro se os campos estiverem vazios
    header('Location: login.html?erro=campos_vazios');
    exit;
}

// -----------------------------------------------------
// 3. BUSCA E AUTENTICAÇÃO DO USUÁRIO
// -----------------------------------------------------

try {
    // Busca o usuário pelo e-mail (incluindo a senha com hash e a função)
    $stmt = $pdo->prepare("SELECT id_usuario, nome_completo, senha, funcao FROM Usuario WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3A. Verifica se o usuário foi encontrado
    if ($usuario) {
        // 3B. Verifica se a senha digitada corresponde ao hash armazenado no DB
        if (password_verify($senha_digitada, $usuario['senha'])) {
            
            // Sucesso na autenticação!

            // -----------------------------------------------------
            // 4. INÍCIO DE SESSÃO E REDIRECIONAMENTO POR FUNÇÃO
            // -----------------------------------------------------
            
            // Armazena dados essenciais na sessão
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nome_completo'] = $usuario['nome_completo'];
            $_SESSION['funcao'] = $usuario['funcao'];
            
            $funcao = $usuario['funcao'];

            // Define o caminho de redirecionamento com base na função
            // ATENÇÃO: Os caminhos agora incluem a pasta "Usuarios/"
            if ($funcao === 'administrador') {
                header('Location: Usuarios/administrador.php');
                exit;
            } elseif ($funcao === 'enfermeiro') {
                header('Location: Usuarios/enfermeiro.php');
                exit;
            } elseif ($funcao === 'paciente') {
                header('Location: Usuarios/paciente.php');
                exit;
            } else {
                // Caso a função seja desconhecida
                session_destroy();
                header('Location: login.html?erro=funcao_desconhecida');
                exit;
            }

        } else {
            // Senha incorreta
            header('Location: login.html?erro=credenciais_invalidas');
            exit;
        }

    } else {
        // Usuário não encontrado
        header('Location: login.html?erro=credenciais_invalidas');
        exit;
    }

} catch (PDOException $e) {
    // Erro durante a consulta ao banco
    header('Location: login.html?erro=falha_sistema');
    // Em debug: die("Erro no login: " . $e->getMessage());
    exit;
}

?>