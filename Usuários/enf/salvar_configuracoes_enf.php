<?php
// Usuarios/enf/salvar_configuracoes_enf.php

// 1. Define a permissão correta (ISSO RESOLVE O ERRO DE ACESSO NEGADO)
$funcao_permitida = 'enfermeiro'; 

include '../verificar_acesso.php'; 
include '../conexao.php'; 

// Verifica se o formulário foi enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pega o ID do usuário da sessão
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    
    // Recebe os dados do formulário (configuracoes_enf.php)
    // O name no formulário é 'posto_saude', mas no banco é 'id_posto_saude'
    $id_posto_saude = $_POST['posto_saude'] ?? null;
    
    // Limpeza dos dados (Sanitização)
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''); // Remove pontos e traços
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''); // Remove parênteses e traços
    $endereco = trim($_POST['endereco'] ?? '');
    
    // Validação básica
    if (empty($id_posto_saude) || empty($id_usuario)) {
        header("Location: configuracoes_enf.php?status=erro&msg=Dados obrigatórios faltando.");
        exit;
    }

    // Prepara a query SQL
    // Utilizamos INSERT ... ON DUPLICATE KEY UPDATE
    // Isso cria o registro na tabela 'enfermeiros' se não existir, ou atualiza se já existir.
    $sql = "
        INSERT INTO enfermeiros (id_usuario, id_posto_saude, cpf, telefone, endereco) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            id_posto_saude = VALUES(id_posto_saude),
            cpf = VALUES(cpf),
            telefone = VALUES(telefone),
            endereco = VALUES(endereco)
    ";
    
    if ($stmt = $conn->prepare($sql)) {
        // Tipos: i = integer, s = string
        // Ordem: id_usuario (i), id_posto_saude (i), cpf (s), telefone (s), endereco (s)
        $stmt->bind_param("iisss", $id_usuario, $id_posto_saude, $cpf, $telefone, $endereco);
        
        if ($stmt->execute()) {
            // Sucesso: Redireciona com mensagem verde
            header("Location: configuracoes_enf.php?status=sucesso");
        } else {
            // Erro de SQL: Redireciona com mensagem vermelha
            header("Location: configuracoes_enf.php?status=erro&msg=Erro ao atualizar banco de dados.");
        }
        $stmt->close();
    } else {
        // Erro na preparação da query
        header("Location: configuracoes_enf.php?status=erro&msg=Erro interno do sistema.");
    }
    
    $conn->close();

} else {
    // Se tentar acessar o arquivo diretamente sem ser via POST
    header("Location: configuracoes_enf.php");
    exit;
}
?>