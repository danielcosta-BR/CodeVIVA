<?php
// Usuarios/administrador.php

// Define a função permitida para esta página
$funcao_permitida = 'administrador';
// Inclui o script de verificação
include 'verificar_acesso.php'; 

// Lógica para exibir mensagens de feedback
$feedback_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'codigo_gerado' && isset($_GET['novo_codigo'])) {
        $novo_codigo = htmlspecialchars($_GET['novo_codigo']);
        $feedback_message = "
            <div style='background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>
                <strong>✅ Sucesso!</strong> Novo Código para Enfermeiro gerado: 
                <span style='font-size: 1.2em; font-weight: bold;'>{$novo_codigo}</span>
                <p>Informe este código ao novo Enfermeiro para que ele complete o cadastro.</p>
            </div>
        ";
    }
} elseif (isset($_GET['erro'])) {
    $erro_msg = '';
    if ($_GET['erro'] === 'falha_db') {
        $erro_msg = 'Erro de conexão com o banco de dados.';
    } elseif ($_GET['erro'] === 'falha_insercao') {
        $erro_msg = 'Falha ao inserir o código no banco de dados.';
    } elseif ($_GET['erro'] === 'falha_geracao') {
        $erro_msg = 'Não foi possível gerar um código único após várias tentativas.';
    } else {
        $erro_msg = 'Ocorreu um erro desconhecido.';
    }
    $feedback_message = "
        <div style='background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>
            <strong>❌ Erro:</strong> {$erro_msg}
        </div>
    ";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Painel do Administrador</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
    <script src="modal.js"></script>
</head>
<body>
    <?php
        include 'header_adm.php';
    ?>

    <main class="form-container">

        <h2>👑 Painel do Administrador</h2>
        <h3>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>!</h3>
        
        <?php echo $feedback_message; // Exibe a mensagem de feedback/sucesso ?>

        <div class="form-section">
            <h4>GERENCIAMENTO DE ACESSO E USUÁRIOS</h4>
            <p>Gere códigos de acesso para enfermeiros e visualize/edite a lista de todos os usuários.</p>
            
            <form action="processa_geracao_codigo.php" method="POST" id="button_center">
                <button type="submit">
                    Gerar Novo Código de Enfermeiro
                </button>
            </form>
            
            <div id="usermanage-btn">
                <button onclick="window.location.href='adm/gerenciar_usuarios.php'" >
                    Gerenciar Usuários (Pacientes, Enfermeiros)
                </button>
            </div>
        </div>
        
        <div class="form-section">
            <h4>GERENCIAMENTO DE INFRAESTRUTURA</h4>
            <p>Configure a base do sistema: Postos, Vacinas e Alertas de Saúde.</p>
            
            <div class="form-sectionR">
                <button onclick="window.location.href='adm/gerenciar_postos.php'">
                    <i class="fas fa-hospital"></i> Gerenciar Postos de Saúde
                </button>
                
                <button onclick="window.location.href='adm/gerenciar_vacinas.php'">
                    <i class="fas fa-syringe"></i> Gerenciar Vacinas Cadastradas
                </button>
    
                <!-- NOVO BOTÃO ADICIONADO AQUI -->
                <button onclick="window.location.href='adm/gerenciar_doencas.php'">
                    <i class="fas fa-virus"></i> Gerenciar Doenças e Alertas
                </button>
            </div>
        </div>

    </main>
    <?php 
        include 'modal_logout.html'; 
    ?>
</body>
</html>