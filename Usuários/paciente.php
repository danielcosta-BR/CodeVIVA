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

</head>
<body>

    <?php
        // O header.php já tem a lógica para o link "Início" e o dropdown de perfil
        include 'header_pct.php';
    ?>

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
                // Se a configuração estiver completa, exibe o dashboard
                else: 
                // =========================================================================
                // 2. LÓGICA DO DASHBOARD COMPLETO (AJUSTADO PARA O DDL)
                // =========================================================================
                
                $vacinas_do_paciente = [];
                $posto_saude_paciente = "Não Definido"; 

                // 2.1. Buscar nome do posto de saúde (USANDO postosaude)
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
                
                // 2.2. Buscar Vacinas (USANDO vacinamodelo e caderneta)
                $sql_vacinas = "
                    SELECT 
                        vm.nome_vacina, 
                        vm.recomendacao_idade, 
                        c.data_tomada,
                        c.data_prevista
                    FROM 
                        vacinamodelo vm
                    LEFT JOIN 
                        caderneta c ON vm.id_vacina_modelo = c.id_vacina_modelo AND c.id_paciente = ?
                    ORDER BY 
                        vm.nome_vacina ASC
                ";
                
                $stmt_vacinas = $conn->prepare($sql_vacinas);
                if ($stmt_vacinas) {
                    $stmt_vacinas->bind_param("i", $id_usuario);
                    $stmt_vacinas->execute();
                    $result_vacinas = $stmt_vacinas->get_result();
                    
                    if ($result_vacinas->num_rows > 0) {
                        while ($row = $result_vacinas->fetch_assoc()) {
                            $vacinas_do_paciente[] = $row;
                        }
                    }
                    $stmt_vacinas->close();
                } else {
                    error_log("Erro ao preparar a busca de vacinas: " . $conn->error);
                }

                // Fecha a conexão após todas as buscas
                $conn->close();
                ?>
                <!-- CABEÇALHO DE BOAS-VINDAS E INFO POSTO -->
                <div class="header-paciente">
                    <p class="posto-info"><i class="fas fa-hospital"></i> Posto de Saúde: <strong><?php echo htmlspecialchars($posto_saude_paciente); ?></strong></p>
                    
                </div>

                <!-- GRUPO DE FUNCIONALIDADES -->
                <div class="painel-funcionalidades">
                    
                    <!-- 1. CARDENETA DE VACINAÇÃO (VISUALIZAÇÃO CENTRAL) -->
                    <div class="card-cardeneta">
                        <h3><i class="fas fa-syringe"></i> Minha Caderneta de Vacinação</h3>
                        <p class="intro-text">Aqui você pode visualizar todas as vacinas recomendadas e o seu status de aplicação.</p>

                        <!-- Tabela de Vacinas -->
                        <div class="tabela-vacinas-container table-responsive">
                            <table class="tabela-vacinas data-table">
                                <thead>
                                    <tr>
                                        <th>Vacina</th>
                                        <th>Recomendação</th>
                                        <th>Status</th>
                                        <th>Data Aplicação</th>
                                        <th>Próxima Dose</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($vacinas_do_paciente) > 0): ?>
                                        <?php foreach ($vacinas_do_paciente as $vacina): 
                                            
                                            // Lógica de Status (baseada em data_tomada e data_prevista)
                                            $data_aplicacao = $vacina['data_tomada'];
                                            $data_prevista = $vacina['data_prevista'];

                                            if (!empty($data_aplicacao)) {
                                                $status = 'Aplicada';
                                                $status_class = 'status-aplicada';
                                                $data_aplicacao_formatada = date('d/m/Y', strtotime($data_aplicacao));
                                            } elseif (!empty($data_prevista)) {
                                                $status = 'Agendada';
                                                $status_class = 'status-agendada';
                                                $data_aplicacao_formatada = 'N/A';
                                            } else {
                                                $status = 'Pendente';
                                                $status_class = 'status-pendente';
                                                $data_aplicacao_formatada = 'N/A';
                                            }
                                            
                                            $proxima_dose_formatada = !empty($data_prevista) ? date('d/m/Y', strtotime($data_prevista)) : 'N/A';
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vacina['nome_vacina']); ?></td>
                                                <td><?php echo htmlspecialchars($vacina['recomendacao_idade']); ?></td>
                                                <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></td>
                                                <td><?php echo htmlspecialchars($data_aplicacao_formatada); ?></td>
                                                <td><?php echo htmlspecialchars($proxima_dose_formatada); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhuma vacina encontrada ou seu cadastro está incompleto.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- Fim Cardeneta -->
                    
                    <!-- 2. SOLICITAR AJUDA E LEMBRETES (CARDS LATERAIS) -->
                    <div class="cards-laterais">
                        
                        <!-- 2.1. Solicitar Apoio / Ajuda -->
                        <div class="card-apoio">
                            <h4><i class="fas fa-headset"></i> Solicitar Apoio do Enfermeiro</h4>
                            <p>Envie uma mensagem direta ao enfermeiro responsável pelo seu posto de saúde. Exponha dúvidas ou solicite um agendamento.</p>
                            <div id="button_center"><button id="abrir-apoio-modal" class="btn-principal"><i class="fas fa-paper-plane"></i> Enviar Solicitação</button></div>
                        </div>

                        <!-- 2.2. Lembretes e Notificações -->
                        <div class="card-lembretes">
                            <h4><i class="fas fa-bell"></i> Lembretes e Notificações</h4>
                            <!-- Lógica de Lembretes Simplificada -->
                            <?php 
                            $proximo_compromisso = 'Nenhum lembrete ativo.';
                            $lembrete_encontrado = false;
                            
                            foreach ($vacinas_do_paciente as $vacina) {
                                // Verifica se há uma próxima dose e se ela ainda não foi tomada
                                if (!empty($vacina['data_prevista']) && empty($vacina['data_tomada'])) {
                                    $data_proxima_dose = date('d/m/Y', strtotime($vacina['data_prevista']));
                                    $proximo_compromisso = "Você tem uma dose agendada da vacina <strong>{$vacina['nome_vacina']}</strong> em <strong>{$data_proxima_dose}</strong>.";
                                    $lembrete_encontrado = true;
                                    break; 
                                }
                            }
                            ?>
                            <p><?php echo $proximo_compromisso; ?></p>
                            <a href="#" class="btn-secundario"><i class="fas fa-list-ul"></i> Ver todos os lembretes</a>
                        </div>
                        
                    </div> <!-- Fim Cards Laterais -->

                </div> <!-- Fim Painel Funcionalidades -->

                <?php endif; ?>
                
            </div>
        </section>

    </main>

    <?php 
        include 'modal_logout.html'; 
        
        // Inclui o modal de solicitação APENAS se o dashboard estiver visível
        if ($config_completa) {
            // OBS: Você precisa criar o modal_solicitacao_apoio.html e paciente.js
            // Por enquanto, apenas os scripts globais são inclusos.
            // include 'modal_solicitacao_apoio.html'; 
            // echo "<script src='paciente.js'></script>";
        }
    ?>
    <script src='modal.js'></script>
</body>
</html>