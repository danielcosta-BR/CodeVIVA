<?php
// Define a função permitida para esta página
$funcao_permitida = 'enfermeiro';
// Inclui o script de verificação
include 'verificar_acesso.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Painel do Enfermeiro</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../style.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='enfermeiro.css'>
    <!-- <link rel='stylesheet' type='text/css' media='screen' href='../form.css'> -->
     <script src="../login.js"></script>
</head>
<body>

    <header>
        <div class="cabeca">
            <div><img class="logo" src="../Img/Logo 2.0 color.png"></div>
            <div class="buttons buttonsR">
                <a class="btn2" href="../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>👨‍⚕️ Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>!</h2>
                <h3>ENFERMEIRO</h3>
                
                <p>Por favor, antes de visualizar a cardeneta dos pacientes, <a>clique</a> no botão abaixo para configurar 
                seu local de atendimento e outros dados para validarmos suas alterações e administrações feitas aqui posteriormente.</p>

                <div class="form-section">
                    <button onclick="alert('Funcionalidade ainda não implementada!')">
                        Configurações
                    </button>
                </div>
            </div>
        </section>
    </main>
</body>
</html>