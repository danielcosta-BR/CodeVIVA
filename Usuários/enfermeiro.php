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
                <h2>👨‍⚕️ Bem-vindo(a), Enfermeiro(a) <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>!</h2>
                <h3>ENFERMEIRO</h3>
                
                <p>Aqui você gerencia agendamentos, registra aplicações e controla o estoque.</p>
            </div>
        </section>
    </main>
</body>
</html>