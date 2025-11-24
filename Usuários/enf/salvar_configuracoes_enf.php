<?php
// Usuarios/pct/salvar_configuracoes_pct.php
$funcao_permitida = 'paciente';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_usuario = $_SESSION['id_usuario'];
    
    // Dados Pessoais
    $id_posto = (int)$_POST['posto_saude'];
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    
    // Doenças (Array de IDs)
    $doencas = $_POST['doencas'] ?? [];

    if (!$id_posto) {
        header("Location: configuracoes_pct.php?status=erro&msg=Posto obrigatório");
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Atualiza Tabela Pacientes
        // Usa ON DUPLICATE KEY UPDATE para garantir (embora id_usuario deva ser único)
        $sql = "INSERT INTO pacientes (id_usuario, id_posto_saude, cpf, telefone, endereco) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                id_posto_saude = VALUES(id_posto_saude),
                cpf = VALUES(cpf),
                telefone = VALUES(telefone),
                endereco = VALUES(endereco)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $id_usuario, $id_posto, $cpf, $telefone, $endereco);
        $stmt->execute();
        $stmt->close();

        // 2. Atualiza Doenças (Delete All + Insert New)
        // Primeiro limpamos as doenças antigas deste paciente
        $stmt_del = $conn->prepare("DELETE FROM paciente_doencas WHERE id_paciente = ?");
        $stmt_del->bind_param("i", $id_usuario);
        $stmt_del->execute();
        $stmt_del->close();

        // Agora inserimos as novas selecionadas
        if (!empty($doencas)) {
            $stmt_ins = $conn->prepare("INSERT INTO paciente_doencas (id_paciente, id_doenca) VALUES (?, ?)");
            foreach ($doencas as $id_doenca) {
                $stmt_ins->bind_param("ii", $id_usuario, $id_doenca);
                $stmt_ins->execute();
            }
            $stmt_ins->close();
        }

        $conn->commit();
        header("Location: configuracoes_pct.php?status=sucesso");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: configuracoes_pct.php?status=erro");
    }
    
    $conn->close();
}
?>