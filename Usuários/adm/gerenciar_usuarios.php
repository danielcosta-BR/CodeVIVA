<?php
// Acesso necessário
$funcao_permitida = 'administrador';
include '../verificar_acesso.php'; 
include '../conexao.php'; // Inclui a conexão com o BD

$mensagem_sucesso = '';
$mensagem_erro = '';

// --- Lógica de Exclusão de Usuário ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['excluir'])) {
    $id_usuario = (int)$_GET['excluir'];
    // Impedir que o administrador exclua a si mesmo (id_usuario == $_SESSION['id_usuario'])
    if ($id_usuario == $_SESSION['id_usuario']) {
        $mensagem_erro = "Erro: Você não pode excluir sua própria conta de administrador.";
    } else {
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        if ($stmt->execute()) {
            $mensagem_sucesso = "Usuário excluído com sucesso!";
        } else {
            $mensagem_erro = "Erro ao excluir usuário: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Lógica de Edição de Usuário ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar_usuario') {
    $id_usuario = (int)$_POST['id_usuario'];
    $nova_funcao = $_POST['funcao'];
    $novo_posto = $_POST['id_posto'] == '' ? NULL : (int)$_POST['id_posto']; // Trata NULL

    $stmt = $conn->prepare("UPDATE usuario SET funcao = ?, id_posto = ? WHERE id_usuario = ?");
    $stmt->bind_param("sii", $nova_funcao, $novo_posto, $id_usuario);

    if ($stmt->execute()) {
        $mensagem_sucesso = "Usuário atualizado com sucesso!";
    } else {
        $mensagem_erro = "Erro ao atualizar usuário: " . $stmt->error;
    }
    $stmt->close();
}


// --- 1. Carregar Postos de Saúde (para o formulário de edição) ---
$postos = [];
$result_postos = $conn->query("SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto");
if ($result_postos->num_rows > 0) {
    while($row = $result_postos->fetch_assoc()) {
        $postos[] = $row;
    }
}

// --- 2. Carregar Lista de Usuários ---
// Excluir o próprio administrador logado e buscar o nome do posto
$sql = "SELECT u.id_usuario, u.nome_completo, u.email, u.funcao, p.nome_posto
        FROM usuario u
        LEFT JOIN postosaude p ON u.id_posto = p.id_posto
        WHERE u.id_usuario != ? 
        ORDER BY u.funcao, u.nome_completo";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$result_usuarios = $stmt->get_result();
$usuarios = [];
if ($result_usuarios->num_rows > 0) {
    while($row = $result_usuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Gerenciar Usuários</title>
    <!-- Caminhos CSS ajustados para estar em Usuarios/adm/ -->
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='tables.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
</head>
<body>

    <?php 
        include 'header.php'; 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>🧑‍💻 Gerenciar Usuários</h2>

                <?php if ($mensagem_sucesso): ?>
                    <p class="msg-sucesso"><?php echo $mensagem_sucesso; ?></p>
                <?php endif; ?>
                <?php if ($mensagem_erro): ?>
                    <p class="msg-erro"><?php echo $mensagem_erro; ?></p>
                <?php endif; ?>

                <p>Lista de todos os usuários cadastrados no sistema (Pacientes e Enfermeiros).</p>

                <?php if (empty($usuarios)): ?>
                    <p>Nenhum usuário encontrado (além do administrador logado).</p>
                <?php else: ?>
                    
                    <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Função</th>
                                <th>Posto Associado</th>
                                <th id="actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($usuario['funcao'])); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nome_posto'] ?? 'N/A'); ?></td>
                                    <td class="btns-edit">
                                        <button class="btn-editar" 
                                            data-id="<?php echo $usuario['id_usuario']; ?>"
                                            data-funcao="<?php echo $usuario['funcao']; ?>"
                                            data-posto="<?php echo $usuario['nome_posto'] ?? ''; ?>"
                                            data-posto-id="<?php echo $usuario['id_posto'] ?? ''; ?>"
                                        >Editar</button>
                                        <!-- O link de exclusão é feito via GET para simplificar -->
                                        <a href="?excluir=<?php echo $usuario['id_usuario']; ?>" 
                                           onclick="return confirm('Tem certeza que deseja excluir o usuário <?php echo $usuario['nome_completo']; ?>?')"
                                           class="btn-excluir">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
                
            </div>
        </section>
    </main>

    <!-- Modal de Edição (Escondido por padrão) -->
    
    <?php include 'modal_usuario.html'; ?>
    
    <?php include '../modal_logout.html'; ?>
    <script src="../modal.js"></script>

    <!-- Script JS para manipular o Modal de Edição -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modal-editar');
            const btnsEditar = document.querySelectorAll('.btn-editar');
            
            btnsEditar.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const funcao = btn.getAttribute('data-funcao');
                    const postoId = btn.getAttribute('data-posto-id');

                    document.getElementById('edit-id-usuario').value = id;
                    document.getElementById('edit-funcao').value = funcao;
                    
                    // Seleciona o posto de saúde correto no dropdown
                    const postoSelect = document.getElementById('edit-id-posto');
                    if (postoId) {
                         postoSelect.value = postoId;
                    } else {
                         postoSelect.value = ''; // N/A
                    }

                    modal.style.display = 'flex';
                });
            });
        });
    </script>
</body>
</html>