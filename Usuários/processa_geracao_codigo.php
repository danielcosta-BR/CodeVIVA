<?php
// Usuarios/processa_geracao_codigo.php

// Inclui o script de verificação de acesso
// Garante que só usuários com a função 'administrador' podem acessar este script.
$funcao_permitida = 'administrador';
include 'verificar_acesso.php'; 

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS
// -----------------------------------------------------

// O caminho para o arquivo de conexão deve ser relativo à raiz do seu site se você tiver um.
// Ou você pode replicar as variáveis de conexão como feito aqui, para este script.
$host = 'localhost';
$db   = 'viva_db'; // Nome do seu banco de dados
$user = 'root';   // Seu usuário do MySQL
$pass = 'b@N¢0_|)Ad05';       // Sua senha do MySQL

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     // Em caso de erro de conexão, redireciona de volta para o admin com uma mensagem de erro.
     header('Location: administrador.php?erro=falha_db');
     exit;
}

// -----------------------------------------------------
// 2. FUNÇÃO PARA GERAR UM CÓDIGO ALFANUMÉRICO ÚNICO
// -----------------------------------------------------

function gerarCodigoUnico($pdo, $length = 8) {
    // Caracteres que podem ser usados no código
    $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = '';
    $max_tentativas = 10; // Limita as tentativas para evitar loops infinitos
    
    for ($i = 0; $i < $max_tentativas; $i++) {
        $codigo = '';
        // Gera uma string aleatória do tamanho especificado
        for ($j = 0; $j < $length; $j++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        // Verifica se este código já existe na tabela CodigoVerificacao
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM CodigoVerificacao WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetchColumn() === 0) {
            // Se o código não existe, ele é único e pode ser retornado
            return $codigo;
        }
        // Se existe, tenta novamente na próxima iteração
    }
    // Se não conseguir gerar um código único após as tentativas, retorna false
    return false;
}

// -----------------------------------------------------
// 3. PROCESSO DE GERAÇÃO E INSERÇÃO DO CÓDIGO
// -----------------------------------------------------

$novo_codigo = gerarCodigoUnico($pdo);

if ($novo_codigo) {
    try {
        // Insere o novo código na tabela CodigoVerificacao
        // Ele é definido para a função 'enfermeiro' e 'usado=0' (não usado) por padrão
        $stmt = $pdo->prepare("INSERT INTO CodigoVerificacao (codigo, funcao_alvo, usado) VALUES (?, 'enfermeiro', 0)");
        $stmt->execute([$novo_codigo]);

        // Sucesso: Redireciona de volta para a página do administrador
        // com uma mensagem de status e o código gerado na URL.
        header('Location: administrador.php?status=codigo_gerado&novo_codigo=' . urlencode($novo_codigo));
        exit;

    } catch (PDOException $e) {
        // Erro ao inserir no banco de dados
        header('Location: administrador.php?erro=falha_insercao');
        exit;
    }

} else {
    // Falha ao gerar um código único após as tentativas
    header('Location: administrador.php?erro=falha_geracao');
    exit;
}
?>