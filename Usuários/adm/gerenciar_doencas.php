<?php
// Usuarios/adm/gerenciar_doencas.php
$funcao_permitida = 'administrador';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

$msg_sucesso = '';
$msg_erro = '';

// --- CRUD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'];
    
    if ($acao == 'adicionar') {
        $nome = trim($_POST['nome']);
        $alerta = trim($_POST['alerta']);
        if (!empty($nome) && !empty($alerta)) {
            $stmt = $conn->prepare("INSERT INTO doencas (nome_doenca, mensagem_alerta) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $alerta);
            if($stmt->execute()) $msg_sucesso = "Doença cadastrada!";
            else $msg_erro = "Erro ao cadastrar.";
        }
    } elseif ($acao == 'excluir') {
        $id = (int)$_POST['id_doenca'];
        if ($id == 1) { // Proteção para não apagar "Nenhuma"
            $msg_erro = "Você não pode excluir a opção padrão 'Nenhuma'.";
        } else {
            $conn->query("DELETE FROM doencas WHERE id_doenca = $id");
            $msg_sucesso = "Removido com sucesso.";
        }
    } elseif ($acao == 'editar_doenca') { // NOVO: Ação de Editar Doença
        $id = (int)$_POST['id_doenca'];
        $nome = trim($_POST['nome_doenca_edit']);
        $alerta = trim($_POST['mensagem_alerta_edit']);
        
        // ID 1 é a doença padrão ('Nenhuma'), deve ser protegida contra edição/exclusão
        if ($id == 1) { 
            $msg_erro = "Você não pode editar a opção padrão 'Nenhuma'.";
        } elseif (!empty($nome) && !empty($alerta) && $id > 0) {
            $sql = "UPDATE doencas SET nome_doenca = ?, mensagem_alerta = ? WHERE id_doenca = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nome, $alerta, $id);
            
            if ($stmt->execute()) {
                $msg_sucesso = "Doença atualizada com sucesso!";
            } else {
                $msg_erro = "Erro ao atualizar a doença: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg_erro = "Dados inválidos para edição.";
        }
    }
}

// Listar
$doencas = [];
$res = $conn->query("SELECT * FROM doencas ORDER BY id_doenca ASC");
while($row = $res->fetch_assoc()) $doencas[] = $row;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Doenças e Alertas</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
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
                <h2>🦠 Gerenciar Doenças e Alertas</h2>
                <p>Cadastre condições de saúde e as mensagens de cuidado que aparecerão para os pacientes.</p>
                
                <?php if($msg_sucesso) echo "<p style='color:green'>$msg_sucesso</p>"; ?>
                <?php if($msg_erro) echo "<p style='color:red'>$msg_erro</p>"; ?>

                <div class="form-box">
                    <h4>Nova Doença</h4>
                    <form method="POST">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="input-group">
                            <label>Nome da Condição/Doença</label>
                            <input type="text" name="nome" required placeholder="Ex: Diabetes Tipo 2">
                        </div>
                        <div class="input-group">
                            <label>Mensagem de Alerta/Cuidado (Dica do Dia)</label>
                            <textarea name="alerta" rows="3" style="width:100%" required placeholder="Ex: Lembre-se de monitorar sua glicemia e evitar doces em excesso."></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Salvar</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Doença</th><th>Mensagem de Alerta</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php foreach($doencas as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['nome_doenca']); ?></td>
                                <td><?php echo htmlspecialchars($d['mensagem_alerta']); ?></td>
                                <td id="actions">
                                    <?php if($d['id_doenca'] != 1): ?>
                                    <div class="btns-edit">
                                        <button class="btn-editar" 
                                                data-id="<?php echo $d['id_doenca']; ?>" 
                                                data-nome="<?php echo htmlspecialchars($d['nome_doenca']); ?>"
                                                data-mensagem="<?php echo htmlspecialchars($d['mensagem_alerta']); ?>">
                                            Editar
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja EXCLUIR a doença \'<?php echo addslashes($d['nome_doenca']); ?>\'?');">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id_doenca" value="<?php echo $d['id_doenca']; ?>">
                                            <button class="btn-excluir">Excluir</button>
                                        </form>
                                    </div>
                                    <?php else: echo "<i>Padrão</i>"; endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
    <?php include 'modal_logout.html'; ?>
    <?php include 'modal_doenca.html'; ?> 
    
    <script src="../modal.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modal-editar-doenca');
            const btnsEditar = document.querySelectorAll('.btn-editar');
            
            btnsEditar.forEach(btn => {
                btn.addEventListener('click', () => {
                    // 1. Pega os dados dos atributos data-
                    const id = btn.getAttribute('data-id');
                    const nome = btn.getAttribute('data-nome');
                    const mensagem = btn.getAttribute('data-mensagem');

                    // 2. Preenche os campos do modal
                    document.getElementById('edit-id-doenca').value = id;
                    document.getElementById('nome_doenca_edit').value = nome;
                    document.getElementById('mensagem_alerta_edit').value = mensagem;

                    // 3. Exibe o modal (usa display: flex para centralização CSS)
                    modal.style.display = 'flex';
                });
            });

            // Opcional: Fechar o modal ao clicar fora
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        });
    </script>
</body>
</html>