<?php
// get_dados_cadastro.php

// 1. Desliga qualquer exibição de erro que possa sujar o JSON
// Isso evita que avisos (Warnings) do PHP apareçam e quebrem o Javascript
error_reporting(0);
ini_set('display_errors', 0);

// 2. Define o cabeçalho como JSON e charset UTF-8
header('Content-Type: application/json; charset=utf-8');

$dados = [
    'postos' => [],
    'doencas' => [],
    'debug_msg' => ''
];

// 3. CONEXÃO DIRETA COM O BANCO
// Usamos conexão direta aqui para evitar problemas de caminho (include path)
// já que este arquivo fica na raiz e o conexao.php fica em Usuarios/

$host = 'localhost';
$db   = 'viva_db';
$user = 'root';
$pass = 'b@N¢0_|)Ad05'; // Senha informada anteriormente

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Se falhar, retorna um JSON de erro
    echo json_encode(['erro' => 'Falha na conexão com o banco: ' . $conn->connect_error]);
    exit;
}

// Garante que os acentos venham corretos do banco
$conn->set_charset("utf8mb4");

// 4. Buscar Postos de Saúde
$sql_postos = "SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto ASC";
$result_postos = $conn->query($sql_postos);

if ($result_postos) {
    while($row = $result_postos->fetch_assoc()) {
        $dados['postos'][] = $row;
    }
} else {
    $dados['debug_msg'] .= "Erro ao buscar postos: " . $conn->error . "; ";
}

// 5. Buscar Doenças
// Verifica primeiro se a tabela existe para evitar erros fatais se o SQL de atualização não tiver sido rodado
$check_table = $conn->query("SHOW TABLES LIKE 'doencas'");

if($check_table && $check_table->num_rows > 0) {
    $sql_doencas = "SELECT id_doenca, nome_doenca FROM doencas ORDER BY id_doenca ASC";
    $result_doencas = $conn->query($sql_doencas);

    if ($result_doencas) {
        while($row = $result_doencas->fetch_assoc()) {
            $dados['doencas'][] = $row;
        }
    }
} else {
    // Se a tabela não existir, apenas avisa no debug, mas retorna a lista vazia sem quebrar
    $dados['debug_msg'] .= "Tabela doencas não encontrada (Execute o script SQL de atualização); ";
}

$conn->close();

// 6. Retorna o JSON final para o Javascript
echo json_encode($dados);
?>