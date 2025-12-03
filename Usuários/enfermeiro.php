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
        $nome_posto_enfermeiro = $dados_enf['nome_posto'] ?? "Posto não encontrado";
    }
}
$stmt_check->close();

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

    // 2. Lista Solicitações
    $sql_msg = "SELECT m.id_mensagem, m.assunto, m.corpo, m.data_envio, m.status, m.data_atendimento, u_rem.nome_completo as nome_paciente, u_atend.nome_completo as nome_atendente FROM mensagens m JOIN usuario u_rem ON m.id_remetente = u_rem.id_usuario LEFT JOIN usuario u_atend ON m.id_atendente = u_atend.id_usuario WHERE m.id_posto_alvo = ? ORDER BY m.status ASC, m.data_envio DESC";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Enfermeiro</title>
    <link rel='stylesheet' type='text/css' href='enfermeiro.css'>
    <link rel='stylesheet' type='text/css' href='../styleenf.css'>
    <link rel='stylesheet' type='text/css' href='modal.css'>
    <link rel='stylesheet' type='text/css' href='mensagens.css'>
    <link rel='stylesheet' type='text/css' href='adm/tables.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
</head>

<body>

    <?php include 'header_enf.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>Olá, Enfermeiro(a) <?php echo htmlspecialchars($nome_completo); ?>!</h2>

                <?php if (!$config_completa): ?>
                    <div class="alerta-config">
                        <i class="fas fa-user-nurse" style="font-size: 50px; color: #3d8cb1; margin-bottom: 20px;"></i>
                        <h3>Configuração Necessária</h3>
                        <p>Configure seu Posto de Saúde para começar.</p>
                        <div id="button_center"><a href="enf/configuracoes_enf.php" class="submit-btn">Configurar Agora</a></div>
                    </div>
                <?php else: ?>
                    <div class="header-paciente">
                        <p class="posto-info"><i class="fas fa-hospital-alt"></i> Atuando em: <strong><?php echo htmlspecialchars($nome_posto_enfermeiro); ?></strong></p>
                    </div>

                    <div class="card-lembretes" style="background-color: #fff; border: 1px solid #ddd; border-left: 5px solid #ff9800; padding: 0;">
                        <div style="padding: 15px; background-color: #fff3e0;">
                            <h4 style="margin:0; color: #e65100;"><i class="fas fa-inbox"></i> Solicitações do Posto</h4>
                        </div>
                        
                        <?php if (empty($solicitacoes)): ?>
                            <p style="padding: 20px;">Nenhuma mensagem recebida.</p>
                        <?php else: ?>
                            <ul class="message-list">
                                <?php foreach ($solicitacoes as $msg): ?>
                                    <li class="message-item">
                                        <div class="message-header" onclick="toggleMessage(this)">
                                            <span class="subject">
                                                <?php if($msg['status']=='pendente') echo '<i class="fas fa-exclamation-circle" style="color:orange"></i> '; ?>
                                                <?php echo htmlspecialchars($msg['assunto']); ?> 
                                                <small style="color:#888;">- <?php echo htmlspecialchars($msg['nome_paciente']); ?></small>
                                            </span>
                                            <div class="meta-info">
                                                <span><?php echo date('d/m H:i', strtotime($msg['data_envio'])); ?></span>
                                                <span class="status-badge <?php echo ($msg['status']=='atendida'?'badge-atendida':'badge-pendente'); ?>">
                                                    <?php echo ucfirst($msg['status']); ?>
                                                </span>
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                        </div>
                                        <div class="message-body">
                                            <p><?php echo nl2br(htmlspecialchars($msg['corpo'])); ?></p>
                                            <div class="atendimento-area">
                                                <?php if($msg['status']=='pendente'): ?>
                                                    <button class="btn-atender" onclick="marcarAtendida(<?php echo $msg['id_mensagem']; ?>, this)">
                                                        <i class="fas fa-check"></i> Marcar como Atendida
                                                    </button>
                                                <?php else: ?>
                                                    <span class="info-atendida">Atendida por <?php echo htmlspecialchars($msg['nome_atendente']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

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
                                        <td>
                                            <div class="btns-edit">
                                                <button class="action-btn" type="button" onclick="window.abrirCaderneta(<?php echo $paciente['id_usuario']; ?>, '<?php echo addslashes($paciente['nome_completo']); ?>')">
                                                    <i class="fas fa-syringe"></i> Gerenciar
                                                </button>
                                                <button class="btn-mensagem" type="button" onclick="window.abrirModalMensagem(<?php echo $paciente['id_usuario']; ?>, '<?php echo addslashes($paciente['nome_completo']); ?>')">
                                                    <i class="fas fa-envelope"></i> Mensagem
                                                </button>
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

    <div id="modal-vacinas" class="modal-logout ">
        <div class="modal-content-logout3">
            <!-- <span class="close" onclick="document.getElementById('modal-vacinas').style.display='none'">&times;</span> -->
            <h3 id="modal-paciente-nome">Vacinas</h3>
            
            <div class="section-nav-container-modal" id="section-nav-container-modal" >
                <button class="nav-btn-modal" id="nav-prev-modal" disabled> < </i></button>
                <div class="section-tabs-modal" id="section-tabs-modal"></div>
                <button class="nav-btn-modal" id="nav-next-modal" disabled> > </button>
            </div>
            
            <div id="modal-body-content" style="max-height: 70vh; overflow-y: auto;">
                Carregando...
            </div>
            <div style="text-align:center; margin-top:15px;">
                <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-vacinas').style.display='none'">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="modal-mensagem" class="modal-logout2" style="z-index: 9999;">
        <div class="modal-content-logout3">
            <h3 class="msg-title">NOVA MENSAGEM</h3>
            <p class="msg-info">Para: <strong id="msg-destinatario-nome">Paciente</strong></p>
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
                    <textarea name="corpo" rows="4" style="width:100%; height: 120px; padding:10px; resize: none;" required placeholder="Digite sua mensagem..."></textarea>
                </div>
                <div style="text-align:center; margin-top:15px;">
                    <button type="submit" class="submit-btn">Enviar</button>
                    <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-mensagem').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="modal.js"></script> 

    <script>
        // FUNÇÕES GLOBAIS (Anexadas ao window para evitar erros de escopo)
        
        // 1. Alternar visualização de mensagem
        window.toggleMessage = function(header) {
            const body = header.nextElementSibling;
            const isOpen = body.style.display === 'block';
            if (!isOpen) {
                body.style.display = 'block';
                const icon = header.querySelector('.fa-chevron-down');
                if(icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                body.style.display = 'none';
                const icon = header.querySelector('.fa-chevron-up');
                if(icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        // 2. Modal Mensagem
        window.abrirModalMensagem = function(id, nome) {
            const modal = document.getElementById('modal-mensagem');
            if(modal) {
                document.getElementById('msg-id-destinatario').value = id;
                document.getElementById('msg-destinatario-nome').innerText = nome;
                modal.style.display = 'block';
                document.getElementById('form-mensagem').reset();
            } else {
                console.error("Modal de mensagem não encontrado!");
            }
        }

        // 3. Processar Envio de Mensagem
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
            })
            .catch(err => alert("Erro na comunicação com o servidor."));
        });

        // 4. Marcar Atendida
        window.marcarAtendida = function(idMsg, btn) {
            if(!confirm("Confirmar que esta solicitação foi atendida?")) return;
            const fd = new FormData();
            fd.append('acao', 'atender');
            fd.append('id_mensagem', idMsg);
            fetch('processa_mensagem.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) location.reload();
                else alert('Erro: ' + d.msg);
            });
        }

        // 5. NAVEGAÇÃO DO MODAL DE VACINAS
        function setupModalNavigation() {
            const sectionsContainer = document.getElementById('modal-body-content');
            const sections = sectionsContainer.querySelectorAll('.tabela-vacinas-secao');
            const navContainer = document.getElementById('section-nav-container-modal');
            const tabsContainer = document.getElementById('section-tabs-modal');
            const navPrev = document.getElementById('nav-prev-modal');
            const navNext = document.getElementById('nav-next-modal');
            let activeIndex = 0;
            
            tabsContainer.innerHTML = '';
            
            if (sections.length > 0) {
                navContainer.style.display = 'flex';
                sections.forEach((section, index) => {
                    const sectionTitleEl = section.querySelector('h4');
                    const sectionTitle = sectionTitleEl ? sectionTitleEl.textContent : `Seção ${index + 1}`; 
                    const tab = document.createElement('div');
                    tab.classList.add('section-tab-modal');
                    tab.textContent = sectionTitle;
                    tab.dataset.index = index;
                    tabsContainer.appendChild(tab);
                    tab.addEventListener('click', () => showSection(index));
                    
                    if (index !== 0) section.classList.add('hidden-section-modal');
                    else section.classList.remove('hidden-section-modal');
                });
                
                const showSection = (index) => {
                    if (index < 0 || index >= sections.length) return;
                    sections.forEach(s => s.classList.add('hidden-section-modal'));
                    tabsContainer.querySelectorAll('.section-tab-modal').forEach(t => t.classList.remove('active'));
                    sections[index].classList.remove('hidden-section-modal');
                    tabsContainer.querySelector(`[data-index="${index}"]`).classList.add('active');
                    activeIndex = index;
                    navPrev.disabled = activeIndex === 0;
                    navNext.disabled = activeIndex === sections.length - 1;
                    const activeTab = tabsContainer.querySelector('.section-tab-modal.active');
                    if(activeTab) {
                        tabsContainer.scroll({ left: activeTab.offsetLeft - (tabsContainer.offsetWidth / 2) + (activeTab.offsetWidth / 2), behavior: 'smooth' });
                    }
                };

                navPrev.onclick = () => showSection(activeIndex - 1);
                navNext.onclick = () => showSection(activeIndex + 1);
                showSection(0);
            } else {
                navContainer.style.display = 'none';
            }
        }

        // 6. ABRIR CADERNETA (VACINAS)
        window.abrirCaderneta = function(id, nome) {
            const modalVacinas = document.getElementById('modal-vacinas');
            if(modalVacinas) {
                modalVacinas.style.display = "block";
                document.getElementById('modal-paciente-nome').innerText = "Vacinas: " + nome;
                document.getElementById('modal-body-content').innerHTML = "<div class='loading'>Carregando dados...</div>";
                document.getElementById('section-nav-container-modal').style.display = 'none';
                
                fetch(`enf/buscar_vacinas_paciente.php?id_paciente=${id}`)
                    .then(r => r.text())
                    .then(h => {
                        document.getElementById('modal-body-content').innerHTML = h;
                        setupModalNavigation();
                    })
                    .catch(e => {
                        document.getElementById('modal-body-content').innerHTML = "<p style='color:red; text-align:center'>Erro ao carregar.</p>";
                    });
            }
        }

        // 7. APLICAR VACINA (Chamada pelo HTML carregado via Fetch)
        window.aplicarVacina = function(p, m, btn) {
            if(!confirm("Confirmar aplicação?")) return;
            const fd = new FormData(); 
            fd.append('id_paciente', p); 
            fd.append('id_vacina_modelo', m);
            
            fetch('enf/registrar_vacina.php', {method:'POST', body:fd})
            .then(r=>r.json())
            .then(d=>{ 
                if(d.success){ 
                    btn.innerText="Aplicada"; 
                    btn.disabled=true;
                    btn.style.backgroundColor = "#ccc";
                    // Opcional: Recarregar o modal para atualizar status
                    // abrirCaderneta(p, document.getElementById('modal-paciente-nome').innerText.replace("Vacinas: ", ""));
                } else {
                    alert("Erro: " + d.msg);
                }
            });
        }
    </script>
</body>
</html>