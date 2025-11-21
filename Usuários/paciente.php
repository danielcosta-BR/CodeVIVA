<?php
// Define a função permitida para esta página
$funcao_permitida = 'paciente';
// Inclui o script de verificação
include 'verificar_acesso.php'; 
// Inclui a conexão com o banco de dados
include 'conexao.php'; 

// Variáveis de sessão já definidas em verificar_acesso.php
$id_usuario = $_SESSION['id_usuario'] ?? null;
$nome_completo = $_SESSION['nome_completo'] ?? 'Usuário';

// =========================================================================
// 1. LÓGICA DE VERIFICAÇÃO DE CONFIGURAÇÃO ESSENCIAL
// =========================================================================

$config_completa = false;
$id_posto_saude = null;

if ($id_usuario) {
    // 1.1. Buscar o ID do posto de saúde na nova tabela 'pacientes'
    $sql_paciente = "SELECT id_posto_saude FROM pacientes WHERE id_usuario = ?";
    $stmt_paciente = $conn->prepare($sql_paciente);
    
    if ($stmt_paciente) {
        $stmt_paciente->bind_param("i", $id_usuario);
        $stmt_paciente->execute();
        $result_paciente = $stmt_paciente->get_result();

        if ($result_paciente->num_rows > 0) {
            $dados_paciente = $result_paciente->fetch_assoc();
            $id_posto_saude = $dados_paciente['id_posto_saude'];
            
            // Verifica se o ID do posto de saúde está definido (não é nulo nem zero)
            if ($id_posto_saude > 0) {
                $config_completa = true;
            }
        }
        $stmt_paciente->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Painel do Paciente</title>
    <!-- Inclui os estilos base e de perfil -->
    <link rel='stylesheet' type='text/css' media='screen' href='administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='adm/tables.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../stylepct.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
    <style>
        /* Estilos adicionais para os novos status */
        .status-aguardando {
            color: #007bff; /* Azul */
            font-weight: bold;
            background-color: #e7f1ff;
            padding: 5px;
            /* border-radius: 5px; */
            text-align: center;
        }
        .status-pendente {
            color: #dc3545; /* Vermelho */
            font-weight: bold;
            background-color: #ffe6e6;
            padding: 5px;
            /* border-radius: 5px; */
            text-align: center;
        }
        .status-aplicada {
            color: #28a745; /* Verde */
            font-weight: bold;
            background-color: #d4edda;
            padding: 5px;
            /* border-radius: 5px; */
            text-align: center;
        }
        .status-neutro {
            color: #6c757d; /* Cinza */
            text-align: center;
            padding: 5px;
        }
    </style>
</head>
<body>

    <?php include 'header_pct.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>👋 Bem-vindo(a), <?php echo htmlspecialchars($nome_completo); ?>!</h2>
                
                <?php if (!$config_completa): ?>
                    <!-- TELA DE ALERTA DE CONFIGURAÇÃO (PRIMEIRO ACESSO/INCOMPLETO) -->
                    <div class="alerta-config">
                        <p class="intro-alerta">
                            <i class="fas fa-exclamation-circle" ></i> 
                            Por favor, antes de visualizar sua caderneta, clique no botão abaixo para configurar seu <b>local de atendimento (Posto de Saúde)</b> e outros dados essenciais para validarmos suas vacinas de forma correta.
                        </p>
                        <div id="button_center">
                            <a href="pct/configuracoes_pct.php" class="submit-btn">
                                <i class="fas fa-cog"></i> Configurações
                            </a>
                        </div>
                    </div>
                
                <?php 
                else: 
                
                $vacinas_do_paciente = [];
                $posto_saude_paciente = "Não Definido"; 

                // 2.1. Buscar nome do posto
                if ($id_posto_saude) {
                    $sql_posto = "SELECT nome_posto FROM postosaude WHERE id_posto = ?";
                    $stmt_posto = $conn->prepare($sql_posto);
                    $stmt_posto->bind_param("i", $id_posto_saude);
                    $stmt_posto->execute();
                    $result_posto = $stmt_posto->get_result();
                    if ($result_posto->num_rows > 0) {
                        $posto_saude_paciente = $result_posto->fetch_assoc()['nome_posto'];
                    }
                    $stmt_posto->close();
                }
                
                // 2.2. Buscar Vacinas
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
                
                $stmt_vacinas = $conn->prepare($sql_vacinas);
                if ($stmt_vacinas) {
                    $stmt_vacinas->bind_param("i", $id_usuario);
                    $stmt_vacinas->execute();
                    $result_vacinas = $stmt_vacinas->get_result();
                    while ($row = $result_vacinas->fetch_assoc()) {
                        $vacinas_do_paciente[] = $row;
                    }
                    $stmt_vacinas->close();
                }

                $conn->close();
                ?>
                
                <div class="header-paciente">
                    <p class="posto-info"><i class="fas fa-hospital"></i> Posto de Saúde: <strong><?php echo htmlspecialchars($posto_saude_paciente); ?></strong></p>
                </div>

                <div class="painel-funcionalidades">
                    
                    <!-- CARDENETA DE VACINAÇÃO -->
                    <div class="card-cardeneta">
                        <h3><i class="fas fa-syringe"></i> Minha Caderneta de Vacinação</h3>
                        <p class="intro-text">Aqui você pode visualizar todas as vacinas recomendadas e o seu status de aplicação.</p>

                        <div class="tabela-vacinas-container table-responsive">
                            <table class="tabela-vacinas data-table">
                                <thead>
                                    <tr>
                                        <th>Vacina</th>
                                        <th>Recomendação</th>
                                        <th>Status</th>
                                        <th>Data Aplicação</th>
                                        <th>Próxima Dose</th>
                                        <th>Enfermeiro Aplicador</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($vacinas_do_paciente) > 0): ?>
                                        <?php foreach ($vacinas_do_paciente as $vacina): 
                                            
                                            $data_aplicacao = $vacina['data_tomada'];
                                            $data_prevista = $vacina['data_prevista'];
                                            $nome_enfermeiro = $vacina['nome_enfermeiro'];
                                            $hoje = date('Y-m-d');

                                            // --- LÓGICA DE STATUS ATUALIZADA ---
                                            if (!empty($data_aplicacao)) {
                                                // Cenário 1: Vacina Tomada
                                                $status = 'Aplicada';
                                                $status_class = 'status-aplicada';
                                                $data_aplicacao_formatada = date('d/m/Y', strtotime($data_aplicacao));
                                                $proxima_dose_formatada = '-'; // Se já tomou, não tem próxima dose para ESTA vacina específica
                                                $enfermeiro_display = !empty($nome_enfermeiro) ? htmlspecialchars($nome_enfermeiro) : 'Não informado';
                                            
                                            } elseif (!empty($data_prevista)) {
                                                // Cenário 2: Vacina Agendada (mas não tomada)
                                                $data_aplicacao_formatada = 'N/A';
                                                $proxima_dose_formatada = date('d/m/Y', strtotime($data_prevista));
                                                $enfermeiro_display = '-';

                                                if ($hoje < $data_prevista) {
                                                    // Se hoje é ANTES da data prevista
                                                    $status = 'Aguardando';
                                                    $status_class = 'status-aguardando';
                                                } else {
                                                    // Se hoje é IGUAL ou DEPOIS da data prevista (e não tomou)
                                                    $status = 'Pendente';
                                                    $status_class = 'status-pendente';
                                                }

                                            } else {
                                                // Cenário 3: Nem tomada, nem agendada (ex: doses futuras não desbloqueadas)
                                                $status = 'Não Tomada';
                                                $status_class = 'status-neutro';
                                                $data_aplicacao_formatada = '-';
                                                $proxima_dose_formatada = '-';
                                                $enfermeiro_display = '-';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vacina['nome_vacina']); ?></td>
                                                <td><?php echo htmlspecialchars($vacina['recomendacao_idade']); ?></td>
                                                <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></td>
                                                <td><?php echo htmlspecialchars($data_aplicacao_formatada); ?></td>
                                                <td><?php echo htmlspecialchars($proxima_dose_formatada); ?></td>
                                                <td><?php echo $enfermeiro_display; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Nenhuma vacina encontrada.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div> 
                    
                    <div class="cards-laterais">
                        <div class="card-apoio">
                            <h4><i class="fas fa-headset"></i> Solicitar Apoio do Enfermeiro</h4>
                            <p>Envie uma mensagem direta ao enfermeiro responsável pelo seu posto de saúde.</p>
                            <div id="button_center"><button id="abrir-apoio-modal" class="btn-principal"><i class="fas fa-paper-plane"></i> Enviar Solicitação</button></div>
                        </div>

                        <div class="card-lembretes">
                            <h4><i class="fas fa-bell"></i> Lembretes</h4>
                            <?php 
                            $proximo_compromisso = 'Nenhum lembrete ativo.';
                            foreach ($vacinas_do_paciente as $vacina) {
                                // Só mostra lembrete se estiver pendente ou aguardando (prevista existe e tomada não)
                                if (!empty($vacina['data_prevista']) && empty($vacina['data_tomada'])) {
                                    $data_proxima_dose = date('d/m/Y', strtotime($vacina['data_prevista']));
                                    $proximo_compromisso = "Você tem uma dose agendada da vacina <strong>{$vacina['nome_vacina']}</strong> para <strong>{$data_proxima_dose}</strong>.";
                                    break; 
                                }
                            }
                            ?>
                            <p><?php echo $proximo_compromisso; ?></p>
                        </div>
                    </div>

                </div>

                <?php endif; ?>
                
            </div>
        </section>
    </main>

    <?php include 'modal_logout.html'; ?>
    <script src='modal.js'></script>
</body>
</html>