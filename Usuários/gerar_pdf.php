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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Painel do Paciente</title>
    <!-- Estilos -->
    <link rel='stylesheet' type='text/css' media='screen' href='paciente.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='gerar_pdf.css'>
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
                        <div class="buttons-box-pdf">
                            <div class="button-pdf">
                                <a href="paciente.php" id="a-none">
                                    <button class="btn-principal">
                                        <i class="fas fa-file-pdf"></i> Voltar ao Início
                                    </button>
                                </a>
                            </div>
                            <div class="button-pdf">
                                <button id="btn-gerar-pdf" class="btn-principal" onclick="gerarPDF()">
                                    <i class="fas fa-file-pdf"></i> Baixar Caderneta em PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'modal_logout.html'; ?>
    <script src='modal.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // NOVA FUNÇÃO: Gerar PDF
        function gerarPDF() {
            // 1. Seleciona o elemento que queremos converter (o container é mais seguro)
            const elemento = document.querySelector(".tabela-vacinas-container"); // Selecione o contêiner!
            
            // Configurações do PDF
            const options = {
                margin:       [10, 10, 10, 10], // Margens
                filename:     'Minha_Carteira_Vacinacao_VivaMais.pdf', // Nome do arquivo
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 }, // Aumenta a escala para melhor qualidade
                // ⚠️ Mude para orientação PAISAGEM para caber mais conteúdo
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' } 
            };

            // ... (Código do botão)

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