<?php
// Define a função permitida para esta página (acesso universal)
$funcao_permitida = ['paciente', 'enfermeiro', 'administrador'];
// Inclui o script de verificação
include '../verificar_acesso.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Meu Perfil</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
</head>
<body>

    <?php 
        include 'header.php'; // Inclui o cabeçalho com o menu de perfil 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>👤 Meu Perfil</h2>
                
                <p>Aqui você verá suas informações básicas cadastradas.</p>
                
                <div class="data-display">
                    <p><strong>Nome Completo:</strong> <?php echo htmlspecialchars($_SESSION['nome_completo']); ?></p>
                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    <p><strong>Função:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['funcao'])); ?></p>
                    </div>

                <p style="margin-top: 30px;">
                    Para alterar seus dados, acesse a seção **Configurações**.
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