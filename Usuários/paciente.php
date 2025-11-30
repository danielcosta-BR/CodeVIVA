<?php
// Usuarios/paciente.php

// Define a função permitida para esta página
$funcao_permitida = 'paciente';
// Inclui o script de verificação
include 'verificar_acesso.php'; 
// Inclui a conexão com o banco de dados
include 'conexao.php'; 

// Variáveis de sessão
$id_usuario = $_SESSION['id_usuario'] ?? null;
$nome_completo = $_SESSION['nome_completo'] ?? 'Usuário';

// =========================================================================
// 1. LÓGICA DE VERIFICAÇÃO E BUSCA DE DADOS
// =========================================================================

$config_completa = false;
$id_posto_saude = null;
$posto_saude_paciente = "Não Definido";

// Busca dados do posto na tabela pacientes
if ($id_usuario) {
    $sql_paciente = "
        SELECT p.id_posto_saude, ps.nome_posto 
        FROM pacientes p 
        LEFT JOIN postosaude ps ON p.id_posto_saude = ps.id_posto 
        WHERE p.id_usuario = ?
    ";
    $stmt_p = $conn->prepare($sql_paciente);
    $stmt_p->bind_param("i", $id_usuario);
    $stmt_p->execute();
    $result_p = $stmt_p->get_result();

    if ($result_p->num_rows > 0) {
        $dados_paciente = $result_p->fetch_assoc();
        $id_posto_saude = $dados_paciente['id_posto_saude'];
        $posto_saude_paciente = $dados_paciente['nome_posto'] ?? "Não Definido";
        
        // Se tiver ID de posto válido, consideramos configuração completa
        if ($id_posto_saude > 0) {
            $config_completa = true;
        }
    }
    $stmt_p->close();
}

// ==========================================================================
// LÓGICA DO ALERTA ALEATÓRIO DE DOENÇA
// ==========================================================================
$alerta_titulo = "Dica de Saúde";
$alerta_msg = "Mantenha sua carteira de vacinação sempre atualizada e hábitos saudáveis!";
$alerta_classe = "info"; // Padrão (Azul)

if ($config_completa) {
    // Busca 1 doença aleatória que o paciente tem (da tabela paciente_doencas)
    $sql_alerta = "
        SELECT d.nome_doenca, d.mensagem_alerta 
        FROM paciente_doencas pd
        JOIN doencas d ON pd.id_doenca = d.id_doenca
        WHERE pd.id_paciente = ?
        ORDER BY RAND()
        LIMIT 1
    ";
    $stmt_a = $conn->prepare($sql_alerta);
    $stmt_a->bind_param("i", $id_usuario);
    $stmt_a->execute();
    $res_a = $stmt_a->get_result();
    
    if ($row_a = $res_a->fetch_assoc()) {
        if ($row_a['nome_doenca'] === 'Nenhuma') {
            // Se for "Nenhuma", mostra a mensagem genérica cadastrada pelo admin para "Nenhuma"
            $alerta_titulo = "Bem-estar";
            $alerta_msg = htmlspecialchars($row_a['mensagem_alerta']); 
            $alerta_classe = "success"; // Verde
        } else {
            // Se for uma doença específica
            $alerta_titulo = "Cuidado com: " . htmlspecialchars($row_a['nome_doenca']);
            $alerta_msg = htmlspecialchars($row_a['mensagem_alerta']);
            $alerta_classe = "warning"; // Laranja (atenção)
        }
    }
    $stmt_a->close();
}

// =========================================================================
// 2. BUSCA DE VACINAS E MENSAGENS (Se configurado)
// =========================================================================

$vacinas_do_paciente = [];
$mensagens_recebidas = [];

if ($config_completa) {

    // 2.1 Buscar Vacinas
    $sql_vacinas = "
        SELECT 
            vm.nome_vacina, 
            vm.recomendacao_idade, 
            c.data_tomada,
            c.data_prevista,
            u_enf.nome_completo AS nome_enfermeiro
        FROM 
            vacinamodelo vm
        LEFT JOIN 
            caderneta c ON vm.id_vacina_modelo = c.id_vacina_modelo AND c.id_paciente = ?
        LEFT JOIN
            usuario u_enf ON c.id_enfermeiro_aplicador = u_enf.id_usuario
        ORDER BY 
            vm.nome_vacina ASC
    ";
    $stmt_v = $conn->prepare($sql_vacinas);
    $stmt_v->bind_param("i", $id_usuario);
    $stmt_v->execute();
    $res_v = $stmt_v->get_result();
    while ($row = $res_v->fetch_assoc()) {
        $vacinas_do_paciente[] = $row;
    }
    $stmt_v->close();

    // 2.2 Buscar Mensagens Recebidas (Diretas do Enfermeiro)
    $sql_msg = "
        SELECT 
            m.assunto, 
            m.corpo, 
            m.data_envio, 
            u.nome_completo as nome_enfermeiro
        FROM mensagens m
        JOIN usuario u ON m.id_remetente = u.id_usuario
        WHERE m.id_destinatario = ?
        ORDER BY m.data_envio DESC
    ";
    $stmt_m = $conn->prepare($sql_msg);
    $stmt_m->bind_param("i", $id_usuario);
    $stmt_m->execute();
    $res_m = $stmt_m->get_result();
    while ($row = $res_m->fetch_assoc()) {
        $mensagens_recebidas[] = $row;
    }
    $stmt_m->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Esse cara aqui comanda a responsividade para os dispositivos -->
    <title>VIVA+ | Painel do Paciente</title>
    <!-- Estilos -->
    <link rel='stylesheet' type='text/css' media='screen' href='paciente.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='adm/tables.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../stylepct.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='mensagens.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>

</head>
<body>

    <?php include 'header_pct.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>👋 Bem-vindo(a), <?php echo htmlspecialchars($nome_completo); ?>!</h2>
                
                <?php if (!$config_completa): ?>
                    <!-- ALERTA DE CADASTRO INCOMPLETO -->
                    <div class="alerta-config">
                        <p class="intro-alerta">
                            <i class="fas fa-exclamation-circle" ></i> 
                            Por favor, configure seu <b>local de atendimento (Posto de Saúde)</b> para visualizar sua caderneta.
                        </p>
                        <div id="button_center">
                            <a href="pct/configuracoes_pct.php" class="submit-btn"><i class="fas fa-cog"></i> Configurações</a>
                        </div>
                    </div>
                
                <?php else: ?>
                
                <!-- ALERT BOX ALEATÓRIO (DOENÇA) -->
                <!-- Só aparece se estiver configurado -->
                <div class="alert-box alert-<?php echo $alerta_classe; ?>">
                    <h4 id="h4-alert">
                        <i class="fas fa-info-circle"></i> <?php echo $alerta_titulo; ?>
                    </h4>
                    <p id="p-alert"><?php echo $alerta_msg; ?></p>
                </div>

                <!-- HEADER DO DASHBOARD -->
                <div class="header-paciente">
                    <p class="posto-info"><i class="fas fa-hospital"></i> Posto de Saúde: <strong><?php echo htmlspecialchars($posto_saude_paciente); ?></strong></p>
                </div>

                <div class="painel-funcionalidades">
                    
                    <!-- 1. CADERNETA DE VACINAÇÃO -->
                    <div class="card-cardeneta">
                        <h3><i class="fas fa-syringe"></i> Minha Caderneta de Vacinação</h3>
                        <div class="tabela-vacinas-container table-responsive">
                            <table class="tabela-vacinas data-table">
                                <thead>
                                    <tr>
                                        <th>Vacina</th>
                                        <th>Recomendação</th>
                                        <th>Status</th>
                                        <th>Data Aplicação</th>
                                        <th>Próxima Dose</th>
                                        <th>Enfermeiro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($vacinas_do_paciente) > 0): ?>
                                        <?php foreach ($vacinas_do_paciente as $vacina): 
                                            $data_aplicacao = $vacina['data_tomada'];
                                            $data_prevista = $vacina['data_prevista'];
                                            $nome_enfermeiro = $vacina['nome_enfermeiro'];
                                            $hoje = date('Y-m-d');

                                            if (!empty($data_aplicacao)) {
                                                $status = 'Aplicada';
                                                $status_class = 'status-aplicada';
                                                $data_app = date('d/m/Y', strtotime($data_aplicacao));
                                                $data_prox = '-';
                                                $enf_display = !empty($nome_enfermeiro) ? htmlspecialchars($nome_enfermeiro) : 'Não informado';
                                            } elseif (!empty($data_prevista)) {
                                                $data_app = 'N/A';
                                                $data_prox = date('d/m/Y', strtotime($data_prevista));
                                                $enf_display = '-';
                                                if ($hoje < $data_prevista) {
                                                    $status = 'Aguardando';
                                                    $status_class = 'status-aguardando';
                                                } else {
                                                    $status = 'Pendente';
                                                    $status_class = 'status-pendente';
                                                }
                                            } else {
                                                $status = 'Não Tomada';
                                                $status_class = 'status-neutro';
                                                $data_app = '-';
                                                $data_prox = '-';
                                                $enf_display = '-';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vacina['nome_vacina']); ?></td>
                                                <td><?php echo htmlspecialchars($vacina['recomendacao_idade']); ?></td>
                                                <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                                                <td><?php echo $data_app; ?></td>
                                                <td><?php echo $data_prox; ?></td>
                                                <td><?php echo $enf_display; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">Nenhuma vacina encontrada.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="button-pdf">
                            <a href="gerar_pdf.php" id="a-none">
                                <button class="btn-principal">
                                    <i class="fas fa-file-pdf"></i> Página - Baixar PDF
                                </button>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 2. MENSAGENS RECEBIDAS -->
                    <div class="card-cardeneta">
                        <h3><i class="fas fa-envelope"></i> Mensagens Recebidas</h3>
                        
                        <?php if(empty($mensagens_recebidas)): ?>
                            <p id="recive-msg-pct"> Sua caixa de entrada está vazia.</p>
                        <?php else: ?>
                            <ul class="message-list">
                                <?php foreach ($mensagens_recebidas as $msg): ?>
                                    <li class="message-item">
                                        <!-- Título Clicável -->
                                        <div class="message-header" onclick="toggleMessage(this)">
                                            <div class="meta-all">
                                                <span class="subject">
                                                    <i class="fas fa-envelope"></i>
                                                    <?php echo htmlspecialchars($msg['assunto']); ?>
                                                </span>
    
                                            </div>
                                            
                                            <div class="meta-info">
                                                <span class="tag-enfermeiro">Enf. <?php echo htmlspecialchars($msg['nome_enfermeiro']); ?></span>
                                                <i class="fas fa-chevron-down"></i>
                                                <span id="date-id" ><?php echo date('d/m/Y', strtotime($msg['data_envio'])); ?></span>
                                            </div>
                                        </div>
                                        <!-- Corpo da Mensagem -->
                                        <div class="message-body">
                                            <p><?php echo nl2br(htmlspecialchars($msg['corpo'])); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- 3. CARDS LATERAIS -->
                    <div class="cards-laterais">
                        
                        <!-- Solicitar Apoio -->
                        <div class="card-apoio">
                            <h4><i class="fas fa-headset"></i> Solicitar Apoio</h4>
                            <p>Envie uma solicitação para a equipe de enfermagem do seu posto.</p>
                            <div id="button_center">
                                <button onclick="document.getElementById('modal-solicitacao').style.display='block'" class="btn-principal">
                                    <i class="fas fa-paper-plane"></i> Enviar Solicitação
                                </button>
                            </div>
                        </div>

                        <!-- Lembretes -->
                        <div class="card-lembretes">
                            <h4><i class="fas fa-bell"></i> Próximos Compromissos</h4>
                            <?php 
                            $lembrete_texto = 'Nenhum lembrete ativo.';
                            foreach ($vacinas_do_paciente as $v) {
                                if (!empty($v['data_prevista']) && empty($v['data_tomada'])) {
                                    $d_prev = date('d/m/Y', strtotime($v['data_prevista']));
                                    $lembrete_texto = "Próxima vacina: <strong>{$v['nome_vacina']}</strong> em <strong>{$d_prev}</strong>.";
                                    break;
                                }
                            }
                            ?>
                            <p><?php echo $lembrete_texto; ?></p>
                        </div>
                        
                    </div>
                    
                </div> <!-- Fim Painel -->
                <?php endif; ?>

            </div>
        </section>
    </main>

    <!-- MODAL DE SOLICITAÇÃO (Envia para o Posto) -->
    <div id="modal-solicitacao" class="modal-logout2">
        <div class="modal-content-logout3">
            <h3 class="msg-title">Nova Solicitação</h3>
            <p class="msg-info">Sua mensagem será visível para <strong>todos os enfermeiros</strong> do seu posto.</p>
            
            <form id="form-solicitacao">
                <!-- Campos ocultos para o processador -->
                <input type="hidden" name="acao" value="enviar">
                <input type="hidden" name="tipo" value="solicitacao">
                
                <div class="input-group">
                    <label>Assunto</label>
                    <input type="text" name="assunto" required placeholder="Ex: Visita Domiciliar, Dúvida sobre vacina...">
                </div>
                <div class="input-group">
                    <label>Mensagem</label>
                    <textarea name="corpo" rows="5"  required placeholder="Descreva detalhadamente o que você precisa..."></textarea>
                </div>

                <div class="submit-box">
                    <button type="submit" class="submit-btn">Enviar Mensagem</button>
                    <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-solicitacao').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'modal_logout.html'; ?>
    <script src='modal.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // Função Acordeão para abrir/fechar mensagens
        function toggleMessage(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('.fa-chevron-down, .fa-chevron-up');
            const isOpen = body.style.display === 'block';

            if (!isOpen) {
                body.style.display = 'block';
                if(icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                body.style.display = 'none';
                if(icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        // Envio do Formulário via AJAX
        document.getElementById('form-solicitacao').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Enviando...";
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('processa_mensagem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Sua solicitação foi enviada com sucesso!');
                    document.getElementById('modal-solicitacao').style.display = 'none';
                    this.reset();
                } else {
                    alert('Erro ao enviar: ' + data.msg);
                }
            })
            .catch(err => {
                alert('Erro de conexão.');
                console.error(err);
            })
            .finally(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });

        // NOVA FUNÇÃO: Gerar PDF
        function gerarPDF() {
            // Seleciona o elemento que queremos converter (sua tabela)
            const elemento = document.querySelector(".tabela-vacinas");
            
            // Configurações do PDF
            const options = {
                margin:       [10, 10, 10, 10], // Margens (topo, esq, baixo, dir)
                filename:     'Minha_Carteira_Vacinacao_VivaMais.pdf', // Nome do arquivo
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 }, // Aumenta a escala para melhorar qualidade
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' } // Formato A4
            };

            // Oculta o botão temporariamente para dar feedback visual (opcional)
            const btn = document.getElementById('btn-gerar-pdf');
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
            btn.disabled = true;

            // Gera o PDF
            html2pdf().set(options).from(elemento).save().then(function(){
                // Restaura o botão após o download iniciar
                btn.innerHTML = textoOriginal;
                btn.disabled = false;
            });
        }
    </script>

</body>
</html>