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
        // Nova lógica: Próxima Vacina
        $prox_vacina = empty($_POST['id_proxima_vacina']) ? NULL : (int)$_POST['id_proxima_vacina'];
        
        if (!empty($nome)) {
            $stmt = $conn->prepare("INSERT INTO vacinamodelo (nome_vacina, recomendacao_idade, intervalo_dias, id_proxima_vacina) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $nome, $idade, $intervalo, $prox_vacina);
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
        $prox_vacina = empty($_POST['id_proxima_vacina_edit']) ? NULL : (int)$_POST['id_proxima_vacina_edit'];

        // Evitar loop infinito (Vacina A apontar para si mesma como próxima)
        if ($prox_vacina == $id) {
            $prox_vacina = NULL; 
            $mensagem_erro = "Aviso: Uma vacina não pode ser sua própria próxima dose.";
        }

        if (!empty($nome)) {
             $stmt = $conn->prepare("UPDATE vacinamodelo SET nome_vacina = ?, recomendacao_idade = ?, intervalo_dias = ?, id_proxima_vacina = ? WHERE id_vacina_modelo = ?");
             $stmt->bind_param("ssiii", $nome, $idade, $intervalo, $prox_vacina, $id);
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
        $mensagem_erro = "Erro ao excluir: " . $stmt->error;
    }
    $stmt->close();
}

// --- Lógica de LEITURA (READ) ---
$vacinas = [];
// Agora buscamos também o nome da próxima vacina (Self Join)
$sql_read = "
    SELECT 
        v1.id_vacina_modelo, 
        v1.nome_vacina, 
        v1.recomendacao_idade, 
        v1.intervalo_dias,
        v1.id_proxima_vacina,
        v2.nome_vacina AS nome_proxima_vacina
    FROM vacinamodelo v1
    LEFT JOIN vacinamodelo v2 ON v1.id_proxima_vacina = v2.id_vacina_modelo
    ORDER BY v1.nome_vacina
";
$result = $conn->query($sql_read);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vacinas[] = $row;
    }
}

// Lista auxiliar para o Dropdown (Select)
$lista_vacinas_opcoes = $vacinas; 

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Gerenciar Vacinas</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='tables.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
</head>
<body>

    <?php include 'header.php'; ?>

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

                <div class="form-box">
                    <h4>Adicionar Novo Modelo de Vacina</h4>
                    <form action="" method="POST">
                        <input type="hidden" name="acao" value="adicionar_vacina">
                        <div class="input-group">
                            <label for="nome_vacina">Nome da Vacina:</label>
                            <input type="text" id="nome_vacina" name="nome_vacina" placeholder="Ex: Vacina Penta 1ª Dose" required>
                        </div>
                        <div class="input-group">
                            <label for="recomendacao_idade">Recomendação/Idade:</label>
                            <input type="text" id="recomendacao_idade" name="recomendacao_idade" placeholder="Ex: 2 meses">
                        </div>
                        <div class="input-group">
                            <label for="intervalo_dias">Intervalo para a próxima dose (dias):</label>
                            <input type="number" id="intervalo_dias" name="intervalo_dias" placeholder="Ex: 60">
                            <p class="hint" style="font-size: 12px; color: #666;">Preencha apenas se houver uma próxima dose vinculada.</p>
                        </div>
                        
                        <div class="input-group">
                            <label for="id_proxima_vacina">Qual a próxima dose?</label>
                            <select id="id_proxima_vacina" name="id_proxima_vacina">
                                <option value="">-- Nenhuma (Dose Única ou Final) --</option>
                                <?php foreach ($lista_vacinas_opcoes as $v): ?>
                                    <option value="<?php echo $v['id_vacina_modelo']; ?>">
                                        <?php echo htmlspecialchars($v['nome_vacina']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint" style="font-size: 12px; color: #666;">Selecione a vacina que será agendada automaticamente após esta.</p>
                        </div>

                        <button type="submit" class="submit-btn">Adicionar Vacina</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <h4 style="margin-top: 40px;">Modelos de Vacina Cadastrados</h4>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome da Vacina</th>
                                <th>Recomendação</th>
                                <th>Intervalo (Dias)</th>
                                <th>Próxima Dose Vinculada</th>
                                <th id="actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vacinas as $vacina): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vacina['nome_vacina']); ?></td>
                                    <td><?php echo htmlspecialchars($vacina['recomendacao_idade'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vacina['intervalo_dias'] ?? '-'); ?></td>
                                    <td style="color: #3d8cb1; font-weight: bold;">
                                        <?php echo htmlspecialchars($vacina['nome_proxima_vacina'] ?? 'Fim do Ciclo'); ?>
                                    </td>
                                    <td class="btns-edit">
                                        <button class="btn-editar" 
                                            data-id="<?php echo $vacina['id_vacina_modelo']; ?>"
                                            data-nome="<?php echo htmlspecialchars($vacina['nome_vacina']); ?>"
                                            data-idade="<?php echo htmlspecialchars($vacina['recomendacao_idade']); ?>"
                                            data-intervalo="<?php echo htmlspecialchars($vacina['intervalo_dias']); ?>"
                                            data-proxima="<?php echo htmlspecialchars($vacina['id_proxima_vacina']); ?>"
                                        >Editar</button>
                                        <a href="?excluir=<?php echo $vacina['id_vacina_modelo']; ?>" 
                                        onclick="return confirm('ATENÇÃO: Excluir este modelo pode afetar as cadernetas existentes. Deseja continuar?')"
                                        class="btn-excluir">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <?php include 'modal_vacina.html'; ?>
    <?php include 'modal_logout.html'; ?>
    <script src="../modal.js"></script>

    <script>
        // Script para preencher o Modal de Edição
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modal-editar');
            const btnsEditar = document.querySelectorAll('.btn-editar');
            
            btnsEditar.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const idade = btn.getAttribute('data-idade');
                    const intervalo = btn.getAttribute('data-intervalo');
                    const proxima = btn.getAttribute('data-proxima'); // ID da próxima

                    document.getElementById('edit-id-vacina').value = id;
                    document.getElementById('nome_vacina_edit').value = nome;
                    document.getElementById('recomendacao_idade_edit').value = idade;
                    document.getElementById('intervalo_dias_edit').value = intervalo === 'Dose Única' ? '' : intervalo;
                    
                    // Selecionar a opção correta no dropdown do modal
                    const selectProxima = document.getElementById('id_proxima_vacina_edit');
                    selectProxima.value = proxima || ""; 

                    modal.style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>