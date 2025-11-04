<?php
// logout.php (NA PASTA RAIZ)

// 1. Inicia a sessão. É necessário para acessar as variáveis de sessão.
session_start();

// 2. Destrói TODAS as variáveis de sessão.
session_unset();

// 3. Destrói a sessão em si. 
// Isso remove o cookie de sessão e limpa a sessão no servidor.
session_destroy();

// 4. Redireciona o usuário para a página de login
// Incluímos um status opcional para exibir uma mensagem de sucesso no login.html
header("Location: index.html?status=logout_sucesso");
exit;
?>