<?php
// Usuarios/adm/configuracoes.php

// Define a função permitida
$funcao_permitida = 'administrador';
include '../verificar_acesso.php'; 
include '../conexao.php'; // Incluindo conexão para buscar os dados

$id_usuario = $_SESSION['id_usuario'];

// 1. Busca os dados atuais do Administrador (da tabela usuario)
$id_posto_atual = null;
$sql_admin = "SELECT id_posto FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_admin);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res_admin = $stmt->get_result();
if ($row = $res_admin->fetch_assoc()) {
    $id_posto_atual = $row['id_posto'];
}
$stmt->close();

// 2. Busca lista de Postos de Saúde para o Select
$postos_opcoes = [];
$sql_postos = "SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto ASC";
$res_postos = $conn->query($sql_postos);
while($row_p = $res_postos->fetch_assoc()) {
    $postos_opcoes[] = $row_p;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Configurações Admin</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
</head>
<body>

    <?php include 'header.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>🛠️ Configurações (Administrador)</h2>
                
                <p>Vincule seu usuário administrativo a um Posto de Saúde.</p>

                <form action="processa_configuracoes.php" method="POST">
                    
                    <h4>LOCAL DE ATENDIMENTO:</h4>
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde Vinculado</label>
                        <select id="posto_saude" name="posto_saude" required>
                            <option value="" disabled <?php echo is_null($id_posto_atual) ? 'selected' : ''; ?>>Selecione seu posto</option>
                            <?php foreach ($postos_opcoes as $posto): ?>
                                <option value="<?php echo $posto['id_posto']; ?>" 
                                    <?php echo ($id_posto_atual == $posto['id_posto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($posto['nome_posto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="submit-btn">Salvar Alterações</button>
                </form>
                
            </div>
        </section>
    </main>
    
    <?php include '../modal_logout.html'; ?>
    <script src="../modal.js"></script>
</body>
</html>