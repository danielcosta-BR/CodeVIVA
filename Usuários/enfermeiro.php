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

    <main style="padding-top: 100px;">
        <h2>👨‍⚕️ Bem-vindo(a), Enfermeiro(a) <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>!</h2>
        <h3>Sua Função: Enfermeiro</h3>
        
        <p>Aqui você gerencia agendamentos, registra aplicações e controla o estoque.</p>

        </main>
</body>
</html>