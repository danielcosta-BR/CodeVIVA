<?php
// Usuarios/pct/salvar_configuracoes_pct.php
$funcao_permitida = 'paciente';
include '../verificar_acesso.php'; 
include '../conexao.php';         

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $id_posto_saude = $_POST['posto_saude'] ?? null;
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    
    // Validações básicas
    $id_posto_saude = filter_var($id_posto_saude, FILTER_VALIDATE_INT);
    $cpf = preg_replace('/[^0-9]/', '', $cpf); 
    $telefone = preg_replace('/[^0-9]/', '', $telefone); 
    $endereco = trim($endereco);
    
    if (empty($id_posto_saude) || empty($id_usuario)) {
        header("Location: configuracoes_pct.php?status=erro&msg=Dados obrigatórios faltando.");
        exit;
    }

    // Inicia Transação para garantir integridade
    $conn->begin_transaction();

    try {
        // 1. Atualiza tabela específica PACIENTES
        $sql = "INSERT INTO pacientes (id_usuario, id_posto_saude, cpf, telefone, endereco)
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

        // 2. Atualizar Doenças
        $doencas_selecionadas = $_POST['doencas'] ?? [];
        
        // Remove antigas
        $stmt_del = $conn->prepare("DELETE FROM paciente_doencas WHERE id_paciente = ?");
        $stmt_del->bind_param("i", $id_usuario);
        $stmt_del->execute();
        $stmt_del->close();
        
        // Insere novas
        if (!empty($doencas_selecionadas)) {
            $stmt_insert = $conn->prepare("INSERT INTO paciente_doencas (id_paciente, id_doenca) VALUES (?, ?)");
            foreach ($doencas_selecionadas as $id_doenca_str) {
                $id_doenca = (int) $id_doenca_str;
                if ($id_doenca > 0) { 
                    $stmt_insert->bind_param("ii", $id_usuario, $id_doenca);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
        }

        $conn->commit();
        header("Location: configuracoes_pct.php?status=sucesso");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: configuracoes_pct.php?status=erro&msg=Erro ao salvar");
    }

    $conn->close();

} else {
    header("Location: configuracoes_pct.php");
    exit;
}
?>