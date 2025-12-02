<?php
// Usuarios/enf/salvar_configuracoes_enf.php

$funcao_permitida = 'enfermeiro'; 
include '../verificar_acesso.php'; 
include '../conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $id_posto_saude = $_POST['posto_saude'] ?? null;
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''); 
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''); 
    $endereco = trim($_POST['endereco'] ?? '');
    
    if (empty($id_posto_saude) || empty($id_usuario)) {
        header("Location: configuracoes_enf.php?status=erro&msg=Dados obrigatórios faltando.");
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Atualiza ENFERMEIROS
        $sql = "INSERT INTO enfermeiros (id_usuario, id_posto_saude, cpf, telefone, endereco) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                id_posto_saude = VALUES(id_posto_saude),
                cpf = VALUES(cpf),
                telefone = VALUES(telefone),
                endereco = VALUES(endereco)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $id_usuario, $id_posto_saude, $cpf, $telefone, $endereco);
        $stmt->execute();
        $stmt->close();

        // ---------------------------------------------------------
        // CORREÇÃO SOLICITADA: Atualizar também a tabela USUARIO
        // ---------------------------------------------------------
        $sql_user = "UPDATE usuario SET id_posto = ? WHERE id_usuario = ?";
        $stmt_u = $conn->prepare($sql_user);
        $stmt_u->bind_param("ii", $id_posto_saude, $id_usuario);
        $stmt_u->execute();
        $stmt_u->close();
        // ---------------------------------------------------------

        $conn->commit();
        header("Location: configuracoes_enf.php?status=sucesso");

    } catch (Exception $e) {
        $conn->rollback();
        // Log do erro se necessário: error_log($e->getMessage());
        header("Location: configuracoes_enf.php?status=erro&msg=Erro ao atualizar banco.");
    }
    
    $conn->close();

} else {
    header("Location: configuracoes_enf.php");
    exit;
}
?>