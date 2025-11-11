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
    <title>VIVA+ | Painel do Administrador</title>
    <link rel='stylesheet' type='text/css' media='screen' href='administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleadm.css'>
    <!-- <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .cabeca { background-color: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .cabeca .logo { height: 40px; }
        .cabeca .buttonsR a { color: white; text-decoration: none; margin-left: 15px; }
        main { padding: 20px; max-width: 900px; margin: 20px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-section { margin-top: 20px; }
        .form-section h4 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .form-section p { font-size: 0.9em; color: #666; }
        .form-section button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
        .form-section button:hover { background-color: #0056b3; }
    </style> -->
</head>
<body>
    <header>
        <div class="cabeca">
            <div><img class="logo" src="../Img/Logo 2.0 color.png" alt="Logo VIVA+"></div>
            <div class="buttons buttonsR">
                <a class="btn2" href="../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="form-container">

        <h2>👑 Painel do Administrador</h2>
        <h3>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>!</h3>
        
        <?php echo $feedback_message; // Exibe a mensagem de feedback/sucesso ?>

        <div class="form-section">
            <h4>GERAR CÓDIGO DE VERIFICAÇÃO PARA ENFERMEIRO</h4>
            <p>Clique no botão abaixo para gerar um código de uso único. Este código deve ser fornecido ao novo Enfermeiro para que ele possa completar seu registro no sistema.</p>
            
            <form action="processa_geracao_codigo.php" method="POST"  id="button_center">
                <button type="submit">
                    Gerar Novo Código
                </button>
            </form>
        </div>

        <div class="form-section">
            <h4>GERENCIAR POSTOS DE SAÚDE</h4>
            <p>Listar, adicionar, editar ou remover postos de saúde.</p>
            <button onclick="alert('Funcionalidade ainda não implementada!')">
                Acessar Gerenciamento de Postos
            </button>
        </div>

        <div class="form-section">
            <h4>GERENCIAR USUÁRIOS</h4>
            <p>Visualizar e editar usuários (pacientes, enfermeiros).</p>
            <button onclick="alert('Funcionalidade ainda não implementada!')">
                Acessar Gerenciamento de Usuários
            </button>
        </div>

    </main>
</body>
</html>