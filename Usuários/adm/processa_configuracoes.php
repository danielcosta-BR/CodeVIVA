<?php
// Usuarios/adm/processa_configuracoes.php

// 1. Definição de acesso restrito ao Administrador
$funcao_permitida = 'administrador';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

// 2. Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Pega o ID do usuário da sessão atual
    $id_usuario = $_SESSION['id_usuario'];
    
    // Recebe o dado do formulário
    $id_posto = $_POST['posto_saude'] ?? null;

    // Validação básica
    if (empty($id_posto)) {
        header("Location: configuracoes.php?status=erro&msg=Selecione um posto válido.");
        exit;
    }

    // 3. Atualiza o Posto na tabela USUARIO
    // Como o admin não tem tabela própria, atualizamos direto na tabela central 'usuario'
    $sql = "UPDATE usuario SET id_posto = ? WHERE id_usuario = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $id_posto, $id_usuario);
        
        if ($stmt->execute()) {
            // Sucesso: Atualiza também a sessão se necessário e redireciona
            $_SESSION['id_posto'] = $id_posto; // Mantém a sessão atualizada
            header("Location: configuracoes.php?status=sucesso");
        } else {
            // Erro de execução SQL
            header("Location: configuracoes.php?status=erro&msg=Erro ao atualizar banco.");
        }
        $stmt->close();
    } else {
        // Erro na preparação da query
        header("Location: configuracoes.php?status=erro&msg=Erro interno.");
    }

    $conn->close();

} else {
    // Se tentar acessar o arquivo diretamente sem enviar o formulário
    header("Location: configuracoes.php");
    exit;
}
?>