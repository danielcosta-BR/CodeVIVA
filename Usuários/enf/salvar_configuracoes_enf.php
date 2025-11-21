<?php
// Usuarios/enf/salvar_configuracoes_enf.php
$funcao_permitida = 'enfermeiro';
include '../verificar_acesso.php';
include '../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_usuario = $_SESSION['id_usuario'];
    $id_posto = filter_var($_POST['posto_saude'], FILTER_VALIDATE_INT);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);

    if (!$id_posto) {
        header("Location: configuracoes_enf.php?status=erro&msg=Selecione um posto válido.");
        exit;
    }

    // Query com ON DUPLICATE KEY UPDATE para criar ou atualizar
    // A tabela enfermeiros deve ter id_usuario como UNIQUE ou Primary Key para isso funcionar bem, 
    // ou usamos lógica de Check-Insert-Update. O DDL diz UNIQUE KEY `id_usuario` em enfermeiros, então OK.

    $sql = "INSERT INTO enfermeiros (id_usuario, id_posto_saude, cpf, telefone, endereco) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            id_posto_saude = VALUES(id_posto_saude),
            cpf = VALUES(cpf),
            telefone = VALUES(telefone),
            endereco = VALUES(endereco)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $id_usuario, $id_posto, $cpf, $telefone, $endereco);

    if ($stmt->execute()) {
        // Atualiza a sessão se necessário (opcional)
        header("Location: ../enfermeiro.php?status=sucesso");
    } else {
        header("Location: configuracoes_enf.php?status=erro&msg=Erro ao salvar no banco.");
    }
    $stmt->close();
    $conn->close();
}
?>