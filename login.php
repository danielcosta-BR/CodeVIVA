<?php
// logout.php (Na pasta raiz)

session_start();

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se for preciso destruir totalmente a sessão, apague também o cookie de sessão.
// Isso irá destruir a sessão, e não apenas os dados da sessão.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão.
session_destroy();

// Redireciona para a página de login
header('Location: ../login.html?status=logout_sucesso');
exit;
?>