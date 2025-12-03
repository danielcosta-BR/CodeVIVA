<?php
// Usuarios/paciente.php

$funcao_permitida = 'paciente';
include 'verificar_acesso.php'; 
include 'conexao.php'; 

$id_usuario = $_SESSION['id_usuario'] ?? null;
$nome_completo = $_SESSION['nome_completo'] ?? 'Usuário';

// =========================================================================
// 1. LÓGICA DE VERIFICAÇÃO E BUSCA DE DADOS DO POSTO (INCLUINDO ENDEREÇO)
// =========================================================================
$config_completa = false;
$id_posto_saude = null;
$posto_saude_paciente = "Não Definido";
$endereco_posto = "Endereço não informado"; // Variável para o local da vacinação

if ($id_usuario) {
    // Adicionado ps.endereco na consulta
    $sql_paciente = "
        SELECT p.id_posto_saude, ps.nome_posto, ps.endereco 
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
        if (!empty($dados_paciente['id_posto_saude'])) {
            $config_completa = true;
            $id_posto_saude = $dados_paciente['id_posto_saude'];
            $posto_saude_paciente = htmlspecialchars($dados_paciente['nome_posto']);
            $endereco_posto = htmlspecialchars($dados_paciente['endereco'] ?? 'Consulte seu posto');
        }
    }
    $stmt_p->close();
}

// =========================================================================
// 2. BUSCA DE MENSAGENS (LEMBRETES DO ENFERMEIRO)
// =========================================================================
$mensagens_recebidas = [];
if ($id_usuario) {
    $sql_msg = "
        SELECT m.*, u.nome_completo as nome_remetente 
        FROM mensagens m 
        JOIN usuario u ON m.id_remetente = u.id_usuario
        WHERE m.id_destinatario = ? 
        ORDER BY m.data_envio DESC LIMIT 5";
    
    $stmt_m = $conn->prepare($sql_msg);
    $stmt_m->bind_param("i", $id_usuario);
    $stmt_m->execute();
    $res_m = $stmt_m->get_result();
    while($row = $res_m->fetch_assoc()){
        $mensagens_recebidas[] = $row;
    }
    $stmt_m->close();
}

// =========================================================================
// 3. BUSCA DE DOENÇAS CRÔNICAS (LÓGICA ALEATÓRIA)
// =========================================================================
$doencas_paciente = [];
$doenca_aleatoria = null; // Variável para armazenar apenas UMA doença

if ($id_usuario) {
    $sql_doencas = "
        SELECT d.nome_doenca, d.mensagem_alerta 
        FROM paciente_doencas pd 
        JOIN doencas d ON pd.id_doenca = d.id_doenca 
        WHERE pd.id_paciente = ?";
    
    $stmt_d = $conn->prepare($sql_doencas);
    $stmt_d->bind_param("i", $id_usuario);
    $stmt_d->execute();
    $res_d = $stmt_d->get_result();
    
    while($row = $res_d->fetch_assoc()){
        $doencas_paciente[] = $row;
    }
    $stmt_d->close();

    // LÓGICA DE ALEATORIEDADE:
    // Se houver doenças, escolhe uma aleatoriamente
    if (!empty($doencas_paciente)) {
        $chave_aleatoria = array_rand($doencas_paciente);
        $doenca_aleatoria = $doencas_paciente[$chave_aleatoria];
    }
}

// =========================================================================
// 4. BUSCA E CRUZAMENTO DE VACINAS + FILTRO DE LEMBRETES
// =========================================================================

// A. Buscar TODOS os Modelos
$todos_modelos = [];
$sql_modelos = "SELECT * FROM vacinamodelo ORDER BY secao, nome_vacina ASC";
$res_modelos = $conn->query($sql_modelos);
while($row = $res_modelos->fetch_assoc()){
    $todos_modelos[] = $row;
}

// B. Buscar o que JÁ existe na Caderneta
$caderneta = [];
$sql_cad = "
    SELECT c.*, e.nome_completo as nome_enfermeiro 
    FROM caderneta c 
    LEFT JOIN usuario e ON c.id_enfermeiro_aplicador = e.id_usuario 
    WHERE c.id_paciente = ?";
$stmt_c = $conn->prepare($sql_cad);
$stmt_c->bind_param("i", $id_usuario);
$stmt_c->execute();
$res_c = $stmt_c->get_result();
while($row = $res_c->fetch_assoc()){
    $caderneta[$row['id_vacina_modelo']] = $row;
}
$stmt_c->close();

// C. Cruzar dados e Gerar Lembretes
$vacinas_agrupadas = [];
$lembretes_vacinas = []; // Array para armazenar os lembretes de vacinação

foreach ($todos_modelos as $modelo) {
    $id_mod = $modelo['id_vacina_modelo'];
    $secao = htmlspecialchars($modelo['secao'] ?? 'Geral');
    if(empty($secao)) $secao = 'Geral';

    $item = [
        'nome_vacina' => $modelo['nome_vacina'],
        'recomendacao_idade' => $modelo['recomendacao_idade'],
        'data_prevista' => null,
        'data_tomada' => null,
        'nome_enfermeiro' => '-'
    ];

    if (isset($caderneta[$id_mod])) {
        $reg = $caderneta[$id_mod];
        $item['data_prevista'] = $reg['data_prevista'];
        $item['data_tomada'] = $reg['data_tomada'];
        $item['nome_enfermeiro'] = $reg['nome_enfermeiro'];
        
        // --- LÓGICA DO LEMBRETE DE VACINA ---
        // Se tem data prevista E não foi tomada ainda
        if (!empty($reg['data_prevista']) && empty($reg['data_tomada'])) {
            $data_prevista_ts = strtotime($reg['data_prevista']);
            $hoje_ts = strtotime(date('Y-m-d'));
            
            // Verifica status
            $status_lembrete = '';
            $dias_diferenca = ($data_prevista_ts - $hoje_ts) / (60 * 60 * 24);

            if ($data_prevista_ts < $hoje_ts) {
                $status_lembrete = 'atrasada'; // Atrasada
            } elseif ($dias_diferenca <= 30) {
                $status_lembrete = 'proxima'; // Próxima (nos próximos 30 dias)
            }

            // Adiciona ao array de lembretes se for atrasada ou próxima
            if ($status_lembrete) {
                $lembretes_vacinas[] = [
                    'vacina' => $modelo['nome_vacina'],
                    'data' => $reg['data_prevista'],
                    'status' => $status_lembrete
                ];
            }
        }
    }

    if (!isset($vacinas_agrupadas[$secao])) {
        $vacinas_agrupadas[$secao] = [];
    }
    $vacinas_agrupadas[$secao][] = $item;
}

// D. Ordenar Seções
$ordem_secoes_base = ['Gestante', 'Criança', 'Adolescente e Jovem', 'Adulto', 'Idoso', 'Geral'];
$secoes_ordenadas = [];
foreach ($ordem_secoes_base as $s) {
    if (isset($vacinas_agrupadas[$s])) {
        $secoes_ordenadas[$s] = $vacinas_agrupadas[$s];
        unset($vacinas_agrupadas[$s]);
    }
}
$secoes_ordenadas = array_merge($secoes_ordenadas, $vacinas_agrupadas);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Paciente</title>
    <link rel='stylesheet' type='text/css' href='paciente.css'>
    <link rel='stylesheet' type='text/css' href='../stylepct.css'>
    <link rel='stylesheet' type='text/css' href='adm/tables.css'>
    <link rel='stylesheet' type='text/css' href='modal.css'>
    <link rel='stylesheet' type='text/css' href='mensagens.css'> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/b35e028b12.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header_pct.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>Bem-vindo(a), <?php echo htmlspecialchars($nome_completo); ?></h2>

                <?php if (!$config_completa): ?>
                    <div class="p-alerta-config">
                        <p class="alerta-config">⚠️ Seu cadastro está incompleto. Por favor, solicite a um(a) administrador(a) que vincule seu perfil a um Posto de Saúde.</p>
                    </div>
                <?php endif; ?>
                
                <p class="data-group">Seu Posto de Saúde Vinculado: <span style="font-weight: bold; color: #3d8cb1;"><?php echo $posto_saude_paciente; ?></span></p>

                <?php if ($doenca_aleatoria): ?>
                    <div class="bloco-mural" style="background-color: #fff3e0; border-left: 5px solid #ff9800; padding: 15px; border-radius: 4px;">
                        <h4 style="margin-top: 0; color: #e65100;"><i class="fas fa-heartbeat"></i> Dica de Saúde do Dia</h4>
                        <div style="font-size: 0.95em;">
                            <p><strong><?php echo htmlspecialchars($doenca_aleatoria['nome_doenca']); ?></strong></p>
                            <p style="font-style: italic; color: #555;">
                                "<?php echo nl2br(htmlspecialchars($doenca_aleatoria['mensagem_alerta'] ?? 'Mantenha seus exames em dia.')); ?>"
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card-apoio" style="margin-bottom: 30px; margin-top: 30px;">
                    <h3 style="color: #00796b; border-bottom: 2px solid #00796b;">
                        <i class="fas fa-notes-medical"></i> Meu Mural de Saúde
                    </h3>

                    <div id="button_center">
                        <button onclick="abrirModalEnviarMensagem()">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitação ao Posto
                        </button>
                    </div>
                    
                    <div class="mural-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        
                        <?php if (!empty($lembretes_vacinas)): ?>
                            <div class="bloco-mural" style="background-color: #e3f2fd; border-left: 5px solid #2196f3; padding: 15px; border-radius: 4px;">
                                <h4 style="margin-top: 0; color: #0d47a1;"><i class="fas fa-calendar-alt"></i> Próximas Vacinas</h4>
                                <p style="font-size: 0.9em; margin-bottom: 10px;">
                                    <strong>Local:</strong> <?php echo $posto_saude_paciente; ?><br>
                                    <span style="color: #555; font-size: 0.85em;"><?php echo $endereco_posto; ?></span>
                                </p>
                                <ul style="list-style: none; padding: 0;">
                                    <?php foreach($lembretes_vacinas as $lembrete): ?>
                                        <li style="margin-bottom: 10px; background: #fff; padding: 8px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                            <div style="font-weight: bold;"><?php echo htmlspecialchars($lembrete['vacina']); ?></div>
                                            <div>
                                                Data: <strong><?php echo date('d/m/Y', strtotime($lembrete['data'])); ?></strong>
                                                <?php if($lembrete['status'] == 'atrasada'): ?>
                                                    <span style="color: red; font-weight: bold; font-size: 0.8em; margin-left: 5px;">(ATRASADA)</span>
                                                <?php else: ?>
                                                    <span style="color: green; font-weight: bold; font-size: 0.8em; margin-left: 5px;">(Em breve)</span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>


                    </div> <?php if(!empty($mensagens_recebidas)): ?>
                        <h4 style="color: #667; margin-top: 30px; border-bottom: 1px dashed #ccc; padding-bottom: 5px;">
                            <i class="fas fa-envelope-open-text"></i> Mensagens Recebidas
                        </h4>
                        <div class="message-list-paciente">
                            <?php foreach($mensagens_recebidas as $msg): ?>
                                <div class="message-item-paciente" style="border: 1px solid #eee; padding: 10px; border-radius: 4px; margin-bottom: 10px; background-color: #fafafa;">
                                    <div style="font-weight: bold; color: #3d8cb1; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($msg['assunto']); ?>
                                        <small style="color: #999; font-weight: normal; float: right;">
                                            <?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?>
                                        </small>
                                    </div>
                                    <div style="color: #555; font-size: 0.9em;">
                                        <?php echo nl2br(htmlspecialchars($msg['corpo'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($secoes_ordenadas) > 0): ?>
                    
                    <div class="card-cardeneta">
                        <h3>Minha Caderneta de Vacinação: <?php echo htmlspecialchars($nome_completo); ?></h3>

                        <div class="section-nav-container">
                            <button class="nav-btn" id="nav-prev" disabled> < </button>
                            <div class="section-tabs" id="section-tabs"></div>
                            <button class="nav-btn" id="nav-next" disabled> > </button>
                        </div>

                        <div id="vacinas-content">
                            <?php $i = 0; ?>
                            <?php foreach ($secoes_ordenadas as $secao_nome => $vacinas_lista): ?>
                                <?php 
                                    $is_active = $i === 0 ? '' : 'hidden-section';
                                    $secao_id = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($secao_nome)); 
                                ?>
                                
                                <div id="secao-<?php echo $secao_id; ?>" class="tabela-vacinas-secao <?php echo $is_active; ?>">
                                    <h4><?php echo htmlspecialchars($secao_nome); ?></h4>

                                    <div class="table-responsive">
                                        <table class="data-table tabela-vacinas">
                                            <thead>
                                                <tr>
                                                    <th>Vacina</th>
                                                    <th>Recomendação</th>
                                                    <th>Previsão</th>
                                                    <th>Aplicação</th>
                                                    <th>Aplicador</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($vacinas_lista as $vacina): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($vacina['nome_vacina']); ?></td>
                                                    <td><?php echo htmlspecialchars($vacina['recomendacao_idade'] ?? '-'); ?></td>
                                                    <td class="data-prevista">
                                                        <?php 
                                                            if ($vacina['data_tomada']) {
                                                                echo $vacina['data_prevista'] ? date('d/m/Y', strtotime($vacina['data_prevista'])) : '-';
                                                            } else {
                                                                if ($vacina['data_prevista']) {
                                                                    $data_ts = strtotime($vacina['data_prevista']);
                                                                    $hoje_ts = strtotime(date('Y-m-d'));
                                                                    
                                                                    if ($data_ts < $hoje_ts) {
                                                                        echo '<span style="color: red; font-weight: bold;">ATRASADA! ' . date('d/m/Y', $data_ts) . '</span>';
                                                                    } elseif ($data_ts == $hoje_ts) {
                                                                        echo '<span style="color: orange; font-weight: bold;">HOJE</span>';
                                                                    } else {
                                                                        echo date('d/m/Y', $data_ts);
                                                                    }
                                                                } else {
                                                                    echo 'Aguardando agendamento';
                                                                }
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="data-tomada" style="text-align: center;">
                                                        <?php if($vacina['data_tomada']): ?>
                                                            <div style="background-color: #d4edda; color: #155724; padding: 5px; border-radius: 4px;">
                                                                <?php echo date('d/m/Y', strtotime($vacina['data_tomada'])); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span style="color: #666;">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($vacina['nome_enfermeiro'] ?? '-'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div> 
                                </div> 
                                <?php $i++; ?>
                            <?php endforeach; ?>
                        </div> 
                        
                    </div>
                    <div class="button-pdf">
                        <button id="btn-gerar-pdf" class="btn-secundario" onclick="gerarPDF()">
                            <i class="fas fa-file-pdf"></i> Gerar PDF da Caderneta
                        </button>
                    </div>

                <?php else: ?>
                    <p class="msg-alerta">Nenhum modelo de vacina cadastrado no sistema.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'modal_logout.html'; ?>
    <script src="modal.js"></script>

    <div id="modal-enviar-mensagem" class="modal-logout2" style="z-index: 9999;">
        <div class="modal-content-logout3">
            <span class="close" onclick="document.getElementById('modal-enviar-mensagem').style.display='none'">&times;</span>
            <h3 class="msg-title">ENVIAR MENSAGEM AO POSTO</h3>
            <p class="msg-info">Destinatário: <strong id="msg-destinatario-nome-paciente"><?php echo $posto_saude_paciente; ?></strong></p>
            <form id="form-mensagem-paciente">
                <input type="hidden" name="acao" value="enviar">
                <input type="hidden" name="tipo" value="solicitacao">
                <input type="hidden" name="id_posto_alvo" value="<?php echo $id_posto_saude; ?>">
                
                <div class="input-group">
                    <label>Assunto (Obrigatório)</label>
                    <input type="text" name="assunto" required placeholder="Ex: Dúvida sobre agendamento...">
                </div>
                <div class="input-group">
                    <label>Mensagem</label>
                    <textarea name="corpo" rows="4" style="width:100%; height: 120px; padding:10px; resize: none;" required placeholder="Digite sua solicitação ou dúvida..."></textarea>
                </div>
                <div style="text-align:center; margin-top:15px;">
                    <button type="submit" class="submit-btn">Enviar Solicitação</button>
                    <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-enviar-mensagem').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEnviarMensagem() {
            const modal = document.getElementById('modal-enviar-mensagem');
            if (modal) {
                modal.style.display = 'block';
                document.getElementById('form-mensagem-paciente').reset();
            }
        }
        
        document.getElementById('form-mensagem-paciente')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerHTML = 'Enviando...';
            btn.disabled = true;

            const formData = new FormData(this);
            fetch('processa_mensagem.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    alert('Sua solicitação foi enviada com sucesso!');
                    document.getElementById('modal-enviar-mensagem').style.display = 'none';
                } else {
                    alert('Erro ao enviar mensagem: ' + d.msg);
                }
            })
            .catch(err => alert("Erro na comunicação com o servidor."))
            .finally(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });

        function gerarPDF() {
            const element = document.querySelector('.card-cardeneta');
            const opt = {
                margin: 0.5,
                filename: 'Minha_Caderneta_VivaMais.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const sectionsContainer = document.getElementById('vacinas-content');
            const sections = sectionsContainer ? sectionsContainer.querySelectorAll('.tabela-vacinas-secao') : [];
            const tabsContainer = document.getElementById('section-tabs');
            const navPrev = document.getElementById('nav-prev');
            const navNext = document.getElementById('nav-next');
            let activeIndex = 0;
            
            if (sections.length > 0) {
                sections.forEach((section, index) => {
                    const sectionTitleEl = section.querySelector('h4');
                    const sectionTitle = sectionTitleEl ? sectionTitleEl.textContent : `Seção ${index + 1}`; 
                    const tab = document.createElement('div');
                    tab.classList.add('section-tab');
                    tab.textContent = sectionTitle;
                    tab.dataset.index = index;
                    tabsContainer.appendChild(tab);
                    tab.addEventListener('click', () => showSection(index));
                });
                
                const showSection = (index) => {
                    if (index < 0 || index >= sections.length) return;
                    sections.forEach(s => s.classList.add('hidden-section'));
                    tabsContainer.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
                    sections[index].classList.remove('hidden-section');
                    tabsContainer.querySelector(`[data-index="${index}"]`).classList.add('active');
                    activeIndex = index;
                    navPrev.disabled = activeIndex === 0;
                    navNext.disabled = activeIndex === sections.length - 1;
                    const activeTab = tabsContainer.querySelector('.section-tab.active');
                    if(activeTab) {
                        tabsContainer.scroll({ left: activeTab.offsetLeft - (tabsContainer.offsetWidth / 2) + (activeTab.offsetWidth / 2), behavior: 'smooth' });
                    }
                };

                navPrev.addEventListener('click', () => showSection(activeIndex - 1));
                navNext.addEventListener('click', () => showSection(activeIndex + 1));
                showSection(0);
            } else {
                const navContainer = document.querySelector('.section-nav-container');
                if (navContainer) navContainer.style.display = 'none';
            }
        });
    </script>
</body>
</html>