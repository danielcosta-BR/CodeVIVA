<?php
// Usuarios/adm/gerenciar_usuarios.php

$funcao_permitida = 'administrador';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

$mensagem_sucesso = '';
$mensagem_erro = '';

// --- Lógica de Exclusão de Usuário ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['excluir'])) {
    $id_usuario = (int)$_GET['excluir'];
    if ($id_usuario == $_SESSION['id_usuario']) {
        $mensagem_erro = "Erro: Você não pode excluir sua própria conta de administrador.";
    } else {
        // A exclusão em cascatas (ON DELETE CASCADE) configurada no banco cuidará das tabelas pacientes/enfermeiros/caderneta
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

// --- Lógica de Edição de Usuário (CORRIGIDA PARA TABELAS ESPECÍFICAS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar_usuario') {
    $id_usuario = (int)$_POST['id_usuario'];
    $nova_funcao = $_POST['funcao'];
    // Se vier vazio do select, definimos como NULL
    $novo_posto_id = (isset($_POST['id_posto']) && $_POST['id_posto'] !== '') ? (int)$_POST['id_posto'] : NULL;

    // 1. Atualiza a tabela MÃE (usuario) - Mantemos sync para referência
    $stmt = $conn->prepare("UPDATE usuario SET funcao = ?, id_posto = ? WHERE id_usuario = ?");
    $stmt->bind_param("sii", $nova_funcao, $novo_posto_id, $id_usuario);

    if ($stmt->execute()) {
        $mensagem_sucesso = "Usuário atualizado com sucesso!";
        $stmt->close();

        // 2. Atualiza as tabelas FILHAS (pacientes ou enfermeiros)
        // Isso garante que o paciente.php ou enfermeiro.php vejam a mudança
        
        if ($nova_funcao == 'paciente') {
            if ($novo_posto_id) {
                // Insere ou Atualiza (Upsert) na tabela pacientes
                $sql_spec = "INSERT INTO pacientes (id_usuario, id_posto_saude) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE id_posto_saude = VALUES(id_posto_saude)";
                $stmt_spec = $conn->prepare($sql_spec);
                $stmt_spec->bind_param("ii", $id_usuario, $novo_posto_id);
                $stmt_spec->execute();
                $stmt_spec->close();
            } else {
                // Se foi removido o posto, setamos NULL na tabela pacientes se existir
                $conn->query("UPDATE pacientes SET id_posto_saude = NULL WHERE id_usuario = $id_usuario");
            }
        } 
        elseif ($nova_funcao == 'enfermeiro') {
            if ($novo_posto_id) {
                // Insere ou Atualiza (Upsert) na tabela enfermeiros
                $sql_spec = "INSERT INTO enfermeiros (id_usuario, id_posto_saude) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE id_posto_saude = VALUES(id_posto_saude)";
                $stmt_spec = $conn->prepare($sql_spec);
                $stmt_spec->bind_param("ii", $id_usuario, $novo_posto_id);
                $stmt_spec->execute();
                $stmt_spec->close();
            } else {
                $conn->query("UPDATE enfermeiros SET id_posto_saude = NULL WHERE id_usuario = $id_usuario");
            }
        }

    } else {
        $mensagem_erro = "Erro ao atualizar usuário: " . $stmt->error;
        $stmt->close();
    }
}

// --- 1. Carregar Lista de Postos (para o modal) ---
$postos = [];
$result_postos = $conn->query("SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto");
if ($result_postos->num_rows > 0) {
    while($row = $result_postos->fetch_assoc()) {
        $postos[] = $row;
    }
}

// --- 2. Carregar Lista de Usuários (CORRIGIDO COM JOIN ESPECÍFICO) ---
// Esta consulta usa COALESCE para pegar o posto da tabela específica (prioridade) ou da geral
$sql = "
    SELECT 
        u.id_usuario, 
        u.nome_completo, 
        u.email, 
        u.funcao,
        -- Pega o ID do posto das tabelas filhas primeiro, se não achar, pega do usuario
        COALESCE(pac.id_posto_saude, enf.id_posto_saude, u.id_posto) as id_posto_real,
        -- Pega o Nome do posto baseado no ID encontrado acima
        p.nome_posto
    FROM usuario u
    LEFT JOIN pacientes pac ON u.id_usuario = pac.id_usuario
    LEFT JOIN enfermeiros enf ON u.id_usuario = enf.id_usuario
    LEFT JOIN postosaude p ON p.id_posto = COALESCE(pac.id_posto_saude, enf.id_posto_saude, u.id_posto)
    WHERE u.id_usuario != ? 
    ORDER BY u.funcao, u.nome_completo
";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Gerenciar Usuários</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='tables.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
</head>
<body>

    <?php include 'header.php'; ?>

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

                <p>Lista de todos os usuários cadastrados no sistema. As alterações de posto aqui refletem diretamente no painel do usuário.</p>

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
                                <th>Posto Associado (Real)</th>
                                <th id="actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td class="user-function-box" style="background-color: <?php echo ($usuario['funcao'] == 'enfermeiro') ? '#e3f2fd' : '#fff3cd'; ?>; ">
                                        <div>
                                            <!-- Tag visual para a função -->
                                            <span class="user-function" 
                                                style="
                                                    color: <?php echo ($usuario['funcao'] == 'enfermeiro') ? '#0d47a1' : '#856404'; ?>;
                                                ">
                                                <?php echo ucfirst(htmlspecialchars($usuario['funcao'])); ?>
                                            </span>

                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['nome_posto'] ?? 'Sem Posto Definido'); ?></td>
                                    <td>
                                        <div class="btns-edit">
                                            <button class="btn-editar" 
                                                data-id="<?php echo $usuario['id_usuario']; ?>"
                                                data-funcao="<?php echo $usuario['funcao']; ?>"
                                                data-posto-id="<?php echo $usuario['id_posto_real'] ?? ''; ?>"
                                                >Editar
                                            </button>
                                            
                                            <a href="?excluir=<?php echo $usuario['id_usuario']; ?>" 
                                               onclick="return confirm('Tem certeza que deseja excluir o usuário <?php echo $usuario['nome_completo']; ?>?')"
                                               class="btn-excluir">Excluir
                                            </a>

                                        </div>
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

    <!-- Modal de Edição -->
    <?php include 'modal_usuario.html'; ?>
    <?php include '../modal_logout.html'; ?>
    <script src="../modal.js"></script>

    <!-- Script JS para preencher o Modal de Edição -->
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

                    modal.style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>