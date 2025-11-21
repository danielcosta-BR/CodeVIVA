<?php
// Usuarios/enfermeiro.php

// 1. Definições de Segurança
$funcao_permitida = 'enfermeiro';
include 'verificar_acesso.php';
include 'conexao.php';

$id_usuario = $_SESSION['id_usuario'];
$nome_completo = $_SESSION['nome_completo'];

// 2. Verificar Configuração do Enfermeiro (Se já possui posto definido)
$config_completa = false;
$id_posto_enfermeiro = null;
$nome_posto_enfermeiro = "Não definido";

// Busca na tabela enfermeiros usando o id_usuario
$sql_check = "SELECT e.id_posto_saude, p.nome_posto 
              FROM enfermeiros e 
              LEFT JOIN postosaude p ON e.id_posto_saude = p.id_posto 
              WHERE e.id_usuario = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $dados_enf = $result_check->fetch_assoc();
    if (!empty($dados_enf['id_posto_saude'])) {
        $config_completa = true;
        $id_posto_enfermeiro = $dados_enf['id_posto_saude'];
        $nome_posto_enfermeiro = $dados_enf['nome_posto'];
    }
}
$stmt_check->close();

// 3. Lógica do Dashboard (Apenas se configurado)
$lista_pacientes = [];
if ($config_completa) {
    // Busca pacientes vinculados ao MESMO posto de saúde do enfermeiro
    // Trazemos também o ID do usuário para buscar a caderneta depois
    $sql_pacientes = "
        SELECT p.id_usuario, u.nome_completo, p.cpf, p.telefone 
        FROM pacientes p
        INNER JOIN usuario u ON p.id_usuario = u.id_usuario
        WHERE p.id_posto_saude = ?
        ORDER BY u.nome_completo ASC
    ";
    $stmt_pct = $conn->prepare($sql_pacientes);
    $stmt_pct->bind_param("i", $id_posto_enfermeiro);
    $stmt_pct->execute();
    $result_pct = $stmt_pct->get_result();
    
    while($row = $result_pct->fetch_assoc()){
        $lista_pacientes[] = $row;
    }
    $stmt_pct->close();
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Área do Enfermeiro</title>
    <link rel='stylesheet' type='text/css' href='administrador.css'>
    <link rel='stylesheet' type='text/css' href='styleprofile.css'>
    <link rel='stylesheet' type='text/css' href='modal.css'>
    <link rel='stylesheet' type='text/css' href='adm/tables.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
    <style>
        /* Ajustes específicos para o modal de vacinas */
        .modal-vacinas {
            max-width: 800px;
            width: 90%;
        }
        .action-btn {
            background-color: #3db19e; 
            padding: 5px 10px; 
            color: white; 
            border-radius: 4px; 
            font-size: 0.9em;
            border:none;
            cursor: pointer;
        }
        .action-btn:hover { background-color: #2f8c7d; }
        .status-check { color: green; font-weight: bold; }
        .loading { text-align: center; padding: 20px; color: #666; }
    </style>
</head>
<body>

    <?php include 'header_enf.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>Olá, Enfermeiro(a) <?php echo htmlspecialchars($nome_completo); ?>!</h2>

                <!-- CENÁRIO 1: NÃO CONFIGURADO -->
                <?php if (!$config_completa): ?>
                    <div class="alerta-config" style="text-align: center; padding: 40px;">
                        <i class="fas fa-user-nurse" style="font-size: 50px; color: #3d8cb1; margin-bottom: 20px;"></i>
                        <h3>Configuração Necessária</h3>
                        <p style="text-align: center; max-width: 600px; margin: 10px auto;">
                            Para começar a gerenciar os pacientes e vacinas, precisamos saber em qual 
                            <strong>Posto de Saúde</strong> você atua e confirmar seus dados profissionais.
                        </p>
                        <div id="button_center" style="margin-top: 30px;">
                            <a href="enf/configuracoes_enf.php" class="submit-btn" style="text-decoration: none; padding: 15px 30px;">
                                <i class="fas fa-cog"></i> Realizar Configuração Agora
                            </a>
                        </div>
                    </div>
                
                <!-- CENÁRIO 2: DASHBOARD COMPLETO -->
                <?php else: ?>
                    <div class="header-paciente">
                        <p class="posto-info"><i class="fas fa-hospital-alt"></i> Atuando em: <strong><?php echo htmlspecialchars($nome_posto_enfermeiro); ?></strong></p>
                    </div>

                    <!-- SEÇÃO DE NOTIFICAÇÕES (MOCKUP VISUAL) -->
                    <div class="card-lembretes" style="margin-bottom: 30px; border-left: 5px solid #ff9800; background-color: #fff3e0; padding: 15px;">
                        <h4><i class="fas fa-bell"></i> Solicitações Pendentes</h4>
                        <!-- Como não temos tabela de solicitações ainda, deixamos fixo ou vazio -->
                        <p><em>Nenhuma solicitação de visita domiciliar ou ajuda pendente no momento.</em></p>
                    </div>

                    <!-- LISTA DE PACIENTES -->
                    <h3><i class="fas fa-users"></i> Pacientes do Posto</h3>
                    <p>Abaixo estão listados os pacientes cadastrados na sua unidade. Clique em "Gerenciar" para ver e atualizar a caderneta.</p>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome do Paciente</th>
                                    <th>CPF</th>
                                    <th>Telefone</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lista_pacientes) > 0): ?>
                                    <?php foreach ($lista_pacientes as $paciente): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($paciente['nome_completo']); ?></td>
                                            <td><?php echo htmlspecialchars($paciente['cpf']); ?></td>
                                            <td><?php echo htmlspecialchars($paciente['telefone']); ?></td>
                                            <td>
                                                <button class="action-btn" onclick="abrirCaderneta(<?php echo $paciente['id_usuario']; ?>, '<?php echo htmlspecialchars($paciente['nome_completo']); ?>')">
                                                    <i class="fas fa-syringe"></i> Gerenciar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center">Nenhum paciente vinculado a este posto ainda.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </main>

    <!-- MODAL PARA GERENCIAR VACINAS -->
    <div id="modal-vacinas" class="modal-logout">
        <div class="modal-content-logout modal-vacinas">
            <h3 id="modal-paciente-nome">Vacinação de [Paciente]</h3>
            <div id="modal-body-content" style="max-height: 60vh; overflow-y: auto; margin: 20px 0;">
                <!-- O conteúdo será carregado via AJAX aqui -->
                <p class="loading">Carregando dados...</p>
            </div>
            <button id="fechar-vacinas" style="background-color: #666; color: white;">Fechar</button>
        </div>
    </div>

    <?php include 'modal_logout.html'; ?>
    <script src="modal.js"></script>
    
    <script>
        // Script Específico do Dashboard do Enfermeiro
        const modalVacinas = document.getElementById('modal-vacinas');
        const btnFecharVacinas = document.getElementById('fechar-vacinas');
        const modalBody = document.getElementById('modal-body-content');
        const modalTitulo = document.getElementById('modal-paciente-nome');

        // Função para abrir o modal e buscar dados via AJAX
        function abrirCaderneta(idPaciente, nomePaciente) {
            modalVacinas.style.display = "block";
            modalTitulo.innerText = "Vacinação de: " + nomePaciente;
            modalBody.innerHTML = '<p class="loading"><i class="fas fa-spinner fa-spin"></i> Buscando caderneta...</p>';

            // Faz a requisição para buscar_vacinas_paciente.php
            fetch(`enf/buscar_vacinas_paciente.php?id_paciente=${idPaciente}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(err => {
                    modalBody.innerHTML = '<p style="color:red">Erro ao carregar dados.</p>';
                    console.error(err);
                });
        }

        // Função chamada pelo botão dentro do modal (gerado pelo PHP) para aplicar vacina
        function aplicarVacina(idPaciente, idModeloVacina, btnElement) {
            if(!confirm("Confirmar a aplicação desta vacina? Esta ação atualizará a caderneta do paciente.")) return;

            const formData = new FormData();
            formData.append('id_paciente', idPaciente);
            formData.append('id_vacina_modelo', idModeloVacina);

            fetch('enf/registrar_vacina.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Atualiza a UI visualmente sem recarregar
                    const row = btnElement.closest('tr');
                    const statusCell = row.querySelector('.status-cell');
                    statusCell.innerHTML = '<span class="status-check"><i class="fas fa-check-circle"></i> Aplicada Hoje</span>';
                    btnElement.disabled = true;
                    btnElement.style.backgroundColor = '#ccc';
                    btnElement.innerText = 'Aplicada';
                } else {
                    alert("Erro: " + (data.msg || "Erro desconhecido"));
                }
            })
            .catch(err => alert("Erro de conexão ao registrar vacina."));
        }

        btnFecharVacinas.onclick = function() {
            modalVacinas.style.display = "none";
        }
        
        // Fechar ao clicar fora
        window.onclick = function(event) {
            if (event.target == modalVacinas) {
                modalVacinas.style.display = "none";
            }
            // Mantém o funcionamento do modal de logout do script global
            const modalLogout = document.getElementById('logout-modal');
             if (event.target == modalLogout) {
                modalLogout.style.display = "none";
            }
        }
    </script>
</body>
</html>