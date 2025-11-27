<?php
// Define a função permitida para esta página
$funcao_permitida = 'enfermeiro';
// Inclui o script de verificação
include '../verificar_acesso.php'; 
// Inclui a conexão com o banco de dados
include '../conexao.php'; 

$id_usuario = $_SESSION['id_usuario'] ?? '0';
$nome_completo = $_SESSION['nome_completo'] ?? 'Usuário Desconhecido';
$funcao = $_SESSION['funcao'] ?? 'Função Não Definida';
$email_usuario = $_SESSION['email'] ?? 'E-mail não disponível';

// Variáveis para os dados do enfermeiro
$cpf_enfermeiro = 'N/A';
$telefone_enfermeiro = 'N/A';
$endereco_enfermeiro = 'N/A';
$posto_saude_nome = 'Nenhum Posto Atribuído';
$mensagem_alerta = '';

// =========================================================================
// LÓGICA DE BUSCA DOS DADOS DO ENFERMEIRO
// =========================================================================
if ($id_usuario) {
    // 1. Buscar dados na tabela 'enfermeiros'
    $sql_dados = "SELECT cpf, telefone, endereco, id_posto_saude FROM enfermeiros WHERE id_usuario = ?";
    $stmt_dados = $conn->prepare($sql_dados);
    
    if ($stmt_dados) {
        $stmt_dados->bind_param("i", $id_usuario);
        $stmt_dados->execute();
        $result_dados = $stmt_dados->get_result();
        
        if ($result_dados->num_rows > 0) {
            $dados = $result_dados->fetch_assoc();
            
            $cpf_enfermeiro = !empty($dados['cpf']) ? htmlspecialchars($dados['cpf']) : 'Não Cadastrado';
            $telefone_enfermeiro = !empty($dados['telefone']) ? htmlspecialchars($dados['telefone']) : 'Não Cadastrado';
            $endereco_enfermeiro = !empty($dados['endereco']) ? htmlspecialchars($dados['endereco']) : 'Não Cadastrado';
            $id_posto_saude = $dados['id_posto_saude'];

            if ($id_posto_saude) {
                // 2. Buscar nome do posto de saúde
                $sql_posto = "SELECT nome_posto FROM postosaude WHERE id_posto = ?";
                $stmt_posto = $conn->prepare($sql_posto);

                if ($stmt_posto) {
                    $stmt_posto->bind_param("i", $id_posto_saude);
                    $stmt_posto->execute();
                    $result_posto = $stmt_posto->get_result();
                    if ($result_posto->num_rows > 0) {
                        $posto_saude_nome = htmlspecialchars($result_posto->fetch_assoc()['nome_posto']);
                    }
                    $stmt_posto->close();
                }
            } else {
                $posto_saude_nome = 'Nenhum Posto Atribuído';
            }
        } else {
            $mensagem_alerta = 'É necessário preencher seus dados de configuração. Acesse o menu "Configurações".';
        }
        $stmt_dados->close();
    } else {
        $mensagem_alerta = 'Erro de preparo da consulta de dados. Contate o administrador.';
    }
}
$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Perfil do Enfermeiro</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleenf.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../modal.css'>
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php include 'header_enf.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2><i class="fas fa-user-circle"></i> Meu Perfil</h2>
                <div class="title_function" 
                    style="padding: 10px;
                    background-color: #5ab4c086;
                    text-align: center;
                    border-radius: 5px;">
                    <h3 
                        style="border-bottom: 0px; 
                        margin-bottom: 0px; 
                        padding-bottom: 0px;">
                        ENFERMEIRO
                    </h3>
                </div>
                <p>Aqui estão seus dados básicos de acesso e as informações complementares.</p>
                <div class="profile-icon-2">
                    <?php echo $inicial_nome; ?> 
                </div>

                <?php if ($mensagem_alerta): ?>
                    <div class="alerta-config" style=" margin-top: 20px; background-color: #c7e3e6ff; border-color: #869a9cff; border-radius: 15px; color: #333; margin-bottom: 20px;">
                        <p style="padding: 10px 0;">
                            <i class="fas fa-exclamation-triangle" style="color: #ff9800; margin-right: 10px;"></i>
                            <?php echo htmlspecialchars($mensagem_alerta); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="data-group">
                    <h3>Dados de Acesso</h3>
                    <div class="data-display">
                        <p><strong><i class="fas fa-id-badge"></i> ID de Usuário:</strong> <?php echo htmlspecialchars($id_usuario); ?></p>
                        <p><strong><i class="fas fa-user-circle"></i> Nome Completo:</strong> <?php echo htmlspecialchars($nome_completo); ?></p>
                        <p><strong><i class="fas fa-at"></i> E-mail:</strong> <?php echo htmlspecialchars($email_usuario); ?></p>
                        <p><strong><i class="fas fa-user-tag"></i> Função:</strong> <?php echo ucfirst(htmlspecialchars($funcao)); ?></p>
                    </div>
                </div>

                <div class="data-group" style="margin-top: 30px;">
                    <h3>Dados Complementares (Enfermeiro)</h3>
                    <div class="data-display">
                        <p><strong><i class="fas fa-id-card"></i> CPF:</strong> <?php echo $cpf_enfermeiro; ?></p>
                        <p><strong><i class="fas fa-phone"></i> Telefone:</strong> <?php echo $telefone_enfermeiro; ?></p>
                        <p><strong><i class="fas fa-map-marker-alt"></i> Endereço:</strong> <?php echo $endereco_enfermeiro; ?></p>
                        <p><strong><i class="fas fa-hospital-symbol"></i> Posto de Saúde:</strong> <?php echo $posto_saude_nome; ?></p>
                    </div>
                </div>

                <p style="margin-top: 30px;">
                    Para alterar seus dados, acesse a seção <b>Configurações</b> no menu superior.
                </p>
                
            </div>
        </section>
    </main>
    
    <?php 
        include '../modal_logout.html'; 
    ?>
    <script src='../modal.js'></script>
</body>
</html>