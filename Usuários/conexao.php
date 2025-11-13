<?php
// Configurações do Banco de Dados
define('DB_SERVER', 'localhost'); // Geralmente 'localhost'
define('DB_USERNAME', 'root');   // Seu usuário do MySQL
define('DB_PASSWORD', 'b@N¢0_|)Ad05');       // Sua senha do MySQL
define('DB_NAME', 'viva_db');    // O nome do seu banco de dados

// Tenta conectar ao banco de dados MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Checa a conexão
if($conn->connect_error){
    die("ERRO DE CONEXÃO: Não foi possível conectar ao banco de dados: " . $conn->connect_error);
}

// Configura o charset para UTF-8 para evitar problemas de acentuação
$conn->set_charset("utf8mb4");

// A variável $conn será usada nas páginas de administração
?>