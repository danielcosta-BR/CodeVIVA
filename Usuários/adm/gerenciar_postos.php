<?php
// Acesso necessário
$funcao_permitida = 'administrador';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

$mensagem_sucesso = '';
$mensagem_erro = '';

// --- Lógica de Ações (CREATE, UPDATE, DELETE) ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Ação: ADICIONAR (CREATE)
    if ($acao == 'adicionar_posto') {
        $nome = trim($_POST['nome_posto']);
        $endereco = trim($_POST['endereco']);
        $telefone = trim($_POST['telefone']);
        
        if (!empty($nome)) {
            $stmt = $conn->prepare("INSERT INTO postosaude (nome_posto, endereco, telefone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $endereco, $telefone);
            if ($stmt->execute()) {
                $mensagem_sucesso = "Posto de Saúde '{$nome}' adicionado com sucesso!";
            } else {
                $mensagem_erro = "Erro ao adicionar posto: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Ação: EDITAR (UPDATE)
    elseif ($acao == 'editar_posto') {
        $id = (int)$_POST['id_posto'];
        $nome = trim($_POST['nome_posto_edit']);
        $endereco = trim($_POST['endereco_edit']);
        $telefone = trim($_POST['telefone_edit']);

        if (!empty($nome)) {
             $stmt = $conn->prepare("UPDATE postosaude SET nome_posto = ?, endereco = ?, telefone = ? WHERE id_posto = ?");
             $stmt->bind_param("sssi", $nome, $endereco, $telefone, $id);
             if ($stmt->execute()) {
                $mensagem_sucesso = "Posto de Saúde atualizado com sucesso!";
             } else {
                $mensagem_erro = "Erro ao atualizar posto: " . $stmt->error;
             }
             $stmt->close();
        }
    }
}

// Ação: EXCLUIR (DELETE) via GET
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['excluir'])) {
    $id_posto = (int)$_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM postosaude WHERE id_posto = ?");
    $stmt->bind_param("i", $id_posto);
    if ($stmt->execute()) {
        $mensagem_sucesso = "Posto de Saúde excluído com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir posto: " . $stmt->error . " (Certifique-se de que não há usuários associados.)";
    }
    $stmt->close();
}

// --- Lógica de LEITURA (READ) ---
$postos = [];
$result = $conn->query("SELECT id_posto, nome_posto, endereco, telefone FROM postosaude ORDER BY nome_posto");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $postos[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Gerenciar Postos</title>
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
        <section class="form-section-G">
            <div class="form-container">
                <h2>🏥 Gerenciar Postos de Saúde</h2>
                
                <?php if ($mensagem_sucesso): ?>
                    <p class="msg-sucesso"><?php echo $mensagem_sucesso; ?></p>
                <?php endif; ?>
                <?php if ($mensagem_erro): ?>
                    <p class="msg-erro"><?php echo $mensagem_erro; ?></p>
                <?php endif; ?>

                <p>Adicione, edite ou remova unidades de saúde.</p>
                
                <!-- Formulário para Adicionar Posto (CREATE) -->
                <div class="form-box">
                    <h4>Adicionar Novo Posto</h4>
                    <form action="" method="POST">
                        <input type="hidden" name="acao" value="adicionar_posto">
                        <div class="input-group">
                            <label for="nome_posto">Nome do Posto:</label>
                            <input type="text" id="nome_posto" name="nome_posto" placeholder="Ex: UBS Central" required>
                        </div>
                        <div class="input-group">
                            <label for="endereco">Endereço:</label>
                            <input type="text" id="endereco" name="endereco" placeholder="Rua, Número, Bairro">
                        </div>
                        <div class="input-group">
                            <label for="telefone">Telefone:</label>
                            <input type="text" id="telefone" name="telefone" placeholder="(XX) XXXX-XXXX">
                        </div>
                        <button type="submit" class="submit-btn">Adicionar Posto</button>
                    </form>
                </div>
                
                <!-- Tabela de Postos (READ) -->
                <h4 style="margin-top: 40px;">Postos Cadastrados</h4>
                <?php if (empty($postos)): ?>
                    <p>Nenhum posto de saúde cadastrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Endereço</th>
                                <th>Telefone</th>
                                <th id="actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($postos as $posto): ?>
                                <tr>
                                    <td id="nome-posto"><?php echo $posto['id_posto']; ?></td>
                                    <td><?php echo htmlspecialchars($posto['nome_posto']); ?></td>
                                    <td><?php echo htmlspecialchars($posto['endereco'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($posto['telefone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="btns-edit">
                                            <button class="btn-editar" 
                                                data-id="<?php echo $posto['id_posto']; ?>"
                                                data-nome="<?php echo htmlspecialchars($posto['nome_posto']); ?>"
                                                data-endereco="<?php echo htmlspecialchars($posto['endereco']); ?>"
                                                data-telefone="<?php echo htmlspecialchars($posto['telefone']); ?>"
                                            >Editar</button>
                                            <a href="?excluir=<?php echo $posto['id_posto']; ?>" 
                                            onclick="return confirm('ATENÇÃO: Excluir este posto pode desassociar usuários. Deseja continuar?')"
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

    <!-- Modal de Edição de Posto -->
         
    <?php include 'modal_posto.html'; ?>

    <?php include 'modal_logout.html'; ?>
    <script src="../modal.js"></script>

    <!-- Script JS para manipular o Modal de Edição -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modal-editar');
            const btnsEditar = document.querySelectorAll('.btn-editar');
            
            btnsEditar.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const endereco = btn.getAttribute('data-endereco');
                    const telefone = btn.getAttribute('data-telefone');

                    document.getElementById('edit-id-posto').value = id;
                    document.getElementById('nome_posto_edit').value = nome;
                    document.getElementById('endereco_edit').value = endereco;
                    document.getElementById('telefone_edit').value = telefone;
                    
                    modal.style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>