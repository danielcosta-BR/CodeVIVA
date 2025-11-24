<?php
// Usuarios/enfermeiro.php
$funcao_permitida = 'enfermeiro';
include 'verificar_acesso.php';
include 'conexao.php';

$id_usuario = $_SESSION['id_usuario'];
$nome_completo = $_SESSION['nome_completo'];

// Verifica Configuração e Posto
$config_completa = false;
$id_posto_enfermeiro = null;
$nome_posto_enfermeiro = "Não definido";

$sql_check = "SELECT e.id_posto_saude, p.nome_posto FROM enfermeiros e LEFT JOIN postosaude p ON e.id_posto_saude = p.id_posto WHERE e.id_usuario = ?";
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

// Dados para o Dashboard
$lista_pacientes = [];
$solicitacoes = [];

if ($config_completa) {
    // 1. Lista Pacientes
    $sql_pct = "SELECT p.id_usuario, u.nome_completo, p.cpf, p.telefone FROM pacientes p INNER JOIN usuario u ON p.id_usuario = u.id_usuario WHERE p.id_posto_saude = ? ORDER BY u.nome_completo ASC";
    $stmt_pct = $conn->prepare($sql_pct);
    $stmt_pct->bind_param("i", $id_posto_enfermeiro);
    $stmt_pct->execute();
    $res_pct = $stmt_pct->get_result();
    while($row = $res_pct->fetch_assoc()){ $lista_pacientes[] = $row; }
    $stmt_pct->close();

    // 2. Lista Solicitações (Mensagens enviadas para o Posto)
    // Trazemos também o nome de quem atendeu (Self join em usuario u2)
    $sql_msg = "
        SELECT 
            m.id_mensagem, m.assunto, m.corpo, m.data_envio, m.status, m.data_atendimento,
            u_rem.nome_completo as nome_paciente,
            u_atend.nome_completo as nome_atendente
        FROM mensagens m
        JOIN usuario u_rem ON m.id_remetente = u_rem.id_usuario
        LEFT JOIN usuario u_atend ON m.id_atendente = u_atend.id_usuario
        WHERE m.id_posto_alvo = ?
        ORDER BY m.status ASC, m.data_envio DESC
    "; // Ordena: Pendentes primeiro, depois as mais recentes
    
    $stmt_msg = $conn->prepare($sql_msg);
    $stmt_msg->bind_param("i", $id_posto_enfermeiro);
    $stmt_msg->execute();
    $res_msg = $stmt_msg->get_result();
    while($row = $res_msg->fetch_assoc()){ $solicitacoes[] = $row; }
    $stmt_msg->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Enfermeiro</title>
    <link rel='stylesheet' type='text/css' href='administrador.css'>
    <link rel='stylesheet' type='text/css' href='../styleenf.css'>
    <link rel='stylesheet' type='text/css' href='styleprofile.css'>
    <link rel='stylesheet' type='text/css' href='modal.css'>
    <link rel='stylesheet' type='text/css' href='mensagens.css'> <!-- NOVO CSS -->
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
    <style>
        /* Pequenos ajustes inline */
        .btn-mensagem { background-color: #5bc0de; color: white; margin-left: 5px; border:none; padding: 5px 10px; border-radius:4px; cursor:pointer; }
        .btn-mensagem:hover { background-color: #31b0d5; }
        .btn-atender { background-color: #f0ad4e; color: white; border:none; padding: 5px 10px; border-radius:4px; cursor:pointer; }
        .btn-atender:hover { background-color: #ec971f; }
        .info-atendida { color: #2e7d32; font-weight: bold; font-size: 0.9em; }
    </style>
</head>
<body>

    <?php include 'header_enf.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>Olá, Enfermeiro(a) <?php echo htmlspecialchars($nome_completo); ?>!</h2>

                <?php if (!$config_completa): ?>
                    <!-- ALERTA DE CONFIGURAÇÃO -->
                    <div class="alerta-config" style="text-align: center; padding: 40px;">
                        <i class="fas fa-user-nurse" style="font-size: 50px; color: #3d8cb1; margin-bottom: 20px;"></i>
                        <h3>Configuração Necessária</h3>
                        <p>Configure seu Posto de Saúde para começar.</p>
                        <div id="button_center"><a href="enf/configuracoes_enf.php" class="submit-btn">Configurar Agora</a></div>
                    </div>
                
                <?php else: ?>
                    <div class="header-paciente">
                        <p class="posto-info"><i class="fas fa-hospital-alt"></i> Atuando em: <strong><?php echo htmlspecialchars($nome_posto_enfermeiro); ?></strong></p>
                    </div>

                    <!-- SEÇÃO DE SOLICITAÇÕES / MENSAGENS DOS PACIENTES -->
                    <div class="card-lembretes" style="background-color: #fff; border: 1px solid #ddd; border-left: 5px solid #ff9800; padding: 0;">
                        <div style="padding: 15px; background-color: #fff3e0;">
                            <h4 style="margin:0; color: #e65100;"><i class="fas fa-inbox"></i> Solicitações do Posto</h4>
                        </div>
                        
                        <?php if (empty($solicitacoes)): ?>
                            <p style="padding: 20px;">Nenhuma mensagem recebida dos pacientes.</p>
                        <?php else: ?>
                            <ul class="message-list">
                                <?php foreach ($solicitacoes as $msg): ?>
                                    <li class="message-item">
                                        <!-- Cabeçalho Clicável -->
                                        <div class="message-header" onclick="toggleMessage(this)">
                                            <span class="subject">
                                                <?php if($msg['status']=='pendente') echo '<i class="fas fa-exclamation-circle" style="color:orange"></i> '; ?>
                                                <?php echo htmlspecialchars($msg['assunto']); ?> 
                                                <small style="color:#888; font-weight:normal;">- <?php echo htmlspecialchars($msg['nome_paciente']); ?></small>
                                            </span>
                                            <div class="meta-info">
                                                <span><?php echo date('d/m H:i', strtotime($msg['data_envio'])); ?></span>
                                                <?php if($msg['status']=='atendida'): ?>
                                                    <span class="status-badge badge-atendida">Atendida</span>
                                                <?php else: ?>
                                                    <span class="status-badge badge-pendente">Pendente</span>
                                                <?php endif; ?>
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                        </div>
                                        <!-- Corpo Expansível -->
                                        <div class="message-body">
                                            <p><?php echo nl2br(htmlspecialchars($msg['corpo'])); ?></p>
                                            
                                            <div class="atendimento-area">
                                                <?php if($msg['status']=='pendente'): ?>
                                                    <p style="font-size:0.9em; color:#666; margin-bottom:5px;">Este paciente precisa de ajuda. Marque como atendido para informar a equipe.</p>
                                                    <button class="btn-atender" onclick="marcarAtendida(<?php echo $msg['id_mensagem']; ?>, this)">
                                                        <i class="fas fa-check"></i> Marcar como Atendida
                                                    </button>
                                                <?php else: ?>
                                                    <span class="info-atendida">
                                                        <i class="fas fa-check-double"></i> 
                                                        Atendida por <?php echo htmlspecialchars($msg['nome_atendente']); ?> 
                                                        em <?php echo date('d/m/Y H:i', strtotime($msg['data_atendimento'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- LISTA DE PACIENTES -->
                    <h3 style="margin-top: 40px;"><i class="fas fa-users"></i> Pacientes do Posto</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>Telefone</th>
                                    <th id="actions">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_pacientes as $paciente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($paciente['nome_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($paciente['cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($paciente['telefone']); ?></td>
                                        <td class="btns-edit">
                                            <button class="action-btn" onclick="abrirCaderneta(<?php echo $paciente['id_usuario']; ?>, '<?php echo $paciente['nome_completo']; ?>')">
                                                <i class="fas fa-syringe"></i> Gerenciar
                                            </button>
                                            <button class="btn-mensagem" onclick="abrirModalMensagem(<?php echo $paciente['id_usuario']; ?>, '<?php echo $paciente['nome_completo']; ?>')">
                                                <i class="fas fa-envelope"></i> Mensagem
                                            </button>
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

    <!-- MODAL DE VACINAS (JÁ EXISTENTE) -->
    <div id="modal-vacinas" class="modal-logout">
        <div class="modal-content-logout modal-vacinas">
            <h3 id="modal-paciente-nome">Vacinação</h3>
            <div id="modal-body-content" style="max-height: 60vh; overflow-y: auto; margin: 20px 0;"></div>
            <button onclick="document.getElementById('modal-vacinas').style.display='none'" style="background-color:#666;color:white;">Fechar</button>
        </div>
    </div>

    <!-- MODAL ENVIAR MENSAGEM -->
    <div id="modal-mensagem" class="modal-logout2">
        <div class="modal-content-logout2" style="height:auto; text-align:left;">
            <h3 style="text-align: center; margin-bottom: 10px; text-decoration: underline; font-family: calibri;">NOVA MENSAGEM</h3>
            <p style="margin-bottom: 10px; font-family: calibri;">Para: <strong id="msg-destinatario-nome">Paciente</strong></p>
            <form id="form-mensagem">
                <input type="hidden" name="acao" value="enviar">
                <input type="hidden" name="tipo" value="privada">
                <input type="hidden" name="id_destinatario" id="msg-id-destinatario">
                
                <div class="input-group">
                    <label>Assunto</label>
                    <input type="text" name="assunto" required placeholder="Ex: Comparecer ao posto...">
                </div>
                <div class="input-group">
                    <label>Mensagem</label>
                    <textarea name="corpo"  rows="4" style="width:100%; height: 120px; padding:10px; resize: none;" required placeholder="Digite sua mensagem..."></textarea>
                </div>
                <div style="text-align:center; margin-top:15px;">
                    <button type="submit" class="submit-btn">Enviar</button>
                    <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-mensagem').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'modal_logout.html'; ?>
    <script src="modal.js"></script>
    
    <script>
        // Função Acordeão para Mensagens
        function toggleMessage(header) {
            const body = header.nextElementSibling;
            const isOpen = body.style.display === 'block';
            
            // Fecha todos (opcional, se quiser abrir um por vez)
            // document.querySelectorAll('.message-body').forEach(el => el.style.display = 'none');

            if (!isOpen) {
                body.style.display = 'block';
                header.querySelector('.fa-chevron-down').classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                body.style.display = 'none';
                header.querySelector('.fa-chevron-up').classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        // Lógica do Modal de Mensagem
        function abrirModalMensagem(id, nome) {
            document.getElementById('msg-id-destinatario').value = id;
            document.getElementById('msg-destinatario-nome').innerText = nome;
            document.getElementById('modal-mensagem').style.display = 'block';
            document.getElementById('form-mensagem').reset();
        }

        document.getElementById('form-mensagem').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('processa_mensagem.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    alert('Mensagem enviada com sucesso!');
                    document.getElementById('modal-mensagem').style.display = 'none';
                } else {
                    alert('Erro: ' + d.msg);
                }
            });
        });

        // Lógica para Marcar como Atendida
        function marcarAtendida(idMsg, btn) {
            if(!confirm("Confirmar que esta solicitação foi atendida?")) return;
            
            const fd = new FormData();
            fd.append('acao', 'atender');
            fd.append('id_mensagem', idMsg);

            fetch('processa_mensagem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    // Recarrega a página para atualizar a lista e mostrar quem atendeu
                    location.reload();
                } else {
                    alert('Erro: ' + d.msg);
                }
            });
        }

        // Mantém lógica da vacina (código anterior simplificado aqui para não ocupar espaço)
        const modalVacinas = document.getElementById('modal-vacinas');
        function abrirCaderneta(id, nome) {
             modalVacinas.style.display = "block";
             document.getElementById('modal-paciente-nome').innerText = "Vacinas: " + nome;
             document.getElementById('modal-body-content').innerHTML = "Carregando...";
             fetch(`enf/buscar_vacinas_paciente.php?id_paciente=${id}`).then(r => r.text()).then(h => document.getElementById('modal-body-content').innerHTML = h);
        }
        function aplicarVacina(p, m, btn) {
             // Lógica de aplicar vacina (mesma do arquivo anterior)
             if(!confirm("Confirmar aplicação?")) return;
             const fd = new FormData(); fd.append('id_paciente', p); fd.append('id_vacina_modelo', m);
             fetch('enf/registrar_vacina.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success){ btn.innerText="Aplicada"; btn.disabled=true; } });
        }
    </script>
</body>
</html>