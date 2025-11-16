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
    if ($acao == 'adicionar_vacina') {
        $nome = trim($_POST['nome_vacina']);
        $idade = trim($_POST['recomendacao_idade']);
        $intervalo = empty($_POST['intervalo_dias']) ? NULL : (int)$_POST['intervalo_dias'];
        
        if (!empty($nome)) {
            $stmt = $conn->prepare("INSERT INTO vacinamodelo (nome_vacina, recomendacao_idade, intervalo_dias) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nome, $idade, $intervalo);
            if ($stmt->execute()) {
                $mensagem_sucesso = "Vacina '{$nome}' adicionada com sucesso!";
            } else {
                $mensagem_erro = "Erro ao adicionar vacina: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Ação: EDITAR (UPDATE)
    elseif ($acao == 'editar_vacina') {
        $id = (int)$_POST['id_vacina_modelo'];
        $nome = trim($_POST['nome_vacina_edit']);
        $idade = trim($_POST['recomendacao_idade_edit']);
        $intervalo = empty($_POST['intervalo_dias_edit']) ? NULL : (int)$_POST['intervalo_dias_edit'];

        if (!empty($nome)) {
             $stmt = $conn->prepare("UPDATE vacinamodelo SET nome_vacina = ?, recomendacao_idade = ?, intervalo_dias = ? WHERE id_vacina_modelo = ?");
             $stmt->bind_param("ssii", $nome, $idade, $intervalo, $id);
             if ($stmt->execute()) {
                $mensagem_sucesso = "Vacina atualizada com sucesso!";
             } else {
                $mensagem_erro = "Erro ao atualizar vacina: " . $stmt->error;
             }
             $stmt->close();
        }
    }
}

// Ação: EXCLUIR (DELETE) via GET
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['excluir'])) {
    $id_vacina_modelo = (int)$_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM vacinamodelo WHERE id_vacina_modelo = ?");
    $stmt->bind_param("i", $id_vacina_modelo);
    if ($stmt->execute()) {
        $mensagem_sucesso = "Modelo de Vacina excluído com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir modelo de vacina: " . $stmt->error . " (Pode haver cadernetas dependentes.)";
    }
    $stmt->close();
}

// --- Lógica de LEITURA (READ) ---
$vacinas = [];
$result = $conn->query("SELECT id_vacina_modelo, nome_vacina, recomendacao_idade, intervalo_dias FROM vacinamodelo ORDER BY nome_vacina");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vacinas[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Gerenciar Vacinas</title>
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
        <section class="form-section-G">
            <div class="form-container">
                <h2>💉 Gerenciar Vacinas</h2>
                
                <?php if ($mensagem_sucesso): ?>
                    <p class="msg-sucesso"><?php echo $mensagem_sucesso; ?></p>
                <?php endif; ?>
                <?php if ($mensagem_erro): ?>
                    <p class="msg-erro"><?php echo $mensagem_erro; ?></p>
                <?php endif; ?>

                <p>Gerencie o catálogo de modelos de vacinas disponíveis no sistema.</p>

                <!-- Formulário para Adicionar Vacina (CREATE) -->
                <div class="form-box">
                    <h4>Adicionar Novo Modelo de Vacina</h4>
                    <form action="" method="POST">
                        <input type="hidden" name="acao" value="adicionar_vacina">
                        <div class="input-group">
                            <label for="nome_vacina">Nome da Vacina:</label>
                            <input type="text" id="nome_vacina" name="nome_vacina" placeholder="Ex: Tríplice Viral" required>
                        </div>
                        <div class="input-group">
                            <label for="recomendacao_idade">Recomendação/Idade:</label>
                            <input type="text" id="recomendacao_idade" name="recomendacao_idade" placeholder="Ex: 1ª Dose: 12 meses">
                        </div>
                        <div class="input-group">
                            <label for="intervalo_dias">Intervalo entre doses (dias):</label>
                            <input type="number" id="intervalo_dias" name="intervalo_dias" placeholder="Ex: 180 (Deixe em branco para dose única)">
                        </div>
                        <button type="submit" class="submit-btn">Adicionar Vacina</button>
                    </form>
                </div>
                <!-- Tabela de Vacinas (READ) -->
                <div class="table-responsive">
                    <h4 style="margin-top: 40px;">Modelos de Vacina Cadastrados</h4>
                    <?php if (empty($vacinas)): ?>
                        <p>Nenhum modelo de vacina cadastrado.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome da Vacina</th>
                                    <th>Recomendação</th>
                                    <th>Intervalo (Dias)</th>
                                    <th id="actions">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vacinas as $vacina): ?>
                                    <tr>
                                        <td><?php echo $vacina['id_vacina_modelo']; ?></td>
                                        <td><?php echo htmlspecialchars($vacina['nome_vacina']); ?></td>
                                        <td><?php echo htmlspecialchars($vacina['recomendacao_idade'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($vacina['intervalo_dias'] ?? 'Dose Única'); ?></td>
                                        <td class="btns-edit">
                                            <button class="btn-editar" 
                                                data-id="<?php echo $vacina['id_vacina_modelo']; ?>"
                                                data-nome="<?php echo htmlspecialchars($vacina['nome_vacina']); ?>"
                                                data-idade="<?php echo htmlspecialchars($vacina['recomendacao_idade']); ?>"
                                                data-intervalo="<?php echo htmlspecialchars($vacina['intervalo_dias']); ?>"
                                            >Editar</button>
                                            <a href="?excluir=<?php echo $vacina['id_vacina_modelo']; ?>" 
                                            onclick="return confirm('ATENÇÃO: Excluir este modelo pode afetar as cadernetas existentes. Deseja continuar?')"
                                            class="btn-excluir">Excluir</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                
            </div>
        </section>
    </main>

    <!-- Modal de Edição de Vacina -->
    
    <?php include 'modal_vacina.html'; ?>
    
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
                    const idade = btn.getAttribute('data-idade');
                    const intervalo = btn.getAttribute('data-intervalo');

                    document.getElementById('edit-id-vacina').value = id;
                    document.getElementById('nome_vacina_edit').value = nome;
                    document.getElementById('recomendacao_idade_edit').value = idade;
                    document.getElementById('intervalo_dias_edit').value = intervalo === 'Dose Única' ? '' : intervalo;
                    
                    modal.style.display = 'flex';
                });
            });
        });
    </script>
</body>
</html>