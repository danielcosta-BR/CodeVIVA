<?php
// Define a função permitida para esta página (acesso universal)
$funcao_permitida = 'paciente';
// Inclui o script de verificação
include '../verificar_acesso.php'; 
// Inclui a conexão com o banco de dados
include '../conexao.php'; // Adicionado para a conexão


$id_usuario = $_SESSION['id_usuario'] ?? '0';
$nome_completo = $_SESSION['nome_completo'] ?? 'Usuário Desconhecido';
$funcao = $_SESSION['funcao'] ?? 'Função Não Definida';
$email_usuario = $_SESSION['email'] ?? 'E-mail não disponível';

// Variáveis para os dados do paciente
$cpf_paciente = 'N/A';
$telefone_paciente = 'N/A';
$endereco_paciente = 'N/A';
$posto_saude_nome = 'Nenhum Posto Atribuído';
$mensagem_alerta = '';

// =========================================================================
// LÓGICA DE BUSCA DOS DADOS DO PACIENTE
// =========================================================================
if ($id_usuario) {
    // 1. Buscar dados na tabela 'pacientes'
    $sql_dados = "SELECT cpf, telefone, endereco, id_posto_saude FROM pacientes WHERE id_usuario = ?";
    $stmt_dados = $conn->prepare($sql_dados);
    
    if ($stmt_dados) {
        $stmt_dados->bind_param("i", $id_usuario);
        $stmt_dados->execute();
        $result_dados = $stmt_dados->get_result();

        if ($result_dados->num_rows > 0) {
            $dados_paciente = $result_dados->fetch_assoc();
            
            // Atribui os dados, se existirem
            $cpf_paciente = !empty($dados_paciente['cpf']) ? htmlspecialchars($dados_paciente['cpf']) : 'Não informado';
            $telefone_paciente = !empty($dados_paciente['telefone']) ? htmlspecialchars($dados_paciente['telefone']) : 'Não informado';
            $endereco_paciente = !empty($dados_paciente['endereco']) ? htmlspecialchars($dados_paciente['endereco']) : 'Não informado';
            
            $id_posto_saude = $dados_paciente['id_posto_saude'];

            // 2. Buscar o nome do Posto de Saúde (se houver um ID)
            if ($id_posto_saude) {
                // CORRIGIDO: Usando nome_posto, conforme seu DDL
                $sql_posto = "SELECT nome_posto FROM postosaude WHERE id_posto = ?";
                $stmt_posto = $conn->prepare($sql_posto);
                $stmt_posto->bind_param("i", $id_posto_saude);
                $stmt_posto->execute();
                $result_posto = $stmt_posto->get_result();
                
                if ($result_posto->num_rows > 0) {
                    $posto_saude_nome = htmlspecialchars($result_posto->fetch_assoc()['nome_posto']);
                }
                $stmt_posto->close();
            } else {
                 $mensagem_alerta = 'Você ainda não selecionou um Posto de Saúde. Por favor, acesse Configurações.';
            }

        } else {
            // Se não encontrou linha em 'pacientes', o usuário não completou o cadastro básico
            $mensagem_alerta = 'Seu cadastro complementar ainda não foi preenchido. Por favor, acesse Configurações.';
        }

        $stmt_dados->close();
    } else {
        $mensagem_alerta = 'Erro interno ao preparar a busca de dados: ' . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Meu Perfil</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php 
        include 'header.php'; // Inclui o cabeçalho com o menu de perfil 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container"> <!-- Reduzindo a largura para um visual de perfil -->
                <h2><i class="fas fa-user-circle"></i> Meu Perfil</h2>
                <p>Aqui estão seus dados básicos de acesso e as informações complementares.</p>
                <div class="profile-icon-2">
                    <?php echo $inicial_nome; ?> 
                </div>
                
                <?php if ($mensagem_alerta): ?>
                    <div class="alerta-config" style="background-color: #ffe0b2; border-color: #ff9800; color: #333; margin-bottom: 20px;">
                        <p style="margin: 0; padding: 10px 0;">
                            <i class="fas fa-exclamation-triangle" style="color: #ff9800; margin-right: 10px;"></i>
                            <?php echo htmlspecialchars($mensagem_alerta); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="data-group">
                    <h3>Dados de Acesso</h3>
                    <div class="data-display">
                        <p><strong><i class="fas fa-id-badge"></i> ID de Usuário:</strong> <?php echo htmlspecialchars($id_usuario); ?></p>
                        <p><strong><i class="fas fa-user"></i> Nome Completo:</strong> <?php echo htmlspecialchars($nome_completo); ?></p>
                        <p><strong><i class="fas fa-at"></i> E-mail:</strong> <?php echo htmlspecialchars($email_usuario); ?></p>
                        <p><strong><i class="fas fa-user-tag"></i> Função:</strong> <?php echo ucfirst(htmlspecialchars($funcao)); ?></p>
                    </div>
                </div>

                <div class="data-group" style="margin-top: 30px;">
                    <h3>Dados Complementares (Paciente)</h3>
                    <div class="data-display">
                        <p><strong><i class="fas fa-id-card"></i> CPF:</strong> <?php echo $cpf_paciente; ?></p>
                        <p><strong><i class="fas fa-phone"></i> Telefone:</strong> <?php echo $telefone_paciente; ?></p>
                        <p><strong><i class="fas fa-map-marker-alt"></i> Endereço:</strong> <?php echo $endereco_paciente; ?></p>
                        <p><strong><i class="fas fa-hospital"></i> Posto de Saúde:</strong> <?php echo $posto_saude_nome; ?></p>
                    </div>
                </div>

                <p style="margin-top: 30px;">
                    Para alterar seus dados, acesse a seção <b>Configurações</b>.
                </p>
                
            </div>
        </section>
    </main>
    
    <?php 
        include '../modal_logout.html'; // Inclui o modal de logout 
    ?>
    <script src="../modal.js"></script>
</body>
</html>